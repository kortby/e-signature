<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_template_id',
        'page_number',
        'type',
        'key_name',
        'label',
        'pos_x',
        'pos_y',
        'settings',
        'default_value',
        'is_prefillable',
        'is_readonly_after_prefill',
        'data_source_mapping', // New
    ];

    protected $casts = [
        'settings' => 'array',
        'is_prefillable' => 'boolean',
        'is_readonly_after_prefill' => 'boolean',
    ];

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }
}
