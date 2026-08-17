<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! $password && app()->environment('production')) {
            throw new RuntimeException('ADMIN_PASSWORD doit être définie avant de créer le compte administrateur en production.');
        }

        DB::table('rules')->updateOrInsert(
            ['name' => 'Admin'],
            [
                'description' => 'Administrateur avec tous les droits',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $adminRoleId = DB::table('rules')->where('name', 'Admin')->value('id');
        $email = env('ADMIN_EMAIL', 'admin@monprof.local');
        $admin = User::firstOrNew(['email' => $email]);

        $admin->fill([
            'rule_id' => $adminRoleId,
            'name' => env('ADMIN_NAME', 'Administrateur'),
            'last_name' => env('ADMIN_LAST_NAME', 'MonProf'),
            'phone' => env('ADMIN_PHONE', '+237600000000'),
            'password' => Hash::make($password ?: 'Admin@123456'),
        ]);

        $admin->unique_token = $admin->unique_token ?: (string) Str::uuid();
        $admin->save();

        $this->command?->info("Compte administrateur prêt : {$email}");
    }
}
