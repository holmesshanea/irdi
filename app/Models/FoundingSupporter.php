<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoundingSupporter extends Model
{
    protected $fillable = ['display_name', 'tier', 'is_public', 'kickstarter_backer_id', 'recognition_consented_at'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'recognition_consented_at' => 'datetime'];
    }
}
