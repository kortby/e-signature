<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignableInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_page_id',
        'template_field_id', // New: Link to the original template field if this input came from a template
        'type',
        'pos_x',
        'pos_y',
        'value',    // This will store the actual pre-filled or user-entered data
        'settings',
        'label',    // This might be copied from TemplateField's label or key_name
    ];

    protected $casts = [
        'settings' => 'array', // Automatically cast JSON 'settings' to an array and vice-versa
    ];

    /**
     * Get the document page this input belongs to.
     */
    public function documentPage(): BelongsTo
    {
        return $this->belongsTo(DocumentPage::class);
    }

    /**
     * The template field this input was derived from (if any).
     */
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }

    // Optional: Relationship to an assigned signer (User model)
    // public function assignedSigner(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'assigned_signer_id');
    // }
}
