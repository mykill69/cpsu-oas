<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SurveyVisit;
use App\Models\Area;
use App\Models\Parameter;
use App\Models\College;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (use firstOrCreate to avoid duplicates)
        $permissions = [
            'manage survey visits',
            'manage areas',
            'manage parameters',
            'manage colleges',
            'manage programs',
            'view reports',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles (use firstOrCreate to avoid duplicates)
        $qaDirector = Role::firstOrCreate(['name' => 'qa_director']);
        $qaDirector->syncPermissions(Permission::all());

        $administrator = Role::firstOrCreate(['name' => 'administrator']);
        $administrator->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'campus_incharge']);

        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions(['view reports', 'manage colleges', 'manage programs']);

        // Create admin user (only if doesn't exist)
        $qaUser = User::firstOrCreate(
            ['email' => 'qa_director@cpsu.edu.ph'],
            [
                'name' => 'QA Director',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $qaUser->syncRoles('qa_director');

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@cpsu.edu.ph'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles('administrator');

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@cpsu.edu.ph'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $staffUser->syncRoles('staff');

        // Run other seeders only if tables are empty
        if (SurveyVisit::count() === 0) {
            $this->call([
                SurveyVisitSeeder::class,
                CollegeSeeder::class,
            ]);
        } else {
            echo "Survey visits and colleges already seeded, skipping...\n";
        }

        // Sample in-charge for the College of Arts and Sciences, assigned to the preliminary visit.
        $cas = College::where('code', 'CAS')->first();
        if ($cas) {
            $inCharge = User::firstOrCreate(
                ['email' => 'cas_incharge@cpsu.edu.ph'],
                ['name' => 'CAS In-charge', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
            );
            $inCharge->update(['college_id' => $cas->id]);
            $inCharge->syncRoles('campus_incharge');
            SurveyVisit::where('code', 'PRELIMINARY')->first()?->colleges()->syncWithoutDetaching([$cas->id]);
        }

        echo "Database seeded successfully!\n";
        echo "\nLogin Credentials:\n";
        echo "QA Director: qa_director@cpsu.edu.ph / password123\n";
        echo "Administrator: admin@cpsu.edu.ph / password123\n";
        echo "Staff: staff@cpsu.edu.ph / password123\n";
        echo "CAS In-charge: cas_incharge@cpsu.edu.ph / password123\n";
    }
}