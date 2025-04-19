<?php

namespace Database\Seeders;

use App\Models\ImageType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class ImageTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $imageTypes = Config::get('images.types', []);
        
        foreach ($imageTypes as $module => $types) {
            foreach ($types as $code => $label) {
                ImageType::updateOrCreate(
                    ['code' => $code, 'module' => $module],
                    ['label' => $label]
                );
            }
        }
    }
}
