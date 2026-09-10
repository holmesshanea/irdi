<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'size' => [
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
    ],
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña es incorrecta.',
    'unique' => 'El valor de :attribute ya está en uso.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'current_password' => 'contraseña actual',
        'currentPassword' => 'contraseña actual',
        'profileName' => 'nombre visible',
        'username' => 'nombre de usuario',
        'countryCode' => 'país',
        'verificationCode' => 'código de verificación',
        'respectForProperty' => 'respeto por la propiedad',
        'communicationCourtesy' => 'comunicación y cortesía',
        'careOfProperty' => 'cuidado de la propiedad',
        'wouldAllowReturn' => 'permiso para volver',
        'subject' => 'asunto',
        'message' => 'mensaje',
    ],
];
