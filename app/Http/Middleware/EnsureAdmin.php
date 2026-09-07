<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Garante que apenas usuários com papel de administrador acessem a rota.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado. Apenas administradores podem executar esta ação.',
                ], 403);
            }

            return redirect()->route('portal.dashboard')
                ->with('error', 'Acesso restrito ao painel administrativo.');
        }

        return $next($request);
    }
}
