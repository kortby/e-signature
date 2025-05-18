<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes if used in migration

    protected $fillable = [
        'user_id',
        'title',
        'original_filename',
        'storage_path',
        'status',
    ];

    /**
     * Get the user who owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all pages for this document.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class)->orderBy('page_number');
    }

    /**
     * Get all signable inputs across all pages of this document.
     */
    public function signableInputs(): HasManyThrough
    {
        // This is a shortcut if you often need all inputs for a document
        // without going through pages first.
        return $this->hasManyThrough(SignableInput::class, DocumentPage::class);
    }

    /**
     * The envelopes that this document belongs to.
     */
    public function envelopes(): BelongsToMany
    {
        return $this->belongsToMany(Envelope::class, 'document_envelope')
            ->withPivot('order')
            ->withTimestamps();
    }
}
