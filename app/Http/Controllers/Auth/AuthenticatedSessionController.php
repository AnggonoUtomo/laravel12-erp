<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Modules\Console\LoginActivities\Services\LoginActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $this->rememberRedirect($request);

        return Inertia::render('console/auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        return $this->authenticate($request, route('dashboard', absolute: false));
    }

    private function authenticate(LoginRequest $request, string $defaultRedirect): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            app(LoginActivityService::class)->recordFailure(
                $request,
                User::query()->where('email', $request->string('email')->toString())->first(),
                collect($exception->errors())->flatten()->first() ?: 'Login gagal.',
            );

            throw $exception;
        }

        $request->session()->regenerate();
        app(LoginActivityService::class)->recordSuccess($request, $request->user());

        return redirect()->intended($defaultRedirect);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            app(LoginActivityService::class)->recordLogout($request, $request->user());
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function rememberRedirect(Request $request, ?string $defaultRedirect = null): void
    {
        $redirect = $request->string('redirect')->toString() ?: $defaultRedirect;

        if ($redirect && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            $request->session()->put('url.intended', $redirect);
        }
    }
}
