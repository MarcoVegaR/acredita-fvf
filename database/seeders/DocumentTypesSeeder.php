<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = config('documents.types');
        $modules = config('documents.modules');
        
        // Create or update document types
        foreach ($types as $type) {
            // Check if type should be associated with a specific module
            $module = null;
            
            // Check which modules allow this type
            foreach ($modules as $moduleKey => $moduleConfig) {
                if (in_array($type['code'], $moduleConfig['allowed_types'] ?? [])) {
                    // If this is the default type for this module, associate it with the module
                    // Otherwise, we'll keep it as a global type (null module)
                    if (($moduleConfig['default_type'] ?? null) === $type['code']) {
                        $module = $moduleKey;
                    }
                }
            }
            
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'label' => $type['label'],
                    'module' => $module
                ]
            );
        }
    }
}
