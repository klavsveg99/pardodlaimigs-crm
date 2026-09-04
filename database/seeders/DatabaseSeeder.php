<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'Roberts Evarsons',
            'email' => 'info@pardodlaimigs.lv',
            'email_verified_at' => now(),
            'role' => 'admin',
            'phone' => '+371 24 922 942',
            'description' => 'Nodrošinu profesionālu atbalstu nekustamā īpašuma realizācijā. Sadarbība ar mani ir pilnībā caurskatāma un saprotama. Jums būs skaidrs kā rodas īpašuma vērtība.',
            'password' => '$2y$12$1PDtjn/kk5bTkGSFFelkf.K9gtI0PdxnKXDSUDGA6QX21EFLlD2Bm',
            'calendar_token' => 'hhEaJESI6euav6L7DWtoFH7kuqhtjJZFl871dRSYNgn933RHPVK6s60HhUe1',
        ]);

        $this->call(CrmPropertySeeder::class);
    }
}
