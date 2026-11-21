<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only Safaricom may reach the M-Pesa callback routes.
 *
 * 1. Secret key: the callback URL given to Safaricom carries ?key=<secret>
 *    (MPESA_CALLBACK_SECRET). Without the right key the request is rejected.
 * 2. Source IP: in production the caller must be in Safaricom's ranges
 *    (TRUSTED_PROXIES must be set when running behind a proxy / tunnel).
 */
class VerifySafaricomCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $production = config('mpesa_security.environment') === 'production';
        $secret     = (string) config('mpesa_security.callback_secret', '');

        if ($secret !== '') {
            $given = (string) $request->query('key', '');

            if ($given === '' || !hash_equals($secret, $given)) {
                Log::warning('M-Pesa callback rejected: missing or wrong secret key.', $this->context($request));

                return $this->deny();
            }
        } elseif ($production) {
            Log::critical('MPESA_CALLBACK_SECRET is not set. Customer payment callbacks rely on the IP check only.');
        }

        $ip      = (string) $request->ip();
        $allowed = (array) config('mpesa_security.allowed_ips', []);
        $ipOk    = $ip !== '' && $allowed && IpUtils::checkIp($ip, $allowed);

        if (!$ipOk) {
            $enforce = (bool) config('mpesa_security.enforce_ip') && config('mpesa_security.environment') !== 'sandbox';

            Log::warning('M-Pesa callback from outside the Safaricom ranges.', $this->context($request) + ['enforced' => $enforce]);

            if ($enforce) {
                return $this->deny();
            }
        }

        return $next($request);
    }

    private function deny(): Response
    {
        return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Forbidden'], 403);
    }

    private function context(Request $request): array
    {
        return ['ip' => $request->ip(), 'forwarded' => $request->header('X-Forwarded-For'), 'path' => $request->path()];
    }
}
