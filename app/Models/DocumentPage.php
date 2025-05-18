<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'page_number',
        'image_path',
        'original_content_path',
    ];

    /**
     * Get the document this page belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get all signable inputs on this page.
     */
    public function signableInputs(): HasMany
    {
        return $this->hasMany(SignableInput::class);
    }
}

