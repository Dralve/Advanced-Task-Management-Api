<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $developer = Role::create(['name' => 'developer']);

        Permission::create(['name' => 'view-users']);
        Permission::create(['name' => 'create-users']);
        Permission::create(['name' => 'update-users']);
        Permission::create(['name' => 'assign-roles']);
        Permission::create(['name' => 'delete-users']);
        Permission::create(['name' => 'restore-users']);
        Permission::create(['name' => 'view-trashed-tasks']);
        Permission::create(['name' => 'view-tasks']);
        Permission::create(['name' => 'create-tasks']);
        Permission::create(['name' => 'update-tasks']);
        Permission::create(['name' => 'update-status-tasks']);
        Permission::create(['name' => 'assign-task']);
        Permission::create(['name' => 'delete-tasks']);
        Permission::create(['name' => 'restore-tasks']);
        Permission::create(['name' => 'add-comment']);
        Permission::create(['name' => 'delete-comment']);
        Permission::create(['name' => 'add-attachment']);
        Permission::create(['name' => 'delete-attachment']);

        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo([
            'view-users',
            'view-tasks',
            'create-tasks',
            'update-tasks',
            'assign-task',
            'delete-tasks',
            'restore-tasks',
            'add-comment',
            'delete-comment',
            'add-attachment',
            'delete-attachment',
        ]);


        $developer->givePermissionTo([
            'view-users',
            'view-tasks',
            'update-status-tasks',
            'add-comment',
            'delete-comment',
            'add-attachment',
            'delete-attachment',
        ]);
    }
}
