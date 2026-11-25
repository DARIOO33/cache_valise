<?php

namespace Database\Seeders;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the single administrator account required by the cahier des charges.
     *
     * The password comes from ADMIN_PASSWORD when it is set; otherwise a random
     * one is generated and printed once. Change it after installation.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@cachevalises.test');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (blank($password)) {
            $password = Str::password(16);
            $generated = true;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        foreach ([
            'societe_nom' => 'Atelier cache-valises',
            'societe_adresse' => '',
            'societe_telephone' => '',
            'devise' => 'DT',
            'decimales' => '3',
            'prefixe_facture' => 'FAC',
            'prefixe_lot' => 'LOT',
            'prefixe_piece' => 'PC',
        ] as $cle => $valeur) {
            Parametre::firstOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }

        if ($admin->wasRecentlyCreated) {
            $this->command->info("Compte administrateur cree : {$email}");

            if ($generated) {
                $this->command->warn("Mot de passe genere : {$password}");
                $this->command->warn('Notez-le maintenant et changez-le apres connexion.');
            }
        } else {
            $this->command->info("Compte administrateur deja present : {$email}");
        }
    }
}
