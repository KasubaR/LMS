<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn ($user, string $ability) => $user->hasRole('Super Admin') ? true : null);

        // Add request context (IP / user agent / inferred browser & device) to every activity.
        Activity::saving(function (Activity $activity) {
            try {
                $activity->properties = AuditLogger::mergeIntoProperties($activity->properties);
            } catch (\Throwable) {
                // Don't break the main request if enrichment fails.
            }
        });

        $this->applyStoredSettings();
    }

    /**
     * Overlay admin-managed settings (Phase 14: System Settings) onto config,
     * so existing config('lms.*') / config('app.name') call sites pick up
     * DB-stored values without needing to know settings exist.
     */
    private function applyStoredSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        try {
            $settings = Setting::query()->find(1);
        } catch (\Throwable) {
            return;
        }

        if (! $settings) {
            return;
        }

        config([
            'app.name' => $settings->business_name ?: config('app.name'),
            'lms.loan_number_prefix' => $settings->loan_number_prefix,
            'lms.default_interest_rate' => (float) $settings->default_interest_rate,
            'lms.default_penalty_type' => $settings->default_penalty_type,
            'lms.default_penalty_value' => (float) $settings->default_penalty_value,
            'lms.grace_period_days' => $settings->grace_period_days,
            'lms.currency_code' => $settings->currency_code,
            'lms.currency_symbol' => $settings->currency_symbol,
        ]);
    }
}
