<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Models\CanonicalSsoUser;
use App\Models\Notification;
use App\Models\Teacher;
use App\Models\Training;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->getRoleNames()->first() ?? 'outsider',
                    'session_only' => $user instanceof CanonicalSsoUser,
                ] : null,
            ],
            'notifications' => [
                'unread_count' => $user
                    ? Notification::forRecipient($user)
                        ->where('is_read', false)
                        ->count()
                    : 0,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'site_stats' => ! $user ? [
                'teachers'    => Teacher::count(),
                'trainings'   => Training::count(),
                'ladap_hours' => (int) round(Attendance::sum('hours_ladap')),
            ] : null,
        ];
    }
}
