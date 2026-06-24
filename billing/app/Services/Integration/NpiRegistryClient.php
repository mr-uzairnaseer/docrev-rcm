<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class NpiRegistryClient
{
    private const API_BASE = 'https://npiregistry.cms.hhs.gov/api/';

    public function lookup(string $npi): ?array
    {
        $npi = preg_replace('/\D/', '', $npi);
        if (strlen($npi) !== 10) {
            throw new RuntimeException('NPI must be exactly 10 digits.');
        }

        $payload = $this->request(['number' => $npi]);
        $results = $payload['results'] ?? [];
        if ($results === []) {
            return null;
        }

        return $this->parseRecord($results[0]);
    }

    /**
     * Find Type-1 (individual) NPIs associated with a practice name in NPPES.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchIndividualsByPractice(string $organizationName, ?string $state = null, int $limit = 25): array
    {
        $organizationName = trim($organizationName);
        if ($organizationName === '') {
            return [];
        }

        $params = [
            'enumeration_type' => 'NPI-1',
            'organization_name' => $organizationName,
            'limit' => min(max($limit, 1), 50),
        ];

        if ($state) {
            $params['state'] = strtoupper(substr(trim($state), 0, 2));
        }

        $payload = $this->request($params);
        $results = $payload['results'] ?? [];

        return array_values(array_map(fn (array $record) => $this->parseRecord($record), $results));
    }

    private function request(array $params): array
    {
        $response = Http::timeout(20)->get(self::API_BASE, array_merge([
            'version' => '2.1',
        ], $params));

        if (! $response->successful()) {
            throw new RuntimeException('NPPES registry request failed.');
        }

        return $response->json();
    }

    private function parseRecord(array $record): array
    {
        $basic = $record['basic'] ?? [];
        $addresses = $record['addresses'] ?? [];
        $taxonomies = $record['taxonomies'] ?? [];
        $enumerationType = $record['enumeration_type'] ?? null;
        $isOrganization = $enumerationType === 'NPI-2';

        $location = $this->parseAddress($addresses, 'LOCATION')
            ?? $this->parseAddress($addresses, 'MAILING');

        $organizationName = $basic['organization_name'] ?? null;
        $firstName = $basic['first_name'] ?? null;
        $lastName = $basic['last_name'] ?? null;

        $displayName = $isOrganization
            ? $organizationName
            : trim(($firstName ?? '').' '.($lastName ?? ''));

        return [
            'npi' => $record['number'] ?? null,
            'name' => $displayName,
            'organization_name' => $organizationName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'enumeration_type' => $enumerationType,
            'apply_as' => $isOrganization ? 'organization' : 'rendering_provider',
            'status' => $basic['status'] ?? null,
            'credential' => $basic['credential'] ?? null,
            'taxonomy' => $taxonomies[0]['desc'] ?? null,
            'taxonomy_code' => $taxonomies[0]['code'] ?? null,
            'address' => $location,
            'primary_practice_address' => $this->formatAddressString($location),
            'phone' => $location['phone'] ?? null,
            'source' => 'https://npiregistry.cms.hhs.gov/',
        ];
    }

    /**
     * @return array{line1: string, line2: ?string, city: string, state: string, zip: string, phone: ?string}|null
     */
    private function parseAddress(array $addresses, string $purpose): ?array
    {
        foreach ($addresses as $address) {
            if (($address['address_purpose'] ?? '') !== $purpose) {
                continue;
            }

            $line1 = trim($address['address_1'] ?? '');
            if ($line1 === '') {
                continue;
            }

            $zip = preg_replace('/\D/', '', $address['postal_code'] ?? '') ?? '';
            if (strlen($zip) === 9) {
                $zip = substr($zip, 0, 5).'-'.substr($zip, 5);
            } elseif (strlen($zip) > 5) {
                $zip = substr($zip, 0, 5);
            }

            return [
                'line1' => $line1,
                'line2' => trim($address['address_2'] ?? '') ?: null,
                'city' => trim($address['city'] ?? ''),
                'state' => strtoupper(trim($address['state'] ?? '')),
                'zip' => $zip,
                'phone' => $this->formatPhone($address['telephone_number'] ?? null),
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $address
     */
    private function formatAddressString(?array $address): ?string
    {
        if (! $address) {
            return null;
        }

        return trim(implode(', ', array_filter([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['zip'] ?? null,
        ])));
    }

    private function formatPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6);
        }

        return $phone;
    }
}
