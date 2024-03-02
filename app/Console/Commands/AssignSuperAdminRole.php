<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignSuperAdminRole extends Command
{
    // Le nom et la signature de la commande console
    protected $signature = 'user:assign-superadmin {email}';

    // La description de la commande console
    protected $description = 'Assign the "super admin" role to a user';

    // La logique d'exécution de la commande
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found.');
            return;
        }

        // Assurez-vous que le rôle existe avant de l'assigner
        $roleName = 'super admin';
        $role = Role::firstOrCreate(['name' => $roleName]);

        $user->assignRole($roleName);
        $this->info("The role '{$roleName}' has been assigned to {$email}.");
    }
}
