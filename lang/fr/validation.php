<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'min' => [
        'string' => 'Le champ :attribute doit comporter au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'size' => [
        'string' => 'Le champ :attribute doit comporter :size caractères.',
    ],
    'digits' => 'Le champ :attribute doit comporter :digits chiffres.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
    ],
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'url' => 'Le champ :attribute doit être une URL valide.',
    'image' => 'Le champ :attribute doit être une image.',
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'unique' => 'La valeur de :attribute est déjà utilisée.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, chiffres, tirets et traits de soulignement.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'current_password' => 'mot de passe actuel',
        'currentPassword' => 'mot de passe actuel',
        'profileName' => 'nom affiché',
        'username' => 'nom d’utilisateur',
        'countryCode' => 'pays',
        'verificationCode' => 'code de vérification',
        'respectForProperty' => 'respect de la propriété',
        'communicationCourtesy' => 'communication et courtoisie',
        'careOfProperty' => 'soin de la propriété',
        'wouldAllowReturn' => 'autorisation de revenir',
        'subject' => 'objet',
        'message' => 'message',
    ],
];
