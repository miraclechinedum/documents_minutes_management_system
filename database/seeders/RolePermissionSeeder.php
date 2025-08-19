<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'documents.create',
            'documents.view',
            'documents.edit',
            'documents.delete',
            'minutes.create',
            'minutes.view',
            'minutes.view_all',
            'documents.forward',
            'users.manage',
            'departments.manage',
            'print.export',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin - full access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Department Head - can manage department documents and users
        $deptHead = Role::create(['name' => 'dept_head']);
        $deptHead->givePermissionTo([
            'documents.create',
            'documents.view',
            'documents.edit',
            'minutes.create',
            'minutes.view',
            'minutes.view_all',
            'documents.forward',
            'print.export',
        ]);

        // Regular User - basic document operations
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'documents.create',
            'documents.view',
            'minutes.create',
            'minutes.view',
            'documents.forward',
            'print.export',
        ]);

        // Auditor - read-only access to everything
        $auditor = Role::create(['name' => 'auditor']);
        $auditor->givePermissionTo([
            'documents.view',
            'minutes.view',
            'minutes.view_all',
            'print.export',
            'audit.view',
        ]);
    }
}