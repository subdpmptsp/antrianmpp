<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceOperatorSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user?->isOperator()) {
            return $next($request);
        }

        $now = now();
        $startedAt = $request->session()->get('operator_session_started_at');
        $sessionDate = $request->session()->get('operator_session_date');
        $maximumMinutes = config('attendance.operator_absolute_session_minutes', 720);

        if ($startedAt === null) {
            $request->session()->put([
                'operator_session_started_at' => $now->toIso8601String(),
                'operator_session_date' => $now->toDateString(),
            ]);

            return $next($request);
        }

        $expired = $sessionDate !== $now->toDateString()
            || Carbon::parse($startedAt)->diffInMinutes($now) >= $maximumMinutes;

        if (! $expired) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login')
            ->with('status', 'Sesi petugas telah berakhir. Silakan login kembali.');
    }
}
