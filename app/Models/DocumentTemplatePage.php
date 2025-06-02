<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplatePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_template_id',
        'page_number',
        'image_path',
    ];

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    public function templateFields(): HasMany
    {
        return $this->hasMany(TemplateField::class, 'document_template_id', 'document_template_id')
                    ->where('page_number', $this->page_number);
    }
}
