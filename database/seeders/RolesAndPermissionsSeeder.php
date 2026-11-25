<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
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
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $qaDirector = Role::create(['name' => 'qa_director']);
        $qaDirector->givePermissionTo(Permission::all());

        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'view reports',
            'manage colleges',
            'manage programs',
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'QA Director',
            'email' => 'qa_director@cpsu.edu.ph',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('qa_director');

        // Create sample staff user
        $staffUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff@cpsu.edu.ph',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $staffUser->assignRole('staff');
    }
}