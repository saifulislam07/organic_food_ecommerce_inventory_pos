<?php

namespace App\Support;

use App\Models\Order;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * An order's invoice as a PDF, for attaching to the receipt email.
 *
 * mPDF rather than dompdf because it shapes Bangla: product names, addresses
 * and the ৳ sign come out as joined script instead of broken glyphs. Hind
 * Siliguri is bundled in resources/fonts and set for the whole page, since it
 * carries Latin as well as Bangla.
 *
 * The layout is its own template (emails.orders.invoice-pdf) built from tables,
 * because mPDF has no flexbox and the on-screen invoice is laid out with it.
 */
class InvoicePdf
{
    public static function render(Order $order): string
    {
        $order->loadMissing('items');

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5',
            'margin_left' => 9,
            'margin_right' => 9,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => self::tempDir(),
            'fontDir' => array_merge(
                (new ConfigVariables)->getDefaults()['fontDir'],
                [resource_path('fonts')],
            ),
            'fontdata' => (new FontVariables)->getDefaults()['fontdata'] + [
                'hindsiliguri' => [
                    'R' => 'HindSiliguri-Regular.ttf',
                    'B' => 'HindSiliguri-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'hindsiliguri',
        ]);

        $mpdf->SetTitle("Invoice {$order->order_number}");
        $mpdf->WriteHTML(view('emails.orders.invoice-pdf', ['order' => $order])->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private static function tempDir(): string
    {
        $dir = storage_path('app/mpdf');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }
}
