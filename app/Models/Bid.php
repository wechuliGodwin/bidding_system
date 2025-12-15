<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = [
        'asset_id',
        'bidder_id',
        'amount',
        'status',
        'bid_time',
        'nullification_reason',
        'nullified_by',
        'nullified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bid_time' => 'datetime',
        'nullified_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function bidder()
    {
        return $this->belongsTo(Bidder::class);
    }

    public function nullifiedBy()
    {
        return $this->belongsTo(User::class, 'nullified_by');
    }

    public function isWinner()
    {
        return $this->status === 'winner';
    }

    public function isValid()
    {
        return $this->status === 'valid';
    }

    public function isNullified()
    {
        return $this->status === 'nullified';
    }
}
