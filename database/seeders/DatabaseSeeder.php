<?php

namespace Database\Seeders;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseSeeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@example.com');

        if (Capsule::table('users')->where('email', $email)->exists()) {
            echo "  Usuario admin ya existe: {$email}\n";
            return;
        }

        $now = now();

        Capsule::table('users')->insert([
            'name' => (string) env('ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => password_hash((string) env('ADMIN_PASSWORD', 'password'), PASSWORD_DEFAULT),
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        echo "  Usuario admin creado: {$email}\n";
    }
}
