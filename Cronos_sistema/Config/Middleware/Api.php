<?php

namespace Cronos_sistema\Config\Middleware;

class Api 
{
    public function handle($request, $next, $parms1)
    {
        $request->getRouter()->setContentType('application/json; charset=utf-8');
        return $next($request);
    }
}