<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * @return array<string, string|null>
     */
    public static function requestContext(): array
    {
        if (app()->runningInConsole()) {
            return [
                'ip_address' => null,
                'user_agent' => null,
                'browser' => null,
                'device' => null,
            ];
        }

        $ip = request()?->ip();
        $userAgent = request()?->userAgent();

        if ($userAgent === null || $userAgent === '') {
            return [
                'ip_address' => $ip,
                'user_agent' => null,
                'browser' => null,
                'device' => null,
            ];
        }

        return [
            'ip_address' => $ip,
            'user_agent' => Str::limit($userAgent, 512),
            'browser' => self::detectBrowser($userAgent),
            'device' => self::detectDevice($userAgent),
        ];
    }

    private static function detectBrowser(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'edg/')) {
            return 'Edge';
        }

        if (str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/') && ! str_contains($ua, 'opr/')) {
            return 'Chrome';
        }

        if (str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/')) {
            return 'Safari';
        }

        if (str_contains($ua, 'firefox/')) {
            return 'Firefox';
        }

        if (str_contains($ua, 'opera/') || str_contains($ua, 'opr/')) {
            return 'Opera';
        }

        return null;
    }

    private static function detectDevice(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'iphone')) {
            return 'iPhone';
        }

        if (str_contains($ua, 'ipad')) {
            return 'iPad';
        }

        if (str_contains($ua, 'android')) {
            return str_contains($ua, 'mobile') ? 'Android Mobile' : 'Android Tablet';
        }

        if (str_contains($ua, 'windows')) {
            return 'Windows';
        }

        if (str_contains($ua, 'mac os x')) {
            return 'macOS';
        }

        if (str_contains($ua, 'linux')) {
            return 'Linux';
        }

        return null;
    }

    /**
     * Log a labeled audit entry into Spatie activity_log.
     *
     * @param  Model|null  $subject  Optional record the action applies to
     * @param  array<string, mixed>  $properties  Extra properties (merged with request context)
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $logName = null
    ): void {
        $context = self::requestContext();
        $merged = array_merge($context, $properties);

        $logger = activity()
            ->withProperties($merged)
            ->useLog($logName);

        if ($subject) {
            $logger = $logger->performedOn($subject);
        }

        $logger->log($action);
    }

    /**
     * Merge request context into an activity's properties, without overwriting non-null keys.
     *
     * @param  array<string, mixed>|Collection<int|string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    public static function mergeIntoProperties(mixed $existing): array
    {
        $existingArray = [];

        if ($existing instanceof Collection) {
            $existingArray = $existing->toArray();
        } elseif (is_array($existing)) {
            $existingArray = $existing;
        }

        $context = self::requestContext();

        foreach ($context as $key => $value) {
            if (! array_key_exists($key, $existingArray) || $existingArray[$key] === null) {
                $existingArray[$key] = $value;
            }
        }

        return $existingArray;
    }
}
