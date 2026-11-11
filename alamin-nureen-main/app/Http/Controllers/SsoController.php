<?php

namespace App\Http\Controllers;

use App\Models\StaffEdu;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class SsoController extends Controller
{
    public function portal(): RedirectResponse
    {
        return $this->redirectToPortal();
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->failure($request);
        }

        try {
            $payload = $this->redeem($code);
            $staff = $this->resolveStaff($payload);

            if (! $staff) {
                return $this->failure($request);
            }

            $this->closeCurrentLoginSession();
            $loginSessionId = $this->createLoginSession($staff);
            $this->startLocalSession($request, $staff, $loginSessionId);
        } catch (ConnectionException) {
            return $this->failure($request);
        } catch (Throwable) {
            // Never log the opaque SSO code, client secret, or identity payload.
            return $this->failure($request);
        }

        return redirect()->route('dashboard');
    }

    private function redeem(string $code): array
    {
        $url = (string) config('alamin_login.redeem_url', '');
        $clientId = (string) config('alamin_login.client_id', '');
        $clientSecret = (string) config('alamin_login.client_secret', '');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array($scheme, ['http', 'https'], true)
            || ((bool) config('alamin_login.require_https', true) && $scheme !== 'https')
            || $clientId === ''
            || $clientSecret === ''
        ) {
            throw new \RuntimeException('Al Amin Login is not configured correctly.');
        }

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout((int) config('alamin_login.connect_timeout', 3))
            ->timeout((int) config('alamin_login.timeout', 8))
            ->withHeaders([
                'X-Alamin-SSO-Client' => $clientId,
                'X-Alamin-SSO-Secret' => $clientSecret,
            ])
            ->post($url, ['code' => $code]);

        if (! $response->successful()) {
            throw new \RuntimeException('The SSO handoff could not be redeemed.');
        }

        $payload = $response->json();
        if (! is_array($payload)
            || ! hash_equals($clientId, (string) ($payload['target'] ?? ''))
            || ! is_array($payload['identity'] ?? null)
        ) {
            throw new \RuntimeException('The SSO response is invalid.');
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            throw new \RuntimeException('The SSO handoff has expired.');
        }

        return $payload;
    }

    private function resolveStaff(array $payload): ?StaffEdu
    {
        $identity = $payload['identity'];
        if (($identity['role'] ?? null) !== 'staff_edu'
            || ($identity['source'] ?? null) !== 'staff_edu'
        ) {
            return null;
        }

        $staffId = trim((string) ($identity['id'] ?? ''));
        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        if ($staffId === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        /** @var StaffEdu|null $staff */
        $staff = StaffEdu::query()->whereKey($staffId)->first();
        if (! $staff
            || strtolower(trim((string) ($staff->email ?? ''))) !== $email
            || strtolower(trim((string) ($staff->status ?? ''))) !== 'active'
        ) {
            return null;
        }

        $role = strtolower(str_replace(['-', ' '], '_', trim((string) ($staff->role ?? 'staff_edu'))));

        return in_array($role, ['staff_edu', 'admin'], true) ? $staff : null;
    }

    private function createLoginSession(StaffEdu $staff): int
    {
        return DB::transaction(function () use ($staff): int {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('SET @current_staff_id = ?, @current_staff_name = ?', [
                    (string) $staff->staffID,
                    (string) $staff->staffName,
                ]);
            }

            $id = (int) DB::table('login_session')->insertGetId([
                'staffID' => (string) $staff->staffID,
                'loginTime' => now(),
                'sessionStatus' => 'active',
            ], 'sessionID');

            if ($id < 1) {
                throw new \RuntimeException('The TrainHub login session was not created.');
            }

            return $id;
        });
    }

    private function startLocalSession(Request $request, StaffEdu $staff, int $loginSessionId): void
    {
        $session = [
            'staffID' => (string) $staff->staffID,
            'staffName' => (string) $staff->staffName,
            'staff_id' => (string) $staff->staffID,
            'staff_name' => (string) $staff->staffName,
            'staff_email' => strtolower(trim((string) $staff->email)),
            'role' => (string) ($staff->role ?? 'STAFF_EDU'),
            'loginSessionID' => $loginSessionId,
            'password_change_required' => (int) ($staff->password_changed_required ?? 0),
            'auth_source' => 'sso',
        ];

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put($session);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION = $session;
    }

    private function failure(Request $request): RedirectResponse
    {
        $this->closeCurrentLoginSession();
        $this->destroyLocalSession($request);

        return $this->redirectToPortal();
    }

    private function closeCurrentLoginSession(): void
    {
        $staffId = (string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? '');
        $sessionId = (int) ($_SESSION['loginSessionID'] ?? 0);
        if ($staffId === '') {
            return;
        }

        try {
            $query = DB::table('login_session')
                ->where('staffID', $staffId)
                ->where('sessionStatus', 'active');

            if ($sessionId > 0) {
                $query->where('sessionID', $sessionId);
            } else {
                $latest = $query->orderByDesc('loginTime')->orderByDesc('sessionID')->value('sessionID');
                if ($latest === null) {
                    return;
                }
                $query = DB::table('login_session')->where('sessionID', $latest);
            }

            $query->update(['logoutTime' => now(), 'sessionStatus' => 'ended']);
        } catch (Throwable) {
            // The browser session must still be cleared when the audit update fails.
        }
    }

    private function destroyLocalSession(Request $request): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $_SESSION = [];
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
    }

    private function redirectToPortal(): RedirectResponse
    {
        $portal = (string) config('alamin_login.url', '');
        $scheme = strtolower((string) parse_url($portal, PHP_URL_SCHEME));
        if (! filter_var($portal, FILTER_VALIDATE_URL)
            || ! in_array($scheme, ['http', 'https'], true)
            || ((bool) config('alamin_login.require_https', true) && $scheme !== 'https')
        ) {
            abort(503, 'Al Amin Login is not configured for TrainHub.');
        }

        return redirect()->away($portal);
    }
}
