<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('garageon.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->string('email')->toString();

        $status = User::where('email', $email)->where('is_platform_admin', true)->exists()
            ? Password::sendResetLink(['email' => $email])
            : Password::RESET_LINK_SENT;

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Se esse e-mail tiver acesso, enviaremos um link para redefinir sua senha.')
            : back()->withErrors(['email' => 'Não consegui enviar o link agora. Tente novamente em instantes.']);
    }
}
