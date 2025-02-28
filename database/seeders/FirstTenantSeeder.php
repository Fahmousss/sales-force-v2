<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FirstTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Kantor Pusat
        $kantorPusat = \App\Models\Team::create(['name' => 'Kantor Pusat', 'key' => '40005']);
        $user = \App\Models\User::find(1);

        if ($user) {
            $user->team_id = $kantorPusat->id; // Assign the team_id
            $user->save(); // Save the changes
        }

        // Seed Kantor Regional and their Kantor Cabang
        foreach (['I' => '20004', 'II' => '10004', 'III' => '40004', 'IV' => '50004', 'V' => '60004', 'VI' => '90004'] as $regionName => $regionKey) {
            \App\Models\Team::create(['name' => "Kantor Regional $regionName", 'key' => $regionKey, 'parent_key' => '40005']);
        }
    }
}
