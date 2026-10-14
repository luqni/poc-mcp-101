<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_source_id',
        'whitelist_tables',
        'masked_columns',
        'max_query_limit',
        'is_active',
    ];

    protected $casts = [
        'whitelist_tables' => 'array',
        'masked_columns' => 'array',
        'max_query_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function databaseSource(): BelongsTo
    {
        return $this->belongsTo(DatabaseSource::class);
    }
}
