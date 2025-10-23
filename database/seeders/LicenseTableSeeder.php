<?php

namespace Database\Seeders;


use Dipokhalder\EnvEditor\EnvEditor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Smartisan\Settings\Facades\Settings;

class LicenseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $envService = new EnvEditor();
            $apiKey = $envService->getValue('MIX_API_KEY');
            
            // Set default API key if not already set
            if (empty($apiKey)) {
                // Generate a unique key based on timestamp to avoid cache issues
                $apiKey = 'default-api-key-' . bin2hex(random_bytes(16));
                $envService->addData(['MIX_API_KEY' => $apiKey]);
            }
            
            Settings::group('license')->set([
                'license_key' => $apiKey
            ]);
            
            // Handle DEMO mode
            if ($envService->getValue('DEMO')) {
                Settings::group('license')->set([
                    'license_key' => 't8l57bk3-k4d6-48z9-3331-h708j46098r124'
                ]);
                $envService->addData(['MIX_API_KEY' => 't8l57bk3-k4d6-48z9-3331-h708j46098r124']);
                Artisan::call('optimize:clear');
            }
        } catch (\Exception $e) {
            // Fallback: Set a basic default license if anything fails
            Settings::group('license')->set([
                'license_key' => 'default-license-key'
            ]);
        }
    }
}