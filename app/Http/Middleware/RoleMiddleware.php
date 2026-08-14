<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Allow only users with one of the supplied roles.
     *
     * Usage:
     * ->middleware(RoleMiddleware::class . ':admin')
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses untuk melakukan tindakan ini.');
        }

        return $next($request);
    }
}
