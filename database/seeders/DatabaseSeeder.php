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
        User::firstOrCreate(
            ['email' => 'info@pardodlaimigs.lv'],
            [
                'name' => 'Roberts Evarsons',
                'email_verified_at' => now(),
                'role' => 'admin',
                'phone' => '+371 24 922 942',
                'description' => 'Nodrošinu profesionālu atbalstu nekustamā īpašuma realizācijā. Sadarbība ar mani ir pilnībā caurskatāma un saprotama. Jums būs skaidrs kā rodas īpašuma vērtība.',
                'password' => bcrypt('Admin123!'),
                'calendar_token' => 'hhEaJESI6euav6L7DWtoFH7kuqhtjJZFl871dRSYNgn933RHPVK6s60HhUe1',
                'linkedin_url' => 'https://www.linkedin.com/in/roberts-evarsons-861151106/',
            ]
        );

        User::firstOrCreate(
            ['email' => 'roberts@pardodlaimigs.lv'],
            [
                'name' => 'Roberts Evarsons',
                'email_verified_at' => now(),
                'role' => 'aģents',
                'phone' => '+371 24 922 942',
                'password' => bcrypt('Admin123!'),
                'position' => 'Nekustamā īpašuma aģents',
                'description' => 'Vairāk kā desmit gadu pieredze pārdošanas jomā mani ir novedusi pie nekustamo īpašumu tirdzniecības Latvijā. Pārdošana ir joma, kurā strādājot es jūtos savā vietā. Es zinu, cik reizēm pārdošanas process var likties sarežģīts, tomēr gadiem ejot esmu radis uz sarežģītām situācijām skatīties viegli un prasmīgi tās atrisināt.',
                'linkedin_url' => 'https://www.linkedin.com/in/roberts-evarsons-861151106/',
            ]
        );

        $this->call(CrmPropertySeeder::class);
    }
}
