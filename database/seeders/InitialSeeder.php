<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        // Create departments
        $admin_dept = Department::create([
            'name' => 'Administration',
            'code' => 'ADMIN',
            'description' => 'Administrative department',
            'is_active' => true,
        ]);

        $md_dept = Department::create([
            'name' => 'Medical Department',
            'code' => 'MD',
            'description' => 'Medical services department',
            'is_active' => true,
        ]);

        $proc_dept = Department::create([
            'name' => 'Procurement',
            'code' => 'PROC',
            'description' => 'Procurement and supply department',
            'is_active' => true,
        ]);

        // Create users
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => 'password',
            'department_id' => $admin_dept->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $admin->assignRole('admin');

        $md = User::create([
            'name' => 'Medical Director',
            'email' => 'md@example.com',
            'password' => 'password',
            'department_id' => $md_dept->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $md->assignRole('dept_head');

        $proc = User::create([
            'name' => 'Procurement Officer',
            'email' => 'proc@example.com',
            'password' => 'password',
            'department_id' => $proc_dept->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $proc->assignRole('user');

        // Update department heads
        $admin_dept->update(['head_user_id' => $admin->id]);
        $md_dept->update(['head_user_id' => $md->id]);
        $proc_dept->update(['head_user_id' => $proc->id]);
    }
}