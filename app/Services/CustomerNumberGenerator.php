<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CustomerNumberGenerator
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = config('lms.customer_number_prefix', 'CUS');

        return DB::transaction(function () use ($prefix, $year) {
            DB::table('customer_number_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'year' => $year,
                'sequence' => 0,
            ]);

            $sequence = DB::table('customer_number_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->value('sequence') + 1;

            DB::table('customer_number_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update(['sequence' => $sequence]);

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
        });
    }
}
