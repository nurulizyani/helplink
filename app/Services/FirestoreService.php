<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirestoreService
{
    protected string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
    }

    protected function getAccessToken(): string
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            json_decode(
                file_get_contents(base_path(env('FIREBASE_CREDENTIALS'))),
                true
            )
        );

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }

    protected function formatValue($value): array
    {
        if ($value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format(DATE_RFC3339)];
        }

        if (is_int($value)) {
            return ['integerValue' => $value];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        return ['stringValue' => (string) $value];
    }

    public function updateUser(string $uid, array $data): void
    {
        $token = $this->getAccessToken();

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->formatValue($value);
        }

        $response = Http::withToken($token)
            ->patch(
                "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}",
                [
                    'fields' => $fields
                ]
            );

        if (!$response->successful()) {
            throw new \Exception(
                'Firestore REST update failed: ' . $response->body()
            );
        }
    }
}
