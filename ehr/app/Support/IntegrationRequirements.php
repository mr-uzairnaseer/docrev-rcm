<?php

namespace App\Support;

class IntegrationRequirements
{
    public static function all(): array
    {
        return [
            'surescripts' => self::surescripts(),
            'lab_interface' => self::labInterface(),
            'hie_fhir' => self::hieFhir(),
            'ehr_sync' => self::ehrSync(),
        ];
    }

    public static function surescripts(): array
    {
        $driver = config('surescripts.driver', 'stub');

        if ($driver === 'stub') {
            return [
                'label' => 'E-Prescribing (Surescripts)',
                'driver' => 'stub',
                'ready' => true,
                'note' => 'Stub mode — prescriptions simulate NewRx. Set SURESCRIPTS_DRIVER=live for production.',
                'missing' => [],
                'you_provide' => self::surescriptsCredentials(),
            ];
        }

        $missing = self::missingEnvKeys([
            'SURESCRIPTS_CLIENT_ID',
            'SURESCRIPTS_CLIENT_SECRET',
            'SURESCRIPTS_ACCOUNT_ID',
        ]);

        return [
            'label' => 'E-Prescribing (Surescripts)',
            'driver' => 'live',
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => self::surescriptsCredentials(),
        ];
    }

    public static function labInterface(): array
    {
        $driver = config('lab.driver', 'stub');

        if ($driver === 'stub') {
            return [
                'label' => 'Lab interfaces (HL7 v2 / FHIR)',
                'driver' => 'stub',
                'ready' => true,
                'note' => 'HL7 ORM orders stage locally. Configure lab vendor for MLLP/FHIR.',
                'missing' => [],
                'you_provide' => self::labCredentials(),
            ];
        }

        $missing = self::missingEnvKeys(['LAB_FHIR_BASE_URL', 'LAB_FHIR_CLIENT_ID']);

        return [
            'label' => 'Lab interfaces (HL7 v2 / FHIR)',
            'driver' => $driver,
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => self::labCredentials(),
        ];
    }

    public static function hieFhir(): array
    {
        $driver = config('hie.driver', 'stub');

        return [
            'label' => 'HIE / FHIR exchange',
            'driver' => $driver,
            'ready' => $driver === 'stub',
            'note' => $driver === 'stub'
                ? 'Stub FHIR client active. Register HIE connections with signed vendor agreements.'
                : 'Add HIE connections with FHIR base URL, OAuth credentials, and signed agreement.',
            'missing' => [],
            'you_provide' => self::hieCredentials(),
        ];
    }

    public static function ehrSync(): array
    {
        $missing = self::missingEnvKeys(['BILLING_API_URL', 'BILLING_API_KEY', 'PORTAL_API_URL']);

        return [
            'label' => 'EHR cross-app sync',
            'ready' => $missing === [],
            'missing' => $missing,
            'you_provide' => [
                'BILLING_API_URL + BILLING_API_KEY',
                'PORTAL_API_URL + PORTAL_API_KEY',
            ],
        ];
    }

    private static function surescriptsCredentials(): array
    {
        return [
            'Surescripts account enrollment (https://portal.surescripts.com)',
            'Organization Surescripts account ID',
            'Provider SPI (Surescripts Provider Identifier) per prescriber',
            'DEA number for controlled substances',
            'EPCS certification if prescribing Schedule II-V electronically',
            'Pharmacy NCPDP IDs in system',
            'BAA with Surescripts',
        ];
    }

    private static function labCredentials(): array
    {
        return [
            'Lab vendor interface spec (HL7 v2 ORM/ORU or FHIR ServiceRequest/DiagnosticReport)',
            'MLLP host/port or FHIR base URL + OAuth credentials',
            'Sending/receiving application and facility codes',
            'Vendor-specific test compendium (LOINC/CPT codes)',
            'Signed interface agreement with lab (Quest, Labcorp, hospital lab, etc.)',
        ];
    }

    private static function hieCredentials(): array
    {
        return [
            'HIE network selection (CommonWell, Carequality, regional HIE, Epic Care Everywhere, etc.)',
            'Signed data sharing / participation agreement',
            'FHIR R4 base URL and OAuth client credentials',
            'Patient consent policy for record query/disclosure',
            'IHE/FHIR document types to exchange (CCD, clinical summary)',
        ];
    }

    private static function missingEnvKeys(array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            if (! env($key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
