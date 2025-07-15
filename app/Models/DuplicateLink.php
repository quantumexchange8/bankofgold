<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicateLink extends Model
{
    protected $guarded = [];

    public function duplicateRecord()
    {
        return $this->belongsTo(DuplicateRecord::class, 'duplicate_record_id', 'id');
    }

    public function lead()
    {
        if ($this->related_table === 'core_leads') {
            return $this->belongsTo(CoreLead::class, 'related_record_id', 'id');
        }

        return null;
    }
}
