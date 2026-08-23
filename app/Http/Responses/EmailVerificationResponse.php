<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class EmailVerificationResponse implements VerifyEmailResponse
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse([
                'message' => 'Your email address has been verified successfully.',
            ])
            : redirect('/account')
                ->with(
                    'status',
                    'Your email address has been verified successfully.'
                );
    }
}
