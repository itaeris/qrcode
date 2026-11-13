<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Models\QrShare;
use App\Models\User;
use App\QrShareRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QrShareController extends Controller
{
    public function store(Request $request, string $code): JsonResponse
    {
        $link = $this->owned($code);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(QrShareRole::class)],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'role.required' => 'Choose a role.',
        ]);

        $email = mb_strtolower(trim($data['email']));
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account uses this email.',
            ]);
        }

        if ($user->id === $link->user_id) {
            throw ValidationException::withMessages([
                'email' => 'This project already belongs to that account.',
            ]);
        }

        QrShare::query()->updateOrCreate(
            ['qr_link_id' => $link->id, 'user_id' => $user->id],
            ['role' => $data['role']],
        );

        return response()->json(['shares' => $link->shareList()]);
    }

    public function update(Request $request, string $code, int $member): JsonResponse
    {
        $link = $this->owned($code);
        $share = $link->shares()->where('user_id', $member)->firstOrFail();
        $data = $request->validate([
            'role' => ['required', Rule::enum(QrShareRole::class)],
        ], [
            'role.required' => 'Choose a role.',
        ]);

        $share->update(['role' => $data['role']]);

        return response()->json(['shares' => $link->shareList()]);
    }

    public function destroy(string $code, int $member): JsonResponse
    {
        $link = $this->owned($code);
        $link->shares()->where('user_id', $member)->firstOrFail()->delete();

        return response()->json(['shares' => $link->shareList()]);
    }

    private function owned(string $code): QrLink
    {
        return QrLink::query()->forUser((int) auth()->id())->where('code', $code)->firstOrFail();
    }
}
