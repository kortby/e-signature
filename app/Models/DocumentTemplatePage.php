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

    /**
     * Get the document template this page belongs to.
     */
    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    /**
     * Get all template fields on this specific template page.
     * This assumes template_fields has a page_number that matches.
     * For a direct relationship, you could add document_template_page_id to template_fields.
     */
    public function templateFields(): HasMany
    {
        // This relationship assumes you query template_fields by document_template_id and page_number
        // To make it a direct Eloquent relationship, you would add 'document_template_page_id'
        // to the 'template_fields' table and change the foreign key here.
        // For now, this is a conceptual link.
        return $this->hasMany(TemplateField::class, 'document_template_id', 'document_template_id')
            ->where('page_number', $this->page_number);
    }
}
