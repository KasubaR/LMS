<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForcePasswordUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.force-password');
    }

    public function update(ForcePasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ])->save();

        AuditLogger::log('Password Changed', $user, [], 'Users');

        return redirect()->route('dashboard');
    }
}
