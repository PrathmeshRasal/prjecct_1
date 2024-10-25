<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

require_once app_path('Helpers/Constants.php');

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        if(Auth::guard('sanctum')->user() && Auth::user()->user_type === USER_TYPE_ADMIN)
        {
            return $next($request);
        }
        return response()->json(['message' => 'Unauthorized: You are not an admin.'], 403);

    }
}
