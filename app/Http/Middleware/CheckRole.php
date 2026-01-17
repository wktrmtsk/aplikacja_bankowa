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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Nieautoryzowany dostęp',
            ], 401);
        }

        // Sprawdź czy użytkownik ma którąkolwiek z wymaganych ról
        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Brak uprawnień do wykonania tej akcji',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
