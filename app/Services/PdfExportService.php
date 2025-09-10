<?php

namespace App\Services;

use Illuminate\Http\Response;

class PdfExportService
{
    /**
     * Stream a simple table as a PDF download using TCPDF.
     *
     * @param  string  $filename
     * @param  string  $title
     * @param  array<string>  $columns
     * @param  iterable<array<string,string|int|float|null>>  $rows
     */
    public static function streamSimpleTable(string $filename, string $title, array $columns, iterable $rows): Response
    {
        // Initialize TCPDF (landscape for wider tables)
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Shebar Laundry');
        $pdf->SetAuthor('Shebar Laundry');
        $pdf->SetTitle($title);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $safe = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Build minimal, robust HTML with inline styles for TCPDF
        $html = '<h2 style="font-family: sans-serif; font-size:18px; margin:0 0 8px 0;">' . $safe($title) . '</h2>';
        $html .= '<table cellspacing="0" cellpadding="6" border="1" style="font-size:11px; border-collapse:collapse;">';
        $html .= '<thead><tr style="background-color:#f3f4f6;">';
        foreach ($columns as $col) {
            $html .= '<th style="font-weight:bold; text-align:left;">' . $safe($col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        $rowAlt = false;
        foreach ($rows as $row) {
            $bg = $rowAlt ? '#ffffff' : '#fbfbfb';
            $rowAlt = !$rowAlt;
            $html .= '<tr style="background-color:'.$bg.';">';
            foreach ($row as $val) {
                $html .= '<td>' . $safe($val) . '</td>';
            }
            $html .= '</tr>';
        }
        if (empty($rows)) {
            $html .= '<tr><td colspan="'.count($columns).'" style="text-align:center; color:#6b7280;">No data to export.</td></tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $content = $pdf->Output($filename, 'S'); // return as string

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Stream an Order Invoice as PDF using TCPDF, with header/footer from settings.
     */
    public static function streamInvoicePdf(\App\Models\Order $order, array $company, string $filename = null): Response
    {
        $filename = $filename ?: ('invoice_'.$order->order_id.'_'.now()->format('Ymd_His').'.pdf');

        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public array $company = [];
        public function Header(): void {
                $c = $this->company;
                $s = fn($v)=>htmlspecialchars((string)($v ?? ''), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
                $html = '<table width="100%" cellspacing="0" cellpadding="2"><tr><td width="65%">'
                    .'<div style="font-size:16px; font-weight:bold;">'.$s($c['name'] ?? '').'</div>'
                    .'<div style="font-size:10px;">'.$s($c['address'] ?? '').'</div>'
            .'<div style="font-size:10px;">'.$s($c['phone'] ?? '').' '.$s($c['email'] ?? '').'</div>'
            .(empty($c['company_vat_no']) ? '' : '<div style="font-size:10px;">VAT Reg. No: '.$s($c['company_vat_no']).'</div>')
            .(empty($c['tin']) ? '' : '<div style="font-size:10px;">TIN: '.$s($c['tin']).'</div>').
                    '</td><td width="35%" align="right">';
                if (!empty($c['logo_url'])) {
                    $html .= '<img src="'.$s($c['logo_url']).'" height="45" />';
                }
                $html .= '</td></tr></table>';
                $this->writeHTML($html, false, false, false, false, '');
            }
            public function Footer(): void {
                $this->SetY(-18);
                $c = $this->company;
                $s = fn($v)=>htmlspecialchars((string)($v ?? ''), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
                $html = '<div style="font-size:10px; text-align:center; color:#666;">'.$s($c['footer'] ?? 'Thank you for your business.').'</div>';
                if (!empty($c['stamp_url'])) {
                    $html .= '<div style="text-align:center; margin-top:2px;"><img src="'.$s($c['stamp_url']).'" height="32" /></div>';
                }
                $this->writeHTML($html, false, false, false, false, '');
            }
        };
        $pdf->company = $company;
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Invoice '.$order->order_id);
        $pdf->SetMargins(10, 36, 10);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(10);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();

        // Content
        $subtotal = max(0, (float)($order->total_cost ?? 0) - (float)($order->penalty_amount ?? 0));
        $penalty = (float)($order->penalty_amount ?? 0);
        $total = (float)($order->total_cost ?? 0);
    $completed = (float)($order->payments()->where('status','completed')->sum('amount'));
    $refunded = (float)($order->payments()->where('status','refunded')->sum('amount'));
    $paid = max(0.0, $completed - $refunded);
        $due = max(0, $total - $paid);

        $safe = fn($v)=>htmlspecialchars((string)($v ?? ''), ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');

        $intro = '<table width="100%" cellspacing="0" cellpadding="2">'
            .'<tr><td><b>Invoice #</b> '.$safe($order->order_id).'</td><td align="right"><b>Date:</b> '.$safe(now()->format('Y-m-d H:i')).'</td></tr>'
            .'<tr><td><b>Customer:</b> '.$safe(optional($order->customer)->name).'</td><td align="right"><b>Phone:</b> '.$safe(optional($order->customer)->phone).'</td></tr>'
            .'</table>';
        $pdf->writeHTML($intro, true, false, true, false, '');

        $rows = '';
        foreach ($order->orderItems as $it) {
            foreach ($it->orderItemServices as $svc) {
                $rows .= '<tr>'
                    .'<td>'.$safe($it->clothItem->name).'</td>'
                    .'<td>'.$safe($svc->service->name).'</td>'
                    .'<td align="right">'.number_format((float)$svc->quantity,2).'</td>'
                    .'<td align="right">'.$safe(optional($it->clothItem->unit)->name ?? optional($it->unit)->name).'</td>'
                    .'<td align="right">'.number_format((float)$svc->price_applied,2).'</td>'
                    .'</tr>';
            }
        }
        $html = '<h3 style="margin:6px 0 4px 0;">Service Details</h3>'
            .'<table width="100%" cellspacing="0" cellpadding="4" border="1" style="border-collapse:collapse; font-size:11px;">'
            .'<thead><tr style="background-color:#f3f4f6;"><th>Cloth Item</th><th>Service</th><th align="right">Qty</th><th align="right">Unit</th><th align="right">Price (ETB)</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $totals = '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:11px; margin-top:6px;">'
            .'<tr><td width="80%" align="right"><b>Subtotal</b></td><td align="right">'.number_format($subtotal,2).' ETB</td></tr>'
            .'<tr><td align="right"><b>Penalty</b></td><td align="right">'.number_format($penalty,2).' ETB</td></tr>'
            .'<tr><td align="right"><b>Total</b></td><td align="right"><b>'.number_format($total,2).' ETB</b></td></tr>'
            .'<tr><td align="right"><b>Paid</b></td><td align="right">'.number_format($paid,2).' ETB</td></tr>'
            .'<tr><td align="right"><b>Due</b></td><td align="right">'.number_format($due,2).' ETB</td></tr>'
            .'</table>';
        $pdf->writeHTML($totals, true, false, true, false, '');

        $content = $pdf->Output($filename, 'S');
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
