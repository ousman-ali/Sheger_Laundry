<?php

namespace App\Http\Controllers\Auth;

 use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use function redirect;
use function route;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('Admin')) {
            return \redirect()->intended(\route('dashboard'));
        }

        if ($user->hasRole('Manager')) {
            return \redirect()->intended(\route('manager.index'));
        }

        if ($user->hasRole('Receptionist')) {
            return \redirect()->intended(\route('reception.index'));
        }

        if ($user->hasRole('Operator')) {
            return \redirect()->intended(\route('operator.my'));
        }

        return \redirect()->intended(\route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return \redirect('/');
    }
}
