<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInboundEmailSignature
{
    /**
     * Verify the mail provider's HMAC-SHA256 signature over the RAW request
     * body before the controller trusts any of its contents. Fails closed:
     * an unconfigured secret rejects every request rather than letting them
     * through unverified.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.inbound_email.secret');

        if (empty($secret)) {
            abort(403);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $provided = (string) $request->header('X-Webhook-Signature', '');

        if (! hash_equals($expected, $provided)) {
            abort(403);
        }

        return $next($request);
    }
}
