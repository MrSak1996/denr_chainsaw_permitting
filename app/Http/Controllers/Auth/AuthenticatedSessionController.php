<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();

    //     $request->session()->regenerate();

    //     return redirect()->intended(route('dashboard', absolute: false));
    // }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        switch ($user->role_id) {
            case 1:
                return redirect('/dashboard');
            case 6:
                return redirect('/ardts-dashboard');
            case 8:
                return redirect('/rps-chief-dashboard');
                
            case 10:
                return redirect('/tsd-chief-dashboard');

            case 4:
                return redirect('/fus-dashboard');

            case 3:
                return redirect('/penro-dashboard');

            //    case 2: 
                // return redirect('/cenro-chief-dashboard');
            // case 3: 
            //     return redirect('/penro-dashboard');

            // case 5: 
            //     return redirect('/rps-chief-dashboard');

            // case 3: 
            //     return redirect('/regional-executive-dashboard');
            
            case 7:
                return redirect('/regional-executive-dashboard');

       

            default:
                return redirect('/dashboard');
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
