<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Envelope extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'status',
    ];

    /**
     * Get the user who created the envelope.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The documents that belong to this envelope.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_envelope')
            ->withPivot('order') // To retrieve the order of documents
            ->orderBy('pivot_order', 'asc') // Order documents by the 'order' in pivot
            ->withTimestamps();
    }
}
