<?php

namespace App\Support\CmsReference;

class CmsExtendedPayers
{
    public static function stateBcbsPayers(): array
    {
        $plans = [
            'AL' => ['name' => 'Blue Cross Blue Shield of Alabama', 'id' => '00510', 'ownership' => 'nonprofit'],
            'AK' => ['name' => 'Premera Blue Cross Blue Shield of Alaska', 'id' => '00958', 'ownership' => 'nonprofit'],
            'AZ' => ['name' => 'Blue Cross Blue Shield of Arizona', 'id' => '00932', 'ownership' => 'nonprofit'],
            'AR' => ['name' => 'Blue Cross Blue Shield of Arkansas', 'id' => '00520', 'ownership' => 'nonprofit'],
            'CA' => ['name' => 'Anthem Blue Cross of California', 'id' => '47198', 'ownership' => 'private'],
            'CO' => ['name' => 'Anthem Blue Cross Blue Shield Colorado', 'id' => '00050', 'ownership' => 'private'],
            'CT' => ['name' => 'Anthem Blue Cross Blue Shield Connecticut', 'id' => '00060', 'ownership' => 'private'],
            'DE' => ['name' => 'Highmark Blue Cross Blue Shield Delaware', 'id' => '00570', 'ownership' => 'nonprofit'],
            'DC' => ['name' => 'CareFirst BlueCross BlueShield DC', 'id' => '00580', 'ownership' => 'nonprofit'],
            'FL' => ['name' => 'Florida Blue', 'id' => '00590', 'ownership' => 'nonprofit'],
            'GA' => ['name' => 'Blue Cross Blue Shield of Georgia', 'id' => '00601', 'ownership' => 'private'],
            'HI' => ['name' => 'Hawaii Medical Service Association (BCBS)', 'id' => '00602', 'ownership' => 'nonprofit'],
            'ID' => ['name' => 'Blue Cross of Idaho', 'id' => '00610', 'ownership' => 'nonprofit'],
            'IL' => ['name' => 'Health Care Service Corporation (BCBS IL)', 'id' => '00621', 'ownership' => 'nonprofit'],
            'IN' => ['name' => 'Anthem Blue Cross Blue Shield Indiana', 'id' => '00622', 'ownership' => 'private'],
            'IA' => ['name' => 'Wellmark Blue Cross Blue Shield', 'id' => '00630', 'ownership' => 'nonprofit'],
            'KS' => ['name' => 'Blue Cross Blue Shield of Kansas', 'id' => '00640', 'ownership' => 'nonprofit'],
            'KY' => ['name' => 'Anthem Blue Cross Blue Shield Kentucky', 'id' => '00660', 'ownership' => 'private'],
            'LA' => ['name' => 'Blue Cross Blue Shield of Louisiana', 'id' => '00670', 'ownership' => 'nonprofit'],
            'ME' => ['name' => 'Anthem Blue Cross Blue Shield Maine', 'id' => '00680', 'ownership' => 'private'],
            'MD' => ['name' => 'CareFirst BlueCross BlueShield Maryland', 'id' => '00690', 'ownership' => 'nonprofit'],
            'MA' => ['name' => 'Blue Cross Blue Shield of Massachusetts', 'id' => '00700', 'ownership' => 'nonprofit'],
            'MI' => ['name' => 'Blue Cross Blue Shield of Michigan', 'id' => '00710', 'ownership' => 'nonprofit'],
            'MN' => ['name' => 'Blue Cross Blue Shield of Minnesota', 'id' => '00720', 'ownership' => 'nonprofit'],
            'MS' => ['name' => 'Blue Cross Blue Shield of Mississippi', 'id' => '00730', 'ownership' => 'nonprofit'],
            'MO' => ['name' => 'Blue Cross Blue Shield of Kansas City / Anthem MO', 'id' => '00740', 'ownership' => 'private'],
            'MT' => ['name' => 'Blue Cross Blue Shield of Montana', 'id' => '00750', 'ownership' => 'nonprofit'],
            'NE' => ['name' => 'Blue Cross Blue Shield of Nebraska', 'id' => '00760', 'ownership' => 'nonprofit'],
            'NV' => ['name' => 'Anthem Blue Cross Blue Shield Nevada', 'id' => '00770', 'ownership' => 'private'],
            'NH' => ['name' => 'Anthem Blue Cross Blue Shield New Hampshire', 'id' => '00780', 'ownership' => 'private'],
            'NJ' => ['name' => 'Horizon Blue Cross Blue Shield of New Jersey', 'id' => '00790', 'ownership' => 'nonprofit'],
            'NM' => ['name' => 'Blue Cross Blue Shield of New Mexico', 'id' => '00800', 'ownership' => 'private'],
            'NY' => ['name' => 'Empire BlueCross BlueShield (NY)', 'id' => '00803', 'ownership' => 'private'],
            'NC' => ['name' => 'Blue Cross Blue Shield of North Carolina', 'id' => '00804', 'ownership' => 'nonprofit'],
            'ND' => ['name' => 'Blue Cross Blue Shield of North Dakota', 'id' => '00805', 'ownership' => 'nonprofit'],
            'OH' => ['name' => 'Anthem Blue Cross Blue Shield Ohio', 'id' => '00834', 'ownership' => 'private'],
            'OK' => ['name' => 'Blue Cross Blue Shield of Oklahoma', 'id' => '00840', 'ownership' => 'nonprofit'],
            'OR' => ['name' => 'Regence BlueCross BlueShield of Oregon', 'id' => '00850', 'ownership' => 'nonprofit'],
            'PA' => ['name' => 'Highmark Blue Cross Blue Shield (PA)', 'id' => '54771', 'ownership' => 'nonprofit'],
            'RI' => ['name' => 'Blue Cross Blue Shield of Rhode Island', 'id' => '00870', 'ownership' => 'nonprofit'],
            'SC' => ['name' => 'Blue Cross Blue Shield of South Carolina', 'id' => '00880', 'ownership' => 'nonprofit'],
            'SD' => ['name' => 'Wellmark Blue Cross Blue Shield SD', 'id' => '00890', 'ownership' => 'nonprofit'],
            'TN' => ['name' => 'BlueCross BlueShield of Tennessee', 'id' => '00902', 'ownership' => 'nonprofit'],
            'TX' => ['name' => 'Blue Cross Blue Shield of Texas', 'id' => '84980', 'ownership' => 'nonprofit'],
            'UT' => ['name' => 'Regence BlueCross BlueShield of Utah', 'id' => '00910', 'ownership' => 'nonprofit'],
            'VT' => ['name' => 'Blue Cross Blue Shield of Vermont', 'id' => '00920', 'ownership' => 'nonprofit'],
            'VA' => ['name' => 'Anthem Blue Cross Blue Shield Virginia', 'id' => '00923', 'ownership' => 'private'],
            'WA' => ['name' => 'Premera Blue Cross (WA)', 'id' => '00924', 'ownership' => 'nonprofit'],
            'WV' => ['name' => 'Highmark Blue Cross Blue Shield West Virginia', 'id' => '00925', 'ownership' => 'nonprofit'],
            'WI' => ['name' => 'Anthem Blue Cross Blue Shield Wisconsin', 'id' => '00926', 'ownership' => 'private'],
            'WY' => ['name' => 'Blue Cross Blue Shield of Wyoming', 'id' => '00928', 'ownership' => 'nonprofit'],
            'PR' => ['name' => 'BlueCross BlueShield of Puerto Rico (Triple-S)', 'id' => '00929', 'ownership' => 'nonprofit'],
        ];

        $payers = [];
        foreach ($plans as $state => $plan) {
            $payers[] = [
                'code' => 'BCBS-'.$state,
                'name' => $plan['name'],
                'state' => $state,
                'ownership' => $plan['ownership'],
                'electronic_payer_id' => $plan['id'],
                'plan_type' => 'commercial_ppo',
            ];
        }

        return $payers;
    }

