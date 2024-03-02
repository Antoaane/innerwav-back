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

        // Création de permissions de base
        Permission::create(['name' => 'view any project']);
        Permission::create(['name' => 'edit project']);
        Permission::create(['name' => 'delete project']);

        // Création du rôle 'super admin'
        $role = Role::create(['name' => 'super admin']);

        // Assignation des permissions au rôle 'super admin'
        $permissions = Permission::all(); // Récupère toutes les permissions créées
        $role->givePermissionTo($permissions);
    }
}

