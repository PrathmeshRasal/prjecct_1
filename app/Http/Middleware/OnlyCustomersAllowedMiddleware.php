<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;

class OnlyCustomersAllowedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check())
        {
            if(Auth::user()->user_type == 1)
            {
                return $next($request);
            }
        }

        $jsonResponse = [
            'status'    =>  false,
            'message'   =>  'Access denied!',
            'data'  =>  [
                'message'   =>  'Only customers can perform this action!'
            ]
        ];

        return response()->json($jsonResponse);
    }
}
