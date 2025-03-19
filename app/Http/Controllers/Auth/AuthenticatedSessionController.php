<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Parse\ParseUser;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\ParseService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{

    protected $parseService;

    public function __construct(ParseService $parseService)
    {
        $this->parseService = $parseService;
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */

     public function store(LoginRequest $request): RedirectResponse
     {
        // Authenticate using ParseService
        $response = $this->parseService->loginUser($request->email, $request->password);

        if (isset($response['error'])) {
             return back()->withErrors(['email' => $response['message'] ?? 'Invalid credentials.']);
        }

         // Retrieve the user object to get the role
         $parseUser = ParseUser::getCurrentUser();
         $role = $parseUser->get("role");

         // Check if the user is an admin
         if ($role !== 'admin') {
             return back()->withErrors(['email' => 'Access denied. Admins only.']);
         }

         // Store session token and Parse user
         session(['sessionToken' => $response, 'user' => $parseUser]);

         // Create a Laravel-compatible user from the Parse user
         $user = new \App\Models\ParseUserAdapter($parseUser);

         // Log in the user using Laravel's Auth facade
         Auth::login($user);

         // Debugging - check if the user is authenticated
         if (Auth::check()) {
             // This is good - the user is authenticated!
             return redirect()->intended(route('dashboard'));
         } else {
             // Something's wrong - log for debugging
             \Log::debug('User not authenticated after login');
             return back()->withErrors(['email' => 'Authentication failed.']);
         }
     }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}