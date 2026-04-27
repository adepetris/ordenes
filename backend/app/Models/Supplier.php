<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'tax_id',
        'name',
        'email',
        'phone',
        'status',
    ];
}
