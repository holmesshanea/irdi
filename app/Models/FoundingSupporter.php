<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoundingSupporter extends Model
{
    protected $fillable = ['display_name', 'tier', 'is_public', 'kickstarter_backer_id'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
