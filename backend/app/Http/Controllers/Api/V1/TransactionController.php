<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\Payments\ReceiptIssuer;
use App\Services\Payments\ReceiptPdf;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    private const LABELS = [
        'created' => 'En attente',
        'pending' => 'En attente',
        'requires_action' => 'Action requise',
        'paid' => 'Payé',
        'failed' => 'Échec',
        'expired' => 'Expiré',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
            'supplier_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'reference' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->baseQuery($request->user()->id)->orderByDesc('payments.id');

        if (! empty($filters['status'])) {
            $query->where('payments.status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('orders.supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('payments.created_at', '>=', Carbon::createFromFormat('Y-m-d', $filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('payments.created_at', '<=', Carbon::createFromFormat('Y-m-d', $filters['to'])->endOfDay());
        }

        if (! empty($filters['reference'])) {
            $ref = $filters['reference'];

            // Correspondance exacte : référence de transaction, de commande ou numéro de reçu.
            $query->where(function ($q) use ($ref) {
                $q->where('payments.reference', $ref)
                    ->orWhere('orders.reference', $ref)
                    ->orWhere('receipts.receipt_number', $ref);
            });
        }

        return TransactionResource::collection($query->paginate($filters['per_page'] ?? 20));
    }

    public function show(Request $request, Payment $payment): TransactionResource
    {
        return new TransactionResource($this->findRow($request, $payment));
    }

    public function receipt(Request $request, Payment $payment): JsonResponse
    {
        $row = $this->findRow($request, $payment);

        $receipt = $row->receipt_id ? Receipt::find($row->receipt_id) : null;

        // Rattrapage : un paiement confirmé doit toujours avoir son reçu officiel.
        if ($receipt === null && $row->status === PaymentStatus::Paid->value) {
            $receipt = ReceiptIssuer::issueFor($payment);
        }

        return response()->json([
            'data' => $receipt ? $this->issued($receipt) : $this->provisional($row),
        ]);
    }

    public function download(Request $request, Receipt $receipt): Response
    {
        // 404 plutôt que 403 : on ne révèle pas l'existence des reçus des autres.
        abort_unless((int) $receipt->user_id === (int) $request->user()->id, 404);

        return response(ReceiptPdf::render($receipt), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt->receipt_number.'.pdf"',
        ]);
    }

    private function baseQuery(int $userId): Builder
    {
        return DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->join('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
            ->leftJoin('receipts', function ($join) {
                $join->on('receipts.payment_id', '=', 'payments.id')
                    ->where('receipts.receipt_type', '=', 'payment');
            })
            ->where('payments.user_id', $userId)
            ->select([
                'payments.id',
                'payments.reference',
                'payments.created_at',
                'payments.confirmed_at',
                'payments.status',
                'payments.payment_method',
                'payments.amount_minor',
                'payments.currency',
                'orders.reference as order_reference',
                'orders.description',
                'orders.supplier_id',
                'suppliers.business_name as supplier_name',
                'receipts.id as receipt_id',
                'receipts.receipt_number',
            ]);
    }

    private function findRow(Request $request, Payment $payment): object
    {
        $row = $this->baseQuery($request->user()->id)->where('payments.id', $payment->id)->first();

        // 404 plutôt que 403 : on ne révèle pas l'existence des transactions des autres.
        abort_if($row === null, 404);

        return $row;
    }

    private function issued(Receipt $receipt): array
    {
        return [
            'type' => 'receipt',
            'is_proof_of_payment' => true,
            'receipt_number' => $receipt->receipt_number,
            'issued_at' => $receipt->issued_at->toIso8601String(),
            'status' => 'paid',
            'status_label' => self::LABELS['paid'],
            'document' => $receipt->snapshot,
            'download_url' => url("/api/v1/me/receipts/{$receipt->id}/download"),
        ];
    }

    private function provisional(object $row): array
    {
        return [
            'type' => 'provisional',
            'is_proof_of_payment' => false,
            'receipt_number' => null,
            'issued_at' => null,
            'status' => $row->status,
            'status_label' => self::LABELS[$row->status] ?? $row->status,
            'notice' => "Ce document n'est pas une preuve de paiement.",
            'document' => [
                'company_name' => config('receipts.company_name'),
                'support_email' => config('receipts.support_email'),
                'support_phone' => config('receipts.support_phone'),
                'payment_reference' => $row->reference,
                'order_reference' => $row->order_reference,
                'supplier_name' => $row->supplier_name,
                'description' => $row->description,
                'amount_minor' => (int) $row->amount_minor,
                'currency' => $row->currency,
                'payment_method' => $row->payment_method,
                'created_at' => Carbon::parse($row->created_at)->toIso8601String(),
                'paid_at' => null,
            ],
            'download_url' => null,
        ];
    }
}