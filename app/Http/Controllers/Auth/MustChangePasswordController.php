<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class MustChangePasswordController extends Controller
{
    /**
     * Show the forced first-login password change screen.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        // Already changed — don't keep them on this screen.
        if (! $request->user()->must_change_password) {
            return redirect()->route($request->user()->homeRoute());
        }

        return Inertia::render('Auth/ChangePassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Persist the new password and lift the change-on-first-login flag.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
                // Must actually differ from the admin-issued default credential.
                Rule::notIn(['ggaa@password']),
            ],
        ], [
            'password.not_in' => 'Please choose a password different from the default one.',
        ]);

        $request->user()->update([
            'password'             => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route($request->user()->homeRoute())
            ->with('status', 'Your password has been updated.');
    }
}
