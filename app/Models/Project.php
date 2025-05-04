<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'capital_needed',
        'duration_months',
        'benefits',
        'risks',
        'farmer_id'
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
} 