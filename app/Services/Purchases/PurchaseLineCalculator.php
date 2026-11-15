<?php

namespace App\Services\Purchases;

class PurchaseLineCalculator
{
    /**
     * @return array{line_total: float, tax_amount: float, net_before_tax: float}
     */
    public function compute(float $quantity, float $unitPrice, float $discountAmount, float $taxRate): array
    {
        $gross = round($quantity * $unitPrice, 4);
        $netBeforeTax = round(max(0, $gross - $discountAmount), 2);
        $taxAmount = round($netBeforeTax * ($taxRate / 100), 2);
        $lineTotal = round($netBeforeTax + $taxAmount, 2);

        return [
            'net_before_tax' => $netBeforeTax,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * @param  iterable<array{line_total: float, discount_amount?: float, tax_amount?: float}>  $lines
     * @return array{subtotal: float, discount_total: float, tax_total: float, grand_total: float}
     */
    public function summarize(iterable $lines): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lines as $line) {
            $lineTotal = (float) ($line['line_total'] ?? 0);
            $subtotal += $lineTotal;
            $discountTotal += (float) ($line['discount_amount'] ?? 0);
            $taxTotal += (float) ($line['tax_amount'] ?? 0);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($subtotal, 2),
        ];
    }
}