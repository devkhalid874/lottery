<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        $general = gs();

        if($general->spa == Status::YES){
            return to_route('admin.dashboard');
        }

        return $next($request);
    }
}
