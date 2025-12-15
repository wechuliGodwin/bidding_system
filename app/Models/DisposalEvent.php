<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DisposalEvent extends Model
{
    protected $fillable = [
        'name',
        'description',
        'bid_type',
        'cut_off_price',
        'bid_increment',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'completed_at',
        'winners_notified',
        'winners_notified_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cut_off_price' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'closed_at' => 'datetime',
        'completed_at' => 'datetime',
        'winners_notified' => 'boolean',
        'winners_notified_at' => 'datetime',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function isActive()
    {
        return $this->status === 'published'
            && Carbon::now()->between($this->start_date, $this->end_date);
    }

    public function hasEnded()
    {
        return Carbon::now()->greaterThan($this->end_date);
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canPublish()
    {
        return $this->status === 'draft' && $this->assets()->count() > 0;
    }

    public function canClose()
    {
        return $this->status === 'published';
    }

    public function canComplete()
    {
        return $this->status === 'closed';
    }
}
