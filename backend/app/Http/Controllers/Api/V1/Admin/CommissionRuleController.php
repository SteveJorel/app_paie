<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRuleRequest;
use App\Http\Resources\CommissionRuleResource;
use App\Models\CommissionRule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CommissionRuleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CommissionRule::query()
            ->orderBy('currency')
            ->orderBy('supplier_id')
            ->orderByDesc('version');

        if ($request->filled('currency')) {
            $query->where('currency', $request->query('currency'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->query('supplier_id'));
        }

        return CommissionRuleResource::collection($query->paginate(50));
    }

    public function store(CommissionRuleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $from = isset($data['valid_from']) ? Carbon::parse($data['valid_from']) : now();

        $rule = DB::transaction(function () use ($data, $from) {
            $scope = CommissionRule::query()
                ->where('currency', $data['currency'])
                ->when(
                    $data['supplier_id'] ?? null,
                    fn ($q, $id) => $q->where('supplier_id', $id),
                    fn ($q) => $q->whereNull('supplier_id')
                );

            $version = ((clone $scope)->max('version') ?? 0) + 1;

            // La nouvelle version clôture les règles actives précédentes de la même portée.
            (clone $scope)
                ->where('status', CommissionRule::STATUS_ACTIVE)
                ->where('valid_from', '<', $from)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', $from))
                ->get()
                ->each(fn (CommissionRule $old) => $old->update(['valid_until' => $from]));

            return CommissionRule::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'currency' => $data['currency'],
                'type' => $data['type'],
                'percentage_bps' => $data['percentage_bps'] ?? 0,
                'fixed_minor' => $data['fixed_minor'] ?? 0,
                'valid_from' => $from,
                'version' => $version,
                'status' => CommissionRule::STATUS_ACTIVE,
            ]);
        });

        return (new CommissionRuleResource($rule))->response()->setStatusCode(201);
    }

    public function retire(CommissionRule $commissionRule): CommissionRuleResource
    {
        $commissionRule->update(['status' => CommissionRule::STATUS_RETIRED]);

        return new CommissionRuleResource($commissionRule);
    }
}