    public static function qhpIssuers(): array
    {
        $issuers = [
            ['id' => '21989', 'name' => 'Delta Dental', 'ownership' => 'private', 'market_type' => 'dental'],
            ['id' => '73836', 'name' => 'UnitedHealthcare', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '60054', 'name' => 'Aetna (CVS Health)', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '62308', 'name' => 'Cigna Healthcare', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '61101', 'name' => 'Humana', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '00834', 'name' => 'Elevance Health (Anthem)', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '91051', 'name' => 'Kaiser Permanente', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '38333', 'name' => 'Molina Healthcare', 'ownership' => 'private', 'market_type' => 'medicaid_mco'],
            ['id' => '68069', 'name' => 'Centene / Ambetter', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '87726', 'name' => 'UnitedHealthcare (Optum)', 'ownership' => 'private', 'market_type' => 'shop'],
            ['id' => '47198', 'name' => 'Anthem Blue Cross', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '84980', 'name' => 'Blue Cross Blue Shield of Texas', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00590', 'name' => 'Florida Blue', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '54771', 'name' => 'Highmark', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00804', 'name' => 'Blue Cross NC', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00902', 'name' => 'BlueCross BlueShield of Tennessee', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00710', 'name' => 'Blue Cross Blue Shield of Michigan', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00700', 'name' => 'Blue Cross Blue Shield of Massachusetts', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00803', 'name' => 'Empire BlueCross BlueShield', 'ownership' => 'private', 'market_type' => 'individual'],
            ['id' => '00924', 'name' => 'Premera Blue Cross', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00910', 'name' => 'Regence BlueCross BlueShield', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00621', 'name' => 'Health Care Service Corporation', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00880', 'name' => 'Blue Cross Blue Shield of South Carolina', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00932', 'name' => 'Blue Cross Blue Shield of Arizona', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00670', 'name' => 'Blue Cross Blue Shield of Louisiana', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00630', 'name' => 'Wellmark', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00790', 'name' => 'Horizon Blue Cross Blue Shield of NJ', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00690', 'name' => 'CareFirst BlueCross BlueShield', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00870', 'name' => 'Blue Cross Blue Shield of Rhode Island', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
            ['id' => '00920', 'name' => 'Blue Cross Blue Shield of Vermont', 'ownership' => 'nonprofit', 'market_type' => 'individual'],
        ];

        $stateCodes = array_column(CmsCatalog::states(), 'code');
        $rows = [];

        foreach ($stateCodes as $state) {
            foreach ($issuers as $issuer) {
                $rows[] = array_merge($issuer, ['state' => $state]);
            }
        }

        return $rows;
    }
}
