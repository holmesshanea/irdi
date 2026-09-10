<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

class PasswordResetResponse implements PasswordResetResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => __('Your password has been reset successfully.')])
            : redirect()
                ->route('login')
                ->with('status', __('Your password has been reset successfully. You may now log in.'));
    }
}
