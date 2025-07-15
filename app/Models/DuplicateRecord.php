<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuplicateRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function duplicateLinks()
    {
        return $this->hasMany(DuplicateLink::class, 'duplicate_record_id', 'id');
    }
}
