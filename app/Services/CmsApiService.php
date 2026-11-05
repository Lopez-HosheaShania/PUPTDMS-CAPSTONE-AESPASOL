<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CmsApiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ocms.base_url')) && filled(config('services.ocms.api_key'));
    }

    public function getAdmins(array $query = []): array
    {
        $data = $this->request('/external/admins', $query);
        if (! array_is_list($data)) {
            throw new RuntimeException('CMS returned an invalid admin directory.');
        }

        return array_map(fn (array $record) => $this->normalize($record), $data);
    }

    public function getAdmin(string $identifier): array
    {
        // Older assignments stored email as the external ID. Resolve it to the canonical ID first.
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($identifier)
                ?? throw new RuntimeException('CMS admin profile was not found.');
        }

        return $this->normalize($this->request('/external/admins/'.rawurlencode($identifier)));
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $matches = array_values(array_filter($this->getAdmins(),
            fn (array $admin) => strtolower(trim((string) $admin['email'])) === $email));
        if (count($matches) > 1) {
            throw new RuntimeException('CMS returned multiple profiles for the same email.');
        }

        return $matches[0] ?? null;
    }

    private function request(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('CMS API configuration is missing.');
        }

        $response = Http::acceptJson()->timeout(15)->connectTimeout(5)
            ->withoutRedirecting()
            ->withHeaders(['X-External-Api-Key' => config('services.ocms.api_key')])
            ->get(rtrim(trim((string) config('services.ocms.base_url')), '/').$path, $query)
            ->throw();
        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)
            || ($payload['success'] ?? true) === false || ! is_array($payload['data'] ?? null)) {
            throw new RuntimeException('CMS returned an invalid profile response.');
        }

        return $payload['data'];
    }

    private function normalize(array $record): array
    {
        $id = $this->value($record, ['admin_uuid', 'admin_id', 'external_admin_id', 'id', 'user_id']);
        if ($id === null) {
            throw new RuntimeException('CMS admin profile is missing its identifier.');
        }

        $first = $this->value($record, ['first_name', 'fname']) ?? '';
        $last = $this->value($record, ['last_name', 'lname']) ?? '';

        return array_replace($record, [
            'admin_id' => $id,
            'fname' => $first,
            'lname' => $last,
            'full_name' => $this->value($record, ['name', 'full_name']) ?? trim($first.' '.$last),
            'email' => $this->value($record, ['email', 'email_address']) ?? '',
            'office' => $this->value($record, ['office']) ?? '',
            'address' => $this->value($record, ['address', 'home_address']) ?? '',
            'age' => $record['age'] ?? null,
            'gender' => $this->value($record, ['gender']) ?? '',
            'birthday' => $this->value($record, ['birthday', 'birthdate']),
            'contact_number' => $this->value($record, ['contact_no', 'contact_number', 'emergency_contact_no']) ?? '',
            'senior_pwd' => $this->value($record, ['senior_pwd']) ?? '',
            'status' => $this->value($record, ['status']),
        ]);
    }

    private function value(array $record, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (is_scalar($record[$field] ?? null) && trim((string) $record[$field]) !== '') {
                return trim((string) $record[$field]);
            }
        }

        return null;
    }
}
