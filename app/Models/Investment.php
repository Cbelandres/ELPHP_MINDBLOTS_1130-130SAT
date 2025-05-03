<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'investor_id',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }
} 