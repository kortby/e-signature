<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'original_pdf_storage_path',
    ];

    /**
     * The user who created this template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All fields defined for this template.
     */
    public function templateFields(): HasMany
    {
        return $this->hasMany(TemplateField::class);
    }

    /**
     * Documents that were instantiated from this template.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentTemplatePage::class)->orderBy('page_number');
    }
}
