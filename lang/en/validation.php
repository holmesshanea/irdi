<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'size' => [
        'string' => 'The :attribute must be :size characters.',
    ],
    'digits' => 'The :attribute must be :digits digits.',
    'integer' => 'The :attribute must be an integer.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
    ],
    'in' => 'The selected :attribute is invalid.',
    'url' => 'The :attribute must be a valid URL.',
    'image' => 'The :attribute must be an image.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'unique' => 'The :attribute has already been taken.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes, and underscores.',
    'boolean' => 'The :attribute field must be true or false.',
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
        'current_password' => 'current password',
        'currentPassword' => 'current password',
        'profileName' => 'display name',
        'username' => 'username',
        'countryCode' => 'country',
        'verificationCode' => 'verification code',
        'respectForProperty' => 'respect for property',
        'communicationCourtesy' => 'communication and courtesy',
        'careOfProperty' => 'care of the property',
        'wouldAllowReturn' => 'return permission',
        'subject' => 'subject',
        'message' => 'message',
    ],
];
