<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Project;
use App\Models\Investment;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AdminSeeder::class,
        ]);

        // Create sample users
        $farmer = User::create([
            'name' => 'John Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password'),
            'role' => 'farmer'
        ]);

        $investor = User::create([
            'name' => 'Jane Investor',
            'email' => 'investor@example.com',
            'password' => Hash::make('password'),
            'role' => 'investor'
        ]);

        // Create sample categories
        $categories = [
            ['category_name' => 'Agriculture', 'description' => 'Farming and agricultural projects'],
            ['category_name' => 'Technology', 'description' => 'Tech-related projects'],
            ['category_name' => 'Real Estate', 'description' => 'Property development projects'],
            ['category_name' => 'Renewable Energy', 'description' => 'Sustainable energy projects']
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create sample project
        $project = Project::create([
            'user_id' => $farmer->id,
            'category_id' => 1,
            'project_name' => 'Organic Farm Expansion',
            'project_description' => 'Expanding organic farming operations to meet growing demand for sustainable produce.',
            'project_capital' => 500000,
            'project_duration' => 12,
            'project_location' => 'Cavite',
            'project_benefits' => 'High ROI, Sustainable farming practices, Growing market demand',
            'project_risks' => 'Weather conditions, Market fluctuations, Production costs'
        ]);

        // Create sample investment
        Investment::create([
            'user_id' => $investor->id,
            'project_id' => $project->id,
            'amount' => 100000
        ]);
    }
} 