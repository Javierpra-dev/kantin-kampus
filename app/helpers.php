<?php

if (!function_exists('rupiah')) {
    /**
     * Format a numeric amount to Indonesian Rupiah representation.
     * Example: 15000 -> "Rp15.000"
     */
    function rupiah(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return 'Rp0';
        }

        $numericAmount = is_numeric($amount) ? (float) $amount : 0;
        return 'Rp' . number_format($numericAmount, 0, ',', '.');
    }
}
