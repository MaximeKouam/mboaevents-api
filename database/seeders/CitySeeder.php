<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Yaoundé', 'region' => 'Centre', 'latitude' => 3.8480, 'longitude' => 11.5021],
            ['name' => 'Douala', 'region' => 'Littoral', 'latitude' => 4.0511, 'longitude' => 9.7679],
            ['name' => 'Bafoussam', 'region' => 'Ouest', 'latitude' => 5.4737, 'longitude' => 10.4176],
            ['name' => 'Buea', 'region' => 'Sud-Ouest', 'latitude' => 4.1560, 'longitude' => 9.2426],
            ['name' => 'Kribi', 'region' => 'Sud', 'latitude' => 2.9438, 'longitude' => 9.9077],
            ['name' => 'Garoua', 'region' => 'Nord', 'latitude' => 9.3017, 'longitude' => 13.3921],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(['name' => $city['name'], 'region' => $city['region']], $city);
        }
    }
}
