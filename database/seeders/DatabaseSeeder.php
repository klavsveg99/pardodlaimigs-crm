<?php

namespace Database\Seeders;

use App\Models\CrmProperty;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Sēšana ir aizliegta produkcijā — reālie dati drīkst zaudēt tikai
        // izmantojot migrācijas, nevis db:seed.
        if (config('app.env') === 'production') {
            return;
        }

        User::firstOrCreate(
            ['email' => 'info@pardodlaimigs.lv'],
            [
                'name' => 'Roberts Evarsons',
                'email_verified_at' => now(),
                'role' => 'admin',
                'phone' => '+371 24 922 942',
                'description' => 'Nodrošinu profesionālu atbalstu nekustamā īpašuma realizācijā. Sadarbība ar mani ir pilnībā caurskatāma un saprotama. Jums būs skaidrs kā rodas īpašuma vērtība.',
                'password' => bcrypt(($_ENV['CRM_ADMIN_PASSWORD'] ?? getenv('CRM_ADMIN_PASSWORD')) ?: 'changeme'),
                'calendar_token' => 'hhEaJESI6euav6L7DWtoFH7kuqhtjJZFl871dRSYNgn933RHPVK6s60HhUe1',
                'linkedin_url' => 'https://www.linkedin.com/in/roberts-evarsons-861151106/',
            ]
        );

        $admin = User::where('email', 'info@pardodlaimigs.lv')->first();

        $agent = User::where('email', 'roberts@pardodlaimigs.lv')->first();
        if ($agent) {
            CrmProperty::where('owner_user_id', $agent->id)->update(['owner_user_id' => $admin->id]);
            $agent->delete();
        }

        $this->call(CrmPropertySeeder::class);
    }
}
