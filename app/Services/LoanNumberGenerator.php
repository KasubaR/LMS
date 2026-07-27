<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LoanNumberGenerator
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = config('lms.loan_number_prefix', 'LN');

        return DB::transaction(function () use ($prefix, $year) {
            DB::table('loan_number_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'year' => $year,
                'sequence' => 0,
            ]);

            $sequence = DB::table('loan_number_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->value('sequence') + 1;

            DB::table('loan_number_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update(['sequence' => $sequence]);

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
        });
    }
}
