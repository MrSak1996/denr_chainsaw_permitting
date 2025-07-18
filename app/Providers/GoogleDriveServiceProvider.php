<?php

namespace App\Providers;

use Masbug\Flysystem\GoogleDriveAdapter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

use Illuminate\Support\Facades\Storage;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */

    public function boot()
    {
        Storage::extend('google', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);

            // ✅ Set a dummy access token + refresh token as required structure
            $client->setAccessToken([
                'access_token' => '', // placeholder
                'expires_in' => 0,
                'created' => time(),
                'refresh_token' => $config['refresh_token'],
            ]);

            // ✅ Attempt to fetch new token using refresh token
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);
            }

            $service = new \Google_Service_Drive($client);

            $adapter = new GoogleDriveAdapter($service, $config['folder_id'] ?? null);

            return new Filesystem($adapter);
        });
    }
}
