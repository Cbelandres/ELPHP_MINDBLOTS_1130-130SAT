<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_name',
        'investor_contact_no',
        'investor_budget_range',
        'investor_type',
    ];

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
} 