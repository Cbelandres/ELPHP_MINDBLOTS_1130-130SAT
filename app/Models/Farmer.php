<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_fname',
        'farmer_lname',
        'farmer_contact',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
} 