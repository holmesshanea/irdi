<?php

namespace App\Actions\Fortify;

use App\Models\BanIdentifier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $normalizedEmail = strtolower(trim($input['email'] ?? ''));
        $registrationIp = request()->ip();

        Validator::make(
            [
                ...$input,
                'email' => $normalizedEmail,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $isBanned = BanIdentifier::query()
                            ->where('type', 'email')
                            ->where('value', strtolower(trim((string) $value)))
                            ->exists();

                        if ($isBanned) {
                            $fail('This email address is not eligible to register for an IRDI account.');
                        }
                    },
                    Rule::unique(User::class),
                ],
                'password' => $this->passwordRules(),
            ]
        )->validate();

        if (
            $registrationIp !== null
            && $this->shouldCheckBanIp($registrationIp)
            && BanIdentifier::query()
                ->where('type', 'ip')
                ->where('value', $registrationIp)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => 'Registration is not available from this network. Please contact IRDI if you believe this is an error.',
            ]);
        }

        return User::create([
            'name' => $input['name'],
            'email' => $normalizedEmail,
            'registration_ip' => $registrationIp,
            'password' => Hash::make($input['password']),
        ]);
    }

    private function shouldCheckBanIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($ip === '::1' || $ip === '0.0.0.0') {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $firstOctet = (int) explode('.', $ip)[0];

            if ($firstOctet === 127) {
                return false;
            }
        }

        return true;
    }
}
