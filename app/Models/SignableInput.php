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
        'template_field_id',
        'type',
        'pos_x',
        'pos_y',
        'value',
        'settings',
        'label',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function documentPage(): BelongsTo
    {
        return $this->belongsTo(DocumentPage::class);
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }
}
