<?php

return [
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere una stringa.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'min' => [
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'max' => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'size' => [
        'string' => 'Il campo :attribute deve contenere :size caratteri.',
    ],
    'digits' => 'Il campo :attribute deve contenere :digits cifre.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'between' => [
        'numeric' => 'Il campo :attribute deve essere compreso tra :min e :max.',
    ],
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'url' => 'Il campo :attribute deve essere un URL valido.',
    'image' => 'Il campo :attribute deve essere un’immagine.',
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'current_password' => 'La password non è corretta.',
    'unique' => 'Il valore di :attribute è già stato utilizzato.',
    'alpha_dash' => 'Il campo :attribute può contenere solo lettere, numeri, trattini e underscore.',
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'attributes' => [
        'name' => 'nome',
        'email' => 'indirizzo email',
        'password' => 'password',
        'current_password' => 'password attuale',
        'currentPassword' => 'password attuale',
        'profileName' => 'nome visualizzato',
        'username' => 'nome utente',
        'countryCode' => 'paese',
        'verificationCode' => 'codice di verifica',
        'respectForProperty' => 'rispetto della proprietà',
        'communicationCourtesy' => 'comunicazione e cortesia',
        'careOfProperty' => 'cura della proprietà',
        'wouldAllowReturn' => 'permesso di tornare',
        'subject' => 'oggetto',
        'message' => 'messaggio',
    ],
];
