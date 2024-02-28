<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Réinitialise le cache des rôles et des permissions
        app()['cache']->forget('spatie.permission.cache');

        // Créer des permissions
        Permission::create(['name' => 'edit projects']);
        Permission::create(['name' => 'delete projects']);

        // Créer des rôles et assigner des permissions existantes
        $role = Role::create(['name' => 'super admin']);
        $role->givePermissionTo(['edit projects', 'delete projects']);

        $role = Role::create(['name' => 'project manager']);
        $role->givePermissionTo('edit projects');
    }
}

