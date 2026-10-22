<?php

namespace App\Services\Payments;

use App\Models\Receipt;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ReceiptPdf
{
    public static function render(Receipt $receipt): string
    {
        $html = view('receipts.receipt', [
            'receipt' => $receipt,
            'data' => $receipt->snapshot,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
