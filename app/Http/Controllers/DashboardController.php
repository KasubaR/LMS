<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\DashboardMetrics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardMetrics $metrics,
    ) {}

    public function index(): View
    {
        return view('dashboard', [
            'cards' => $this->metrics->cards(),
            'charts' => [
                'monthly_lending' => $this->metrics->monthlyLending(),
                'monthly_collections' => $this->metrics->monthlyCollections(),
                'loan_status' => $this->metrics->loanStatusBreakdown(),
                'top_borrowers' => $this->metrics->topBorrowers(),
                'loan_growth' => $this->metrics->loanGrowth(),
            ],
            'recentActivity' => $this->recentActivity(),
            'overdueLoans' => $this->overdueLoans(),
            'paymentsDueToday' => $this->paymentsDueToday(),
        ]);
    }

    /**
     * @return Collection<int, array{text: string, time: Carbon|null}>
     */
    private function recentActivity()
    {
        return Activity::query()
            ->with('causer')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity) => [
                'text' => trim(sprintf(
                    '%s %s %s',
                    $activity->causer?->name ?? 'System',
                    $activity->description,
                    Str::headline(class_basename($activity->subject_type ?? 'record'))
                )),
                'time' => $activity->created_at,
            ]);
    }

    /**
     * @return Collection<int, Loan>
     */
    private function overdueLoans()
    {
        $today = today();

        return Loan::query()
            ->with('customer')
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->whereHas('installments', function ($query) use ($today) {
                $query->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED])
                    ->where('due_date', '<', $today);
            })
            ->with(['installments' => fn ($query) => $query->orderBy('sequence')])
            ->orderBy('next_due_date')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, LoanInstallment>
     */
    private function paymentsDueToday()
    {
        return LoanInstallment::query()
            ->with('loan.customer')
            ->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED])
            ->whereDate('due_date', today())
            ->get();
    }
}
