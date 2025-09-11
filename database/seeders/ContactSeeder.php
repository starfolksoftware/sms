<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $sources = ['website_form','meta_ads','x','instagram','referral','manual','other'];
        $statuses = ['lead','qualified','customer','archived'];

        $users = User::pluck('id')->all();

        Contact::factory()->count(20)->create()->each(function (Contact $c) use ($sources, $statuses, $users) {
            $c->update([
                'status' => Arr::random($statuses),
                'source' => Arr::random($sources),
                'owner_id' => Arr::random($users) ?: null,
            ]);
        });
    }
}
