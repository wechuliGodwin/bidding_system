<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Asset extends Model
{
    protected $fillable = [
        'disposal_event_id',
        'name',
        'description',
        'image',
        'starting_price',
        'current_highest_bid',
        'winner_bidder_id',
        'status',
        'payment_status',
        'payment_amount',
        'payment_completed_at',
        'handover_status',
        'handover_date',
        'handover_notes',
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'current_highest_bid' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'payment_completed_at' => 'datetime',
        'handover_date' => 'datetime',
    ];

    public function disposalEvent()
    {
        return $this->belongsTo(DisposalEvent::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function winner()
    {
        return $this->belongsTo(Bidder::class, 'winner_bidder_id');
    }

    public function getHighestBid()
    {
        return $this->bids()->where('status', 'valid')->orderBy('amount', 'desc')->first();
    }

    public function hasWinner()
    {
        return $this->winner_bidder_id !== null;
    }

    public function isPaymentCompleted()
    {
        return $this->payment_status === 'completed';
    }

    public function isHandedOver()
    {
        return $this->handover_status === 'completed';
    }
}
