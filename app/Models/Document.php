<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'bidder_id',
        'document_type',
        'file_path',
    ];

    /**
     * Get the bidder that owns the document.
     */
    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Bidder::class);
    }
}
