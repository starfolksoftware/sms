<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Supports delimited role lists using "|" or "," (any-of semantics), e.g.:
     *   role:admin|manager  or  role:admin,manager
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        $roleList = collect(preg_split('/[|,]/', $roles, -1, PREG_SPLIT_NO_EMPTY))
            ->map(static fn (string $r): string => trim($r))
            ->filter();

        if ($roleList->isEmpty()) {
            abort(403, 'No role(s) specified.');
        }

        $hasAny = $roleList->some(fn (string $role): bool => $request->user()->hasRole($role));

        if (! $hasAny) {
            abort(403, 'You do not have the required role(s).');
        }

        return $next($request);
    }
}
