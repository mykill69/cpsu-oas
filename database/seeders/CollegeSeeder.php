<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\College;

class CollegeSeeder extends Seeder
{
    public function run()
    {
        $colleges = [
            [
                'name' => 'College of Agriculture and Forestry',
                'code' => 'CAF',
                'dean' => 'Dr. Juan Dela Cruz',
                'description' => 'Programs in agriculture, forestry, and environmental science',
                'is_extension' => false,
            ],
            [
                'name' => 'College of Arts and Sciences',
                'code' => 'CAS',
                'dean' => 'Dr. Maria Santos',
                'description' => 'Programs in natural sciences, social sciences, and humanities',
                'is_extension' => false,
            ],
            [
                'name' => 'College of Business and Management',
                'code' => 'CBM',
                'dean' => 'Dr. Pedro Reyes',
                'description' => 'Programs in business administration, entrepreneurship, and management',
                'is_extension' => false,
            ],
            [
                'name' => 'College of Education',
                'code' => 'COE',
                'dean' => 'Dr. Ana Gonzales',
                'description' => 'Programs in teacher education and professional development',
                'is_extension' => false,
            ],
            [
                'name' => 'College of Engineering and Technology',
                'code' => 'CET',
                'dean' => 'Engr. Roberto Villanueva',
                'description' => 'Programs in engineering and technology',
                'is_extension' => false,
            ],
            [
                'name' => 'CPSU Extension - Kabankalan',
                'code' => 'EXT-KAB',
                'dean' => 'Dr. Carmen Lopez',
                'description' => 'Extension campus in Kabankalan City',
                'is_extension' => true,
            ],
            [
                'name' => 'CPSU Extension - Sipalay',
                'code' => 'EXT-SIP',
                'dean' => 'Dr. Ricardo Torres',
                'description' => 'Extension campus in Sipalay City',
                'is_extension' => true,
            ],
        ];

        foreach ($colleges as $college) {
            College::create($college);
        }

        echo "Colleges seeded successfully!\n";
    }
}