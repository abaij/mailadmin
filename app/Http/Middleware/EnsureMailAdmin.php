<?php

namespace App\Http\Middleware;

use App\Models\Vmail\Mailbox;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the admin area to accounts iRedMail marks as global or per-domain
 * administrators. Everyone else is a plain mail user.
 */
class EnsureMailAdmin
{
    public function __invoke(Request $request, Closure $next): Response
    {
        return $this->handle($request, $next);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof Mailbox && $user->isDomainAdmin(), 403, 'This account does not administer any domain.');

        return $next($request);
    }
}
