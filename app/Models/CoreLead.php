<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreLead extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['date_added', 'deleted_at'];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'duplicate_ids' => 'array',
    ];
    
}
