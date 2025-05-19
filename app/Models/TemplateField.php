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
        // 'document_template_page_id', // Add this if you want a direct FK to DocumentTemplatePage
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
        // 'assigned_to_role',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_prefillable' => 'boolean',
        'is_readonly_after_prefill' => 'boolean',
    ];

    /**
     * The document template this field belongs to.
     */
    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }
}
