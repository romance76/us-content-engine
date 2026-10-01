<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks the progress of an on-demand article batch across requests —
 * the admin button that kicks it off and the ingest API calls that the AI
 * pipeline makes as it publishes each article are entirely separate HTTP
 * requests (different processes, maybe minutes apart), so progress has to
 * live somewhere both can reach: the cache, not request/session state.
 */
class GenerationStatus
{
    private const KEY = 'generation.status';

    // The hourly processing routine only checks once an hour (the
    // platform's minimum schedule interval), so a request made right
    // after one check-in can sit for close to 60 minutes before the next
    // one picks it up. This must stay comfortably longer than that, or a
    // click near the start of the hour silently expires before anything
    // ever processes it.
    private const TTL_MINUTES = 90;

    public static function start(int $target, string $requestedBy): void
    {
        Cache::put(self::KEY, [
            'status' => 'running',
            'target' => $target,
            'completed' => 0,
            'requested_by' => $requestedBy,
            'started_at' => now()->toIso8601String(),
            'message' => null,
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    public static function increment(): void
    {
        $state = Cache::get(self::KEY);

        if (! $state || $state['status'] !== 'running') {
            return;
        }

        $state['completed']++;
        Cache::put(self::KEY, $state, now()->addMinutes(self::TTL_MINUTES));
    }

    public static function complete(string $message): void
    {
        $state = Cache::get(self::KEY);

        if (! $state) {
            return;
        }

        $state['status'] = 'done';
        $state['message'] = $message;
        // Kept briefly so the UI has time to show the final message before it clears.
        Cache::put(self::KEY, $state, now()->addMinutes(2));
    }

    public static function isRunning(): bool
    {
        $state = Cache::get(self::KEY);

        return $state && $state['status'] === 'running';
    }

    public static function current(): ?array
    {
        return Cache::get(self::KEY);
    }
}
