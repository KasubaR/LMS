<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const SUGGEST_LIMIT = 5;

    private const RESULTS_LIMIT = 25;

    public function index(Request $request): View
    {
        $term = trim($request->string('q')->toString());
        $user = $request->user();

        $customers = collect();
        $loans = collect();

        if ($term !== '') {
            if ($user->can('view customers')) {
                $customers = Customer::query()
                    ->search($term)
                    ->orderBy('name')
                    ->limit(self::RESULTS_LIMIT)
                    ->get();
            }

            if ($user->can('view loans')) {
                $loans = Loan::query()
                    ->with('customer')
                    ->search($term)
                    ->latest()
                    ->limit(self::RESULTS_LIMIT)
                    ->get();
            }
        }

        return view('search.index', [
            'q' => $term,
            'customers' => $customers,
            'loans' => $loans,
            'canViewCustomers' => $user->can('view customers'),
            'canViewLoans' => $user->can('view loans'),
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $user = $request->user();

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['customers' => [], 'loans' => []]);
        }

        $customers = [];
        $loans = [];

        if ($user->can('view customers')) {
            $customers = Customer::query()
                ->search($term)
                ->orderBy('name')
                ->limit(self::SUGGEST_LIMIT)
                ->get()
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'type' => 'customer',
                    'label' => $customer->name,
                    'meta' => trim(implode(' · ', array_filter([
                        $customer->customer_number,
                        $customer->phone,
                        $customer->nrc,
                    ]))),
                    'url' => route('customers.show', $customer),
                ])
                ->values()
                ->all();
        }

        if ($user->can('view loans')) {
            $loans = Loan::query()
                ->with('customer')
                ->search($term)
                ->latest()
                ->limit(self::SUGGEST_LIMIT)
                ->get()
                ->map(fn (Loan $loan) => [
                    'id' => $loan->id,
                    'type' => 'loan',
                    'label' => $loan->loan_number,
                    'meta' => trim(implode(' · ', array_filter([
                        $loan->customer?->name,
                        $loan->customer?->phone,
                        ucfirst(str_replace('_', ' ', $loan->status)),
                    ]))),
                    'url' => route('loans.show', $loan),
                ])
                ->values()
                ->all();
        }

        return response()->json([
            'customers' => $customers,
            'loans' => $loans,
        ]);
    }
}
