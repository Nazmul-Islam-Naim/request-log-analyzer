<?php

namespace NIN\RequestLogAnalyzer\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use NIN\RequestLogAnalyzer\Models\UserLoginHistory;

class TrackUserLogin
{
    public function __construct(private readonly Request $request) {}

    /**
     * Handle the Login event — insert a new history row.
     */
    public function handleLogin(Login $event): void
    {
        try {
            UserLoginHistory::create([
                'user_id' => $event->user->getKey(),
                'ip_address' => $this->request->ip(),
                'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
                'login_at' => now(),
                'logout_at' => null,
            ]);
        } catch (\Throwable) {
            // Never let tracking crash the application.
        }
    }

    /**
     * Handle the Logout event — stamp logout_at on the latest open session.
     */
    public function handleLogout(Logout $event): void
    {
        try {
            UserLoginHistory::where('user_id', $event->user->getKey())
                ->whereNull('logout_at')
                ->latest('login_at')
                ->limit(1)
                ->update(['logout_at' => now()]);
        } catch (\Throwable) {
            // Never let tracking crash the application.
        }
    }
}
