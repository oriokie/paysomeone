<?php

namespace App\Http\Controllers;

use App\Models\JobHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        try {
            DB::select('select 1 as ok');
        } catch (\Throwable) {
            $database = 'down';
        }
        $beat = null;
        try {
            $beat = JobHeartbeat::query()->find('alerts:deadlines');
        } catch (\Throwable) {
            $beat = null;
        }
        $schedule = 'never';
        if ($beat && $beat->ran_at) {
            $schedule = $beat->ran_at->gt(now()->subHours(2)) ? 'ok' : 'stale';
        }

        return response()->json([
            'app' => 'ok',
            'database' => $database,
            'schedule' => $schedule,
            'checked_at' => now()->toIso8601String(),
        ], $database === 'ok' ? 200 : 503);
    }
}
