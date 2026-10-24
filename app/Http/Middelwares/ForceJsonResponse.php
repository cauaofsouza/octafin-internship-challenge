<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Força o formato JSON em todas as rotas da API, para que erros de validação (422),
 * limite excedido (429) e rotas inexistentes (404) nunca virem redirecionamentos HTML.
 */
class ForceJsonResponse
{
    /**
     * @param Request $request Requisição recebida.
     * @param Closure $next    Próximo middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}