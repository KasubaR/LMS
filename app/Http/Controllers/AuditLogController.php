<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoginHistory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        $module = $request->string('module')->toString();
        $userId = $request->integer('user_id');
        $action = $request->string('action')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $q = $request->string('q')->toString();

        $activitiesQuery = Activity::query()
            ->with(['causer', 'subject'])
            ->latest()
            ->when($userId, function ($query) use ($userId) {
                $query->where('causer_id', $userId);
            })
            ->when($module !== '', function ($query) use ($module) {
                $query->where('log_name', $module);
            })
            ->when($action !== '', function ($query) use ($action) {
                $query->where('description', 'like', "%{$action}%");
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($searchQuery) use ($q) {
                    $searchQuery
                        ->where('description', 'like', "%{$q}%")
                        ->orWhere('subject_type', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom !== '' && $dateTo !== '', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->when($dateFrom !== '' && $dateTo === '', function ($query) use ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->when($dateFrom === '' && $dateTo !== '', function ($query) use ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            });

        $modules = ['Customers', 'Loans', 'Payments', 'Users', 'Roles', 'Auth'];

        $activities = $activitiesQuery
            ->paginate(20)
            ->withQueryString()
            ->through(function (Activity $activity) {
                return $this->mapActivityRow($activity);
            });

        return view('audit-logs.index', [
            'activities' => $activities,
            'modules' => $modules,
            'users' => $users,
            'filters' => [
                'module' => $module,
                'user_id' => $userId,
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'q' => $q,
            ],
        ]);
    }

    public function loginHistory(Request $request): View
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        $userId = $request->integer('user_id');
        $email = $request->string('email')->toString();
        $failedOnly = $request->boolean('failed_only');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $historiesQuery = LoginHistory::query()
            ->with('user')
            ->latest('login_at')
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($email !== '', function ($query) use ($email) {
                $query->where('email', 'like', "%{$email}%");
            })
            ->when($failedOnly, function ($query) {
                $query->where('failed', true);
            })
            ->when($dateFrom !== '' && $dateTo !== '', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('login_at', [$dateFrom, $dateTo]);
            })
            ->when($dateFrom !== '' && $dateTo === '', function ($query) use ($dateFrom) {
                $query->where('login_at', '>=', $dateFrom);
            })
            ->when($dateFrom === '' && $dateTo !== '', function ($query) use ($dateTo) {
                $query->where('login_at', '<=', $dateTo);
            });

        $histories = $historiesQuery
            ->paginate(20)
            ->withQueryString();

        return view('audit-logs.login-history', [
            'histories' => $histories,
            'users' => $users,
            'filters' => [
                'user_id' => $userId,
                'email' => $email,
                'failed_only' => $failedOnly,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapActivityRow(Activity $activity): array
    {
        $causerName = $activity->causer?->name ?? 'System';
        $module = $activity->log_name ?? class_basename($activity->subject_type ?? 'Record');
        $action = $activity->description;

        $ip = $activity->getProperty('ip_address');
        $browser = $activity->getProperty('browser');
        $device = $activity->getProperty('device');

        $subject = $activity->subject;

        $recordLabel = '—';
        $recordUrl = null;
        if ($subject instanceof Customer) {
            $recordLabel = (string) ($subject->name ?: $subject->customer_number);
            $recordUrl = route('customers.show', $subject);
        } elseif ($subject instanceof Loan) {
            $recordLabel = (string) ($subject->loan_number ?: $subject->id);
            $recordUrl = route('loans.show', $subject);
        } elseif ($subject instanceof Payment) {
            $recordLabel = (string) ($subject->payment_number ?: $subject->id);
            $recordUrl = route('payments.show', $subject);
        } elseif ($subject instanceof User) {
            $recordLabel = (string) ($subject->name ?: $subject->email);
            $recordUrl = route('admin.users.edit', $subject);
        } elseif ($subject) {
            $recordLabel = (string) ($subject->id ?? '—');
        }

        $changes = $activity->attribute_changes?->toArray() ?? [];

        // Spatie stores changes as two flat maps ('attributes' = new values,
        // 'old' = previous values), not nested per-attribute — build per-field
        // old/new pairs from the union of both maps' keys.
        $newValues = $changes['attributes'] ?? [];
        $oldValues = $changes['old'] ?? [];

        $diffs = [];
        $prevParts = [];
        $newParts = [];

        foreach (array_unique([...array_keys($oldValues), ...array_keys($newValues)]) as $attribute) {
            $old = $this->formatChangeValue($oldValues[$attribute] ?? null);
            $new = $this->formatChangeValue($newValues[$attribute] ?? null);

            if ($old === $new) {
                continue;
            }

            $diffs[] = [
                'attribute' => (string) $attribute,
                'old' => $old,
                'new' => $new,
            ];

            $prevParts[] = sprintf('%s: %s', $attribute, $old === null || $old === '' ? '—' : $old);
            $newParts[] = sprintf('%s: %s', $attribute, $new === null || $new === '' ? '—' : $new);
        }

        return [
            'id' => $activity->id,
            'causer_name' => $causerName,
            'action' => $action,
            'module' => $module,
            'record_label' => $recordLabel,
            'record_url' => $recordUrl,
            'previous' => implode('; ', $prevParts),
            'new' => implode('; ', $newParts),
            'diffs' => $diffs,
            'ip_address' => $ip,
            'browser' => $browser,
            'device' => $device,
            'created_at' => $activity->created_at,
        ];
    }

    private function formatChangeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof \JsonSerializable) {
            $encoded = $value->jsonSerialize();
            if (is_scalar($encoded)) {
                return (string) $encoded;
            }

            $json = json_encode($encoded, JSON_UNESCAPED_UNICODE);

            return $json !== false ? $json : (string) $value;
        }

        // attribute_changes can contain nested arrays/objects; stringify safely.
        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: (string) $value;
    }
}
