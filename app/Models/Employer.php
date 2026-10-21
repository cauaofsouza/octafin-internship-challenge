<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    protected $fillable = ['corporate_name', 'cnpj', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
