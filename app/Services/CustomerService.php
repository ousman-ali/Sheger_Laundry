<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerService
{
    public function generateCustomerCode(?string $name = null, bool $vip = false): string
    {
        $prefix = $vip ? 'VIP' : 'CUST';
        $base = strtoupper(Str::slug((string)($name ?? ''), ''));
        $base = $base ? substr($base, 0, 6) : ($vip ? 'VIP' : 'CUST');
        $try = 0;
        do {
            $suffix = $vip ? Str::upper(Str::random(3)) : Str::padLeft((string)random_int(0, 999), 3, '0');
            $code = $prefix.'-'.$base.'-'.$suffix;
            $exists = Customer::where('code', $code)->exists();
        } while ($exists && ++$try < 20);
        if ($exists) {
            // Fallback ultra-unique
            $code = $prefix.'-'.Str::upper(Str::random(8));
        }
        return $code;
    }
}
