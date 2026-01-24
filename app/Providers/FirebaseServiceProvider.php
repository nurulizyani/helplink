<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Auth::class, function ($app) {

            $credentials = config('firebase.credentials');
            $projectId  = config('firebase.project_id');

            if (!$credentials || !$projectId) {
                throw new \RuntimeException('Firebase credentials or project_id missing');
            }

            $factory = (new Factory)
                ->withServiceAccount($credentials)
                ->withProjectId($projectId);

            return $factory->createAuth();
        });

        // alias
        $this->app->alias(Auth::class, 'firebase.auth');
    }
}
