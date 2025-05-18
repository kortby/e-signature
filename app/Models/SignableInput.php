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
        // 'assigned_signer_id',
        'type',
        'pos_x',
        'pos_y',
        'value',
        'settings',
        'label',
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

    // Optional: Relationship to an assigned signer (User model)
    // public function assignedSigner(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'assigned_signer_id');
    // }
}
