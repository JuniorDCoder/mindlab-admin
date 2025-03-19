<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;

class CheckAuthToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for login page to allow errors to show
        // if ($request->routeIs('login')) {
        //     return $next($request);
        // }

        // // If no auth token, redirect to login
        // if (!Session::has('sessionToken')) {
        //     return redirect()->route('login')->withErrors(['auth' => 'Please log in first.']);
        // }

        return $next($request);
    }
}
