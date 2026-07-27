<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class LoanScheduleGenerator
{
    /**
     * @param  array{
     *     principal: float|int|string,
     *     interest_rate: float|int|string,
     *     interest_type: string,
     *     duration_months: int,
     *     frequency: string,
     *     start_date: string|CarbonInterface,
     *     processing_fee?: float|int|string|null
     * }  $terms
     * @return array{installments: list<array<string, mixed>>, due_date: string, installment_count: int, total_interest: string, total_repayment: string}
     */
    public function generate(array $terms): array
    {
        $principal = round((float) $terms['principal'], 2);
        $rate = (float) $terms['interest_rate'];
        $interestType = $terms['interest_type'];
        $duration = max(1, (int) $terms['duration_months']);
        $frequency = $terms['frequency'];
        $fee = round((float) ($terms['processing_fee'] ?? 0), 2);
        $start = Carbon::parse($terms['start_date'])->startOfDay();

        $count = $this->installmentCount($duration, $frequency);
        $dueDates = $this->dueDates($start, $count, $frequency);

        $rows = match ($interestType) {
            Loan::INTEREST_FLAT => $this->flatSchedule($principal, $rate, $count, $dueDates, $fee),
            Loan::INTEREST_REDUCING => $this->reducingSchedule($principal, $rate, $count, $dueDates, $fee),
            default => throw new InvalidArgumentException("Unknown interest type [{$interestType}]."),
        };

        $totalInterest = round(array_sum(array_column($rows, 'interest_amount')), 2);
        $totalRepayment = round(array_sum(array_map(
            fn (array $row) => $row['principal_amount'] + $row['interest_amount'] + $row['fee_amount'],
            $rows
        )), 2);

        return [
            'installments' => $rows,
            'due_date' => end($dueDates)->toDateString(),
            'installment_count' => $count,
            'total_interest' => number_format($totalInterest, 2, '.', ''),
            'total_repayment' => number_format($totalRepayment, 2, '.', ''),
        ];
    }

    public function installmentCount(int $durationMonths, string $frequency): int
    {
        return match ($frequency) {
            Loan::FREQUENCY_MONTHLY => $durationMonths,
            Loan::FREQUENCY_BIWEEKLY => $durationMonths * 2,
            Loan::FREQUENCY_WEEKLY => $durationMonths * 4,
            default => throw new InvalidArgumentException("Unknown frequency [{$frequency}]."),
        };
    }

    /**
     * @return list<Carbon>
     */
    public function dueDates(CarbonInterface $start, int $count, string $frequency): array
    {
        $dates = [];
        $cursor = Carbon::parse($start)->startOfDay();

        for ($i = 1; $i <= $count; $i++) {
            $dates[] = match ($frequency) {
                Loan::FREQUENCY_MONTHLY => $cursor->copy()->addMonthsNoOverflow($i),
                Loan::FREQUENCY_BIWEEKLY => $cursor->copy()->addWeeks($i * 2),
                Loan::FREQUENCY_WEEKLY => $cursor->copy()->addWeeks($i),
                default => throw new InvalidArgumentException("Unknown frequency [{$frequency}]."),
            };
        }

        return $dates;
    }

    /**
     * @param  list<Carbon>  $dueDates
     * @return list<array<string, mixed>>
     */
    private function flatSchedule(float $principal, float $rate, int $count, array $dueDates, float $fee): array
    {
        $totalInterest = round($principal * ($rate / 100), 2);
        $principalParts = $this->splitAmount($principal, $count);
        $interestParts = $this->splitAmount($totalInterest, $count);

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $feeAmount = $i === 0 ? $fee : 0.0;
            $principalAmount = $principalParts[$i];
            $interestAmount = $interestParts[$i];
            $amountDue = round($principalAmount + $interestAmount + $feeAmount, 2);

            $rows[] = [
                'sequence' => $i + 1,
                'due_date' => $dueDates[$i]->toDateString(),
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'fee_amount' => $feeAmount,
                'penalty_amount' => 0.0,
                'amount_due' => $amountDue,
                'amount_paid' => 0.0,
                'status' => 'pending',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<Carbon>  $dueDates
     * @return list<array<string, mixed>>
     */
    private function reducingSchedule(float $principal, float $rate, int $count, array $dueDates, float $fee): array
    {
        $principalParts = $this->splitAmount($principal, $count);
        $remaining = $principal;
        $periodRate = ($rate / 100) / $count;

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $interestAmount = round($remaining * $periodRate, 2);
            $principalAmount = $principalParts[$i];
            $feeAmount = $i === 0 ? $fee : 0.0;
            $amountDue = round($principalAmount + $interestAmount + $feeAmount, 2);

            $rows[] = [
                'sequence' => $i + 1,
                'due_date' => $dueDates[$i]->toDateString(),
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'fee_amount' => $feeAmount,
                'penalty_amount' => 0.0,
                'amount_due' => $amountDue,
                'amount_paid' => 0.0,
                'status' => 'pending',
            ];

            $remaining = round($remaining - $principalAmount, 2);
        }

        // Ensure principal parts sum exactly; adjust last interest if needed is already handled by split.
        return $rows;
    }

    /**
     * @return list<float>
     */
    private function splitAmount(float $amount, int $count): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Installment count must be at least 1.');
        }

        $base = round($amount / $count, 2);
        $parts = array_fill(0, $count, $base);
        $allocated = round($base * $count, 2);
        $remainder = round($amount - $allocated, 2);
        $parts[$count - 1] = round($parts[$count - 1] + $remainder, 2);

        return $parts;
    }
}
