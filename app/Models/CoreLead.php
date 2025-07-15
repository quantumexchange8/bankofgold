<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreLead extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'date_added' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function import()
    {
        return $this->belongsTo(DataImport::class, 'import_id', 'id');
    }

    public function duplicateLinks()
    {
        return $this->hasMany(DuplicateLink::class, 'related_record_id', 'id')
                    ->where('related_table', 'core_leads');
    }

    public function duplicateRecords()
    {
        return $this->belongsToMany(DuplicateRecord::class, 'duplicate_links', 'related_record_id', 'duplicate_record_id')
                    ->wherePivot('related_table', 'core_leads');
    }
}
