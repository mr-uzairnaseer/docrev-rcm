<?php

namespace App\Support\CmsReference;

class CmsCatalog
{
    public static function regions(): array
    {
        return [
            ['number' => 1, 'name' => 'CMS Region 1 — Boston'],
            ['number' => 2, 'name' => 'CMS Region 2 — New York'],
            ['number' => 3, 'name' => 'CMS Region 3 — Philadelphia'],
            ['number' => 4, 'name' => 'CMS Region 4 — Atlanta'],
            ['number' => 5, 'name' => 'CMS Region 5 — Chicago'],
            ['number' => 6, 'name' => 'CMS Region 6 — Dallas'],
            ['number' => 7, 'name' => 'CMS Region 7 — Kansas City'],
            ['number' => 8, 'name' => 'CMS Region 8 — Denver'],
            ['number' => 9, 'name' => 'CMS Region 9 — San Francisco'],
            ['number' => 10, 'name' => 'CMS Region 10 — Seattle'],
        ];
    }

    public static function states(): array
    {
        $map = [
            1 => ['CT', 'MA', 'ME', 'NH', 'RI', 'VT'],
            2 => ['NJ', 'NY', 'PR', 'VI'],
            3 => ['DC', 'DE', 'MD', 'PA', 'VA', 'WV'],
            4 => ['AL', 'FL', 'GA', 'KY', 'MS', 'NC', 'SC', 'TN'],
            5 => ['IL', 'IN', 'MI', 'MN', 'OH', 'WI'],
            6 => ['AR', 'LA', 'NM', 'OK', 'TX'],
            7 => ['IA', 'KS', 'MO', 'NE'],
            8 => ['CO', 'MT', 'ND', 'SD', 'UT', 'WY'],
            9 => ['AZ', 'CA', 'HI', 'NV', 'AS', 'GU', 'MP'],
            10 => ['AK', 'ID', 'OR', 'WA'],
        ];

        $names = [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
            'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
            'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
            'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
            'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
            'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'PR' => 'Puerto Rico',
            'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee',
            'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VI' => 'U.S. Virgin Islands',
            'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin',
            'WY' => 'Wyoming', 'AS' => 'American Samoa', 'GU' => 'Guam', 'MP' => 'Northern Mariana Islands',
        ];

        $fips = [
            'AL' => '01', 'AK' => '02', 'AZ' => '04', 'AR' => '05', 'CA' => '06', 'CO' => '08',
            'CT' => '09', 'DE' => '10', 'DC' => '11', 'FL' => '12', 'GA' => '13', 'HI' => '15',
            'ID' => '16', 'IL' => '17', 'IN' => '18', 'IA' => '19', 'KS' => '20', 'KY' => '21',
            'LA' => '22', 'ME' => '23', 'MD' => '24', 'MA' => '25', 'MI' => '26', 'MN' => '27',
            'MS' => '28', 'MO' => '29', 'MT' => '30', 'NE' => '31', 'NV' => '32', 'NH' => '33',
            'NJ' => '34', 'NM' => '35', 'NY' => '36', 'NC' => '37', 'ND' => '38', 'OH' => '39',
            'OK' => '40', 'OR' => '41', 'PA' => '42', 'PR' => '72', 'RI' => '44', 'SC' => '45',
            'SD' => '46', 'TN' => '47', 'TX' => '48', 'UT' => '49', 'VT' => '50', 'VI' => '78',
            'VA' => '51', 'WA' => '53', 'WV' => '54', 'WI' => '55', 'WY' => '56', 'AS' => '60',
            'GU' => '66', 'MP' => '69',
        ];

        $territories = ['PR', 'VI', 'AS', 'GU', 'MP'];

        $states = [];
        foreach ($map as $region => $codes) {
            foreach ($codes as $code) {
                $states[] = [
                    'code' => $code,
                    'name' => $names[$code],
                    'region_number' => $region,
                    'jurisdiction_type' => $code === 'DC' ? 'district' : (in_array($code, $territories, true) ? 'territory' : 'state'),
                    'fips_code' => $fips[$code] ?? null,
                ];
            }
        }

        return $states;
    }

    public static function macs(): array
    {
        return [
            [
                'contract_number' => '01111',
                'name' => 'Noridian Healthcare Solutions, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J1',
                'website' => 'https://med.noridianmedicare.com/web/jea',
                'phone' => '877-320-0390',
                'processes_hhh' => false,
                'states' => ['CA', 'HI', 'NV', 'AS', 'GU', 'MP'],
            ],
            [
                'contract_number' => '02101',
                'name' => 'Noridian Healthcare Solutions, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J2',
                'website' => 'https://med.noridianmedicare.com/web/jf',
                'phone' => '877-320-0390',
                'processes_hhh' => false,
                'states' => ['AK', 'ID', 'OR', 'WA'],
            ],
            [
                'contract_number' => '03102',
                'name' => 'CGS Administrators, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J3',
                'website' => 'https://www.cgsmedicare.com/jh/',
                'phone' => '866-270-4909',
                'processes_hhh' => false,
                'states' => ['AZ', 'MT', 'ND', 'SD', 'UT', 'WY'],
            ],
            [
                'contract_number' => '04111',
                'name' => 'Novitas Solutions, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J4',
                'website' => 'https://www.novitas-solutions.com/web/jh/',
                'phone' => '855-252-8782',
                'processes_hhh' => false,
                'states' => ['CO', 'NM', 'OK', 'TX'],
            ],
            [
                'contract_number' => '05101',
                'name' => 'Wisconsin Physicians Service Insurance Corporation',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J5',
                'website' => 'https://www.wpsmedicare.com/j5',
                'phone' => '866-518-3285',
                'processes_hhh' => false,
                'states' => ['IA', 'KS', 'MO', 'NE'],
            ],
            [
                'contract_number' => '06102',
                'name' => 'National Government Services, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J6',
                'website' => 'https://www.ngsmedicare.com/j6',
                'phone' => '877-908-8431',
                'processes_hhh' => true,
                'states' => ['IL', 'MN', 'WI'],
            ],
            [
                'contract_number' => '07101',
                'name' => 'Novitas Solutions, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J7',
                'website' => 'https://www.novitas-solutions.com/web/jh/',
                'phone' => '855-252-8782',
                'processes_hhh' => false,
                'states' => ['AR', 'LA', 'MS'],
            ],
            [
                'contract_number' => '08102',
                'name' => 'Wisconsin Physicians Service Insurance Corporation',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J8',
                'website' => 'https://www.wpsmedicare.com/j8',
                'phone' => '866-518-3285',
                'processes_hhh' => false,
                'states' => ['IN', 'MI'],
            ],
            [
                'contract_number' => '09101',
                'name' => 'First Coast Service Options, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J9',
                'website' => 'https://medicare.fcso.com/',
                'phone' => '877-735-1327',
                'processes_hhh' => false,
                'states' => ['FL', 'PR', 'VI'],
            ],
            [
                'contract_number' => '10111',
                'name' => 'Palmetto GBA, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J10',
                'website' => 'https://www.palmettogba.com/palmetto/medicare.nsf',
                'phone' => '866-749-4301',
                'processes_hhh' => false,
                'states' => ['AL', 'GA', 'TN'],
            ],
            [
                'contract_number' => '11101',
                'name' => 'Palmetto GBA, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J11',
                'website' => 'https://www.palmettogba.com/palmetto/medicare.nsf',
                'phone' => '866-749-4301',
                'processes_hhh' => false,
                'states' => ['NC', 'SC', 'VA', 'WV'],
            ],
            [
                'contract_number' => '12102',
                'name' => 'Novitas Solutions, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J12',
                'website' => 'https://www.novitas-solutions.com/web/jh/',
                'phone' => '855-252-8782',
                'processes_hhh' => false,
                'states' => ['DE', 'DC', 'MD', 'NJ', 'PA'],
            ],
            [
                'contract_number' => '13102',
                'name' => 'National Government Services, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J13',
                'website' => 'https://www.ngsmedicare.com/j13',
                'phone' => '877-908-8431',
                'processes_hhh' => false,
                'states' => ['CT', 'NY'],
            ],
            [
                'contract_number' => '14111',
                'name' => 'National Government Services, Inc.',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J14',
                'website' => 'https://www.ngsmedicare.com/j14',
                'phone' => '877-908-8431',
                'processes_hhh' => false,
                'states' => ['ME', 'MA', 'NH', 'RI', 'VT'],
            ],
            [
                'contract_number' => '15201',
                'name' => 'CGS Administrators, LLC',
                'mac_type' => 'ab_mac',
                'jurisdiction_code' => 'J15',
                'website' => 'https://www.cgsmedicare.com/jk/',
                'phone' => '866-270-4909',
                'processes_hhh' => false,
                'states' => ['KY', 'OH'],
            ],
            [
                'contract_number' => '17013',
                'name' => 'Noridian Healthcare Solutions, LLC',
                'mac_type' => 'dme_mac',
                'jurisdiction_code' => 'DME-A',
                'website' => 'https://www.noridianmedicare.com/dme/',
                'phone' => '877-320-0390',
                'processes_hhh' => false,
                'states' => ['CT', 'DE', 'ME', 'MA', 'MD', 'NH', 'NJ', 'NY', 'PA', 'RI', 'VT', 'DC', 'PR', 'VI'],
            ],
            [
                'contract_number' => '19003',
                'name' => 'CGS Administrators, LLC',
                'mac_type' => 'dme_mac',
                'jurisdiction_code' => 'DME-B',
                'website' => 'https://www.cgsmedicare.com/dme/',
                'phone' => '866-270-4909',
                'processes_hhh' => false,
                'states' => ['IL', 'IN', 'KY', 'MI', 'MN', 'OH', 'WI'],
            ],
            [
                'contract_number' => '20002',
                'name' => 'Noridian Healthcare Solutions, LLC',
                'mac_type' => 'dme_mac',
                'jurisdiction_code' => 'DME-C',
                'website' => 'https://www.noridianmedicare.com/dme/',
                'phone' => '877-320-0390',
                'processes_hhh' => false,
                'states' => ['AL', 'AR', 'FL', 'GA', 'LA', 'MS', 'NC', 'SC', 'TN', 'TX', 'VA', 'WV', 'PR', 'VI'],
            ],
            [
                'contract_number' => '21002',
                'name' => 'Southeastern Medicare Administrative Services (Palmetto GBA)',
                'mac_type' => 'dme_mac',
                'jurisdiction_code' => 'DME-D',
                'website' => 'https://www.palmettogba.com/palmetto/dme.nsf',
                'phone' => '866-204-0117',
                'processes_hhh' => false,
                'states' => ['AK', 'AZ', 'CA', 'CO', 'HI', 'IA', 'ID', 'KS', 'MO', 'MT', 'ND', 'NE', 'NV', 'NM', 'OK', 'OR', 'SD', 'UT', 'WA', 'WY', 'AS', 'GU', 'MP'],
            ],
        ];
    }

    public static function medicaidPrograms(): array
    {
        return [
            'AL' => ['name' => 'Alabama Medicaid Agency', 'electronic_payer_id' => '12B57', 'website' => 'https://medicaid.alabama.gov/'],
            'AK' => ['name' => 'Alaska Medicaid', 'electronic_payer_id' => 'SKAK0', 'website' => 'https://health.alaska.gov/dhcs/Pages/default.aspx'],
            'AZ' => ['name' => 'AHCCCS', 'electronic_payer_id' => '12X01', 'website' => 'https://www.azahcccs.gov/'],
            'AR' => ['name' => 'Arkansas Medicaid', 'electronic_payer_id' => '12043', 'website' => 'https://humanservices.arkansas.gov/divisions/medical-services/'],
            'CA' => ['name' => 'Medi-Cal', 'electronic_payer_id' => '57106', 'website' => 'https://www.dhcs.ca.gov/services/medi-cal'],
            'CO' => ['name' => 'Health First Colorado', 'electronic_payer_id' => '12K04', 'website' => 'https://hcpf.colorado.gov/'],
            'CT' => ['name' => 'HUSKY Health (Connecticut Medicaid)', 'electronic_payer_id' => '12K02', 'website' => 'https://portal.ct.gov/HUSKY'],
            'DE' => ['name' => 'Delaware Medicaid', 'electronic_payer_id' => '12K01', 'website' => 'https://dhss.delaware.gov/dss/medicaid.html'],
            'DC' => ['name' => 'DC Medicaid (DHCF)', 'electronic_payer_id' => '12705', 'website' => 'https://dhcf.dc.gov/'],
            'FL' => ['name' => 'Florida Medicaid', 'electronic_payer_id' => '77027', 'website' => 'https://www.flmedicaidmanagedcare.com/'],
            'GA' => ['name' => 'Georgia Medicaid (DCH)', 'electronic_payer_id' => '77034', 'website' => 'https://medicaid.georgia.gov/'],
            'HI' => ['name' => 'Med-QUEST (Hawaii Medicaid)', 'electronic_payer_id' => '12K06', 'website' => 'https://medquest.hawaii.gov/'],
            'ID' => ['name' => 'Idaho Medicaid', 'electronic_payer_id' => '12705', 'website' => 'https://healthandwelfare.idaho.gov/services-programs/medicaid-health'],
            'IL' => ['name' => 'Illinois Medicaid (HFS)', 'electronic_payer_id' => '37127', 'website' => 'https://hfs.illinois.gov/'],
            'IN' => ['name' => 'Indiana Medicaid (FSSA)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.in.gov/medicaid/'],
            'IA' => ['name' => 'Iowa Medicaid (HHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://hhs.iowa.gov/programs/welcome-iowa-medicaid'],
            'KS' => ['name' => 'KanCare (Kansas Medicaid)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.kancare.ks.gov/'],
            'KY' => ['name' => 'Kentucky Medicaid (DMS)', 'electronic_payer_id' => '12B57', 'website' => 'https://chfs.ky.gov/agencies/dms/Pages/default.aspx'],
            'LA' => ['name' => 'Louisiana Medicaid (LDH)', 'electronic_payer_id' => '12B57', 'website' => 'https://ldh.la.gov/medicaid'],
            'ME' => ['name' => 'MaineCare', 'electronic_payer_id' => '12B57', 'website' => 'https://www.maine.gov/dhhs/oms/'],
            'MD' => ['name' => 'Maryland Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://health.maryland.gov/mmcp/'],
            'MA' => ['name' => 'MassHealth', 'electronic_payer_id' => '12B57', 'website' => 'https://www.mass.gov/masshealth'],
            'MI' => ['name' => 'Michigan Medicaid (MDHHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.michigan.gov/mdhhs/assistance-programs/medicaid'],
            'MN' => ['name' => 'Minnesota Medical Assistance', 'electronic_payer_id' => '12B57', 'website' => 'https://mn.gov/dhs/people-we-serve/adults/health-care/health-care-programs/programs-and-services/medical-assistance.jsp'],
            'MS' => ['name' => 'Mississippi Medicaid (DOM)', 'electronic_payer_id' => '12B57', 'website' => 'https://medicaid.ms.gov/'],
            'MO' => ['name' => 'MO HealthNet', 'electronic_payer_id' => '12B57', 'website' => 'https://mydss.mo.gov/mhd'],
            'MT' => ['name' => 'Montana Medicaid (DPHHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://dphhs.mt.gov/MontanaHealthcarePrograms/Medicaid'],
            'NE' => ['name' => 'Nebraska Medicaid (DHHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://dhhs.ne.gov/Pages/Medicaid.aspx'],
            'NV' => ['name' => 'Nevada Medicaid (DHCFP)', 'electronic_payer_id' => '12B57', 'website' => 'https://dhcfp.nv.gov/'],
            'NH' => ['name' => 'New Hampshire Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.dhhs.nh.gov/programs-services/medicaid'],
            'NJ' => ['name' => 'New Jersey Medicaid (NJ FamilyCare)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.nj.gov/humanservices/dmahs/'],
            'NM' => ['name' => 'New Mexico Medicaid (HSD)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.hsd.state.nm.us/lookingforassistance/medicaid/'],
            'NY' => ['name' => 'New York Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.health.ny.gov/health_care/medicaid/'],
            'NC' => ['name' => 'North Carolina Medicaid (NCDHHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://medicaid.ncdhhs.gov/'],
            'ND' => ['name' => 'North Dakota Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.hhs.nd.gov/healthcare/medicaid'],
            'OH' => ['name' => 'Ohio Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://medicaid.ohio.gov/'],
            'OK' => ['name' => 'Oklahoma Medicaid (OHCA)', 'electronic_payer_id' => '12B57', 'website' => 'https://oklahoma.gov/ohca.html'],
            'OR' => ['name' => 'Oregon Health Plan (Medicaid)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.oregon.gov/oha/HSD/OHP/Pages/index.aspx'],
            'PA' => ['name' => 'Pennsylvania Medicaid (DHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.dhs.pa.gov/Services/Assistance/Pages/Medical-Assistance.aspx'],
            'PR' => ['name' => 'Puerto Rico Medicaid (ASES)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.asespr.org/'],
            'RI' => ['name' => 'Rhode Island Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://eohhs.ri.gov/medicaid'],
            'SC' => ['name' => 'South Carolina Medicaid (SCDHHS)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.scdhhs.gov/'],
            'SD' => ['name' => 'South Dakota Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://dss.sd.gov/medicaid/'],
            'TN' => ['name' => 'TennCare (Tennessee Medicaid)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.tn.gov/tenncare.html'],
            'TX' => ['name' => 'Texas Medicaid (HHSC)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.hhs.texas.gov/services/health/medicaid-chip'],
            'UT' => ['name' => 'Utah Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://medicaid.utah.gov/'],
            'VT' => ['name' => 'Vermont Medicaid (DVHA)', 'electronic_payer_id' => '12B57', 'website' => 'https://dvha.vermont.gov/'],
            'VI' => ['name' => 'U.S. Virgin Islands Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://dhs.gov.vi/'],
            'VA' => ['name' => 'Virginia Medicaid (DMAS)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.dmas.virginia.gov/'],
            'WA' => ['name' => 'Apple Health (Washington Medicaid)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.hca.wa.gov/free-or-low-cost-health-care/apple-health-medicaid-coverage'],
            'WV' => ['name' => 'West Virginia Medicaid (BMS)', 'electronic_payer_id' => '12B57', 'website' => 'https://bms.wv.gov/'],
            'WI' => ['name' => 'Wisconsin Medicaid (ForwardHealth)', 'electronic_payer_id' => '12B57', 'website' => 'https://www.dhs.wisconsin.gov/medicaid/index.htm'],
            'WY' => ['name' => 'Wyoming Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://health.wyo.gov/healthcarefin/medicaid/'],
            'AS' => ['name' => 'American Samoa Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.medicaid.gov/state-overviews/stateprofile.html?state=american-samoa'],
            'GU' => ['name' => 'Guam Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.medicaid.gov/state-overviews/stateprofile.html?state=guam'],
            'MP' => ['name' => 'Northern Mariana Islands Medicaid', 'electronic_payer_id' => '12B57', 'website' => 'https://www.medicaid.gov/state-overviews/stateprofile.html?state=northern-mariana-islands'],
        ];
    }

    public static function chipPrograms(): array
    {
        return [
            'AL' => ['name' => 'ALL Kids (Alabama CHIP)', 'electronic_payer_id' => '12B57'],
            'AK' => ['name' => 'Denali KidCare', 'electronic_payer_id' => 'SKAK0'],
            'AZ' => ['name' => 'KidsCare (Arizona CHIP)', 'electronic_payer_id' => '12X01'],
            'AR' => ['name' => 'ARKids (Arkansas CHIP)', 'electronic_payer_id' => '12043'],
            'CA' => ['name' => 'California CHIP (Medi-Cal CHDP)', 'electronic_payer_id' => '57106'],
            'CO' => ['name' => 'Child Health Plan Plus (CHP+)', 'electronic_payer_id' => '12K04'],
            'CT' => ['name' => 'HUSKY B (Connecticut CHIP)', 'electronic_payer_id' => '12K02'],
            'DE' => ['name' => 'Delaware CHIP', 'electronic_payer_id' => '12K01'],
            'DC' => ['name' => 'DC Healthy Families', 'electronic_payer_id' => '12705'],
            'FL' => ['name' => 'Florida KidCare', 'electronic_payer_id' => '77027'],
            'GA' => ['name' => 'PeachCare for Kids', 'electronic_payer_id' => '77034'],
            'HI' => ['name' => 'Hawaii QUEST Integration (CHIP)', 'electronic_payer_id' => '12K06'],
            'ID' => ['name' => 'Idaho CHIP', 'electronic_payer_id' => '12705'],
            'IL' => ['name' => 'All Kids (Illinois CHIP)', 'electronic_payer_id' => '37127'],
            'IN' => ['name' => 'Hoosier Healthwise (CHIP)', 'electronic_payer_id' => '12B57'],
            'IA' => ['name' => 'Iowa hawk-i', 'electronic_payer_id' => '12B57'],
            'KS' => ['name' => 'KanCare CHIP', 'electronic_payer_id' => '12B57'],
            'KY' => ['name' => 'Kentucky CHIP (KCHIP)', 'electronic_payer_id' => '12B57'],
            'LA' => ['name' => 'Louisiana CHIP (LaCHIP)', 'electronic_payer_id' => '12B57'],
            'ME' => ['name' => 'MaineCare CHIP', 'electronic_payer_id' => '12B57'],
            'MD' => ['name' => 'Maryland Children\'s Health Program', 'electronic_payer_id' => '12B57'],
            'MA' => ['name' => 'MassHealth CHIP', 'electronic_payer_id' => '12B57'],
            'MI' => ['name' => 'MIChild / Healthy Kids', 'electronic_payer_id' => '12B57'],
            'MN' => ['name' => 'MinnesotaCare (CHIP)', 'electronic_payer_id' => '12B57'],
            'MS' => ['name' => 'Mississippi CHIP', 'electronic_payer_id' => '12B57'],
            'MO' => ['name' => 'MO HealthNet for Kids', 'electronic_payer_id' => '12B57'],
            'MT' => ['name' => 'Healthy Montana Kids', 'electronic_payer_id' => '12B57'],
            'NE' => ['name' => 'Nebraska CHIP', 'electronic_payer_id' => '12B57'],
            'NV' => ['name' => 'Nevada Check Up', 'electronic_payer_id' => '12B57'],
            'NH' => ['name' => 'New Hampshire CHIP', 'electronic_payer_id' => '12B57'],
            'NJ' => ['name' => 'NJ FamilyCare (CHIP)', 'electronic_payer_id' => '12B57'],
            'NM' => ['name' => 'New Mexico CHIP', 'electronic_payer_id' => '12B57'],
            'NY' => ['name' => 'New York Child Health Plus', 'electronic_payer_id' => '12B57'],
            'NC' => ['name' => 'North Carolina Health Choice', 'electronic_payer_id' => '12B57'],
            'ND' => ['name' => 'North Dakota CHIP', 'electronic_payer_id' => '12B57'],
            'OH' => ['name' => 'Ohio Healthy Start / Healthy Families', 'electronic_payer_id' => '12B57'],
            'OK' => ['name' => 'SoonerCare CHIP', 'electronic_payer_id' => '12B57'],
            'OR' => ['name' => 'Oregon Health Plan (CHIP)', 'electronic_payer_id' => '12B57'],
            'PA' => ['name' => 'Pennsylvania CHIP', 'electronic_payer_id' => '12B57'],
            'PR' => ['name' => 'Puerto Rico CHIP', 'electronic_payer_id' => '12B57'],
            'RI' => ['name' => 'Rhode Island RIte Care (CHIP)', 'electronic_payer_id' => '12B57'],
            'SC' => ['name' => 'South Carolina CHIP', 'electronic_payer_id' => '12B57'],
            'SD' => ['name' => 'South Dakota CHIP', 'electronic_payer_id' => '12B57'],
            'TN' => ['name' => 'CoverKids (Tennessee CHIP)', 'electronic_payer_id' => '12B57'],
            'TX' => ['name' => 'Texas CHIP', 'electronic_payer_id' => '12B57'],
            'UT' => ['name' => 'Utah CHIP', 'electronic_payer_id' => '12B57'],
            'VT' => ['name' => 'Vermont Dr. Dynasaur', 'electronic_payer_id' => '12B57'],
            'VI' => ['name' => 'U.S. Virgin Islands CHIP', 'electronic_payer_id' => '12B57'],
            'VA' => ['name' => 'Virginia FAMIS (CHIP)', 'electronic_payer_id' => '12B57'],
            'WA' => ['name' => 'Apple Health for Kids', 'electronic_payer_id' => '12B57'],
            'WV' => ['name' => 'West Virginia CHIP', 'electronic_payer_id' => '12B57'],
            'WI' => ['name' => 'BadgerCare Plus (CHIP)', 'electronic_payer_id' => '12B57'],
            'WY' => ['name' => 'Wyoming Kid Care CHIP', 'electronic_payer_id' => '12B57'],
            'AS' => ['name' => 'American Samoa CHIP', 'electronic_payer_id' => '12B57'],
            'GU' => ['name' => 'Guam CHIP', 'electronic_payer_id' => '12B57'],
            'MP' => ['name' => 'Northern Mariana Islands CHIP', 'electronic_payer_id' => '12B57'],
        ];
    }

    public static function marketplaceByState(): array
    {
        $stateBased = ['CA', 'CO', 'CT', 'DC', 'ID', 'KY', 'ME', 'MD', 'MA', 'MN', 'NV', 'NJ', 'NM', 'NY', 'PA', 'RI', 'VT', 'VA', 'WA'];

        $states = array_column(self::states(), 'code');
        $result = [];
        foreach ($states as $code) {
            $result[$code] = [
                'name' => in_array($code, $stateBased, true)
                    ? $code.' State-Based Marketplace (SBE)'
                    : $code.' Federally-Facilitated Marketplace (FFM)',
                'ownership' => in_array($code, $stateBased, true) ? 'public' : 'public',
                'plan_type' => in_array($code, $stateBased, true) ? 'state_based_exchange' : 'federally_facilitated_exchange',
                'website' => in_array($code, $stateBased, true)
                    ? 'https://www.cms.gov/marketplace/states/state-based-marketplaces'
                    : 'https://www.healthcare.gov/',
            ];
        }

        return $result;
    }

    public static function commercialPayers(): array
    {
        return [
            ['code' => 'BCBS-NATIONAL', 'name' => 'Blue Cross Blue Shield Association (National)', 'ownership' => 'nonprofit', 'electronic_payer_id' => 'BCBS', 'plan_type' => 'commercial_ppo'],
            ['code' => 'UHC-NATIONAL', 'name' => 'UnitedHealthcare', 'ownership' => 'private', 'electronic_payer_id' => '87726', 'plan_type' => 'commercial_ppo'],
            ['code' => 'AETNA-NATIONAL', 'name' => 'Aetna (CVS Health)', 'ownership' => 'private', 'electronic_payer_id' => '60054', 'plan_type' => 'commercial_ppo'],
            ['code' => 'CIGNA-NATIONAL', 'name' => 'Cigna Healthcare', 'ownership' => 'private', 'electronic_payer_id' => '62308', 'plan_type' => 'commercial_ppo'],
            ['code' => 'HUMANA-NATIONAL', 'name' => 'Humana Inc.', 'ownership' => 'private', 'electronic_payer_id' => '61101', 'plan_type' => 'commercial_hmo'],
            ['code' => 'ANTHEM-NATIONAL', 'name' => 'Elevance Health (Anthem)', 'ownership' => 'private', 'electronic_payer_id' => '00834', 'plan_type' => 'commercial_ppo'],
            ['code' => 'KAISER-NATIONAL', 'name' => 'Kaiser Permanente', 'ownership' => 'nonprofit', 'electronic_payer_id' => '91051', 'plan_type' => 'commercial_hmo'],
            ['code' => 'MOLINA-NATIONAL', 'name' => 'Molina Healthcare', 'ownership' => 'private', 'electronic_payer_id' => '38333', 'plan_type' => 'managed_medicaid_mco'],
            ['code' => 'CENTENE-NATIONAL', 'name' => 'Centene Corporation', 'ownership' => 'private', 'electronic_payer_id' => '68069', 'plan_type' => 'managed_medicaid_mco'],
        ];
    }

    public static function federalPrograms(): array
    {
        return [
            ['code' => 'MEDICARE-FFS', 'name' => 'Medicare Fee-for-Service (Part A & B)', 'program' => 'medicare', 'ownership' => 'public', 'electronic_payer_id' => 'CMS', 'plan_type' => 'medicare_ffs', 'notes' => 'Route claims to the A/B MAC for the provider state of service.'],
            ['code' => 'MEDICARE-PART-D', 'name' => 'Medicare Part D (PDP)', 'program' => 'medicare', 'ownership' => 'public', 'electronic_payer_id' => 'PARTD', 'plan_type' => 'medicare_part_d', 'notes' => 'Pharmacy claims; use plan-specific payer ID for each PDP sponsor.'],
            ['code' => 'MEDICARE-ADVANTAGE', 'name' => 'Medicare Advantage (Part C)', 'program' => 'medicare_advantage', 'ownership' => 'private', 'electronic_payer_id' => 'MA', 'plan_type' => 'medicare_advantage', 'notes' => 'Use CMS contract/plan ID (H####) for each MA plan.'],
            ['code' => 'TRICARE', 'name' => 'TRICARE (DoD)', 'program' => 'tricare', 'ownership' => 'public', 'electronic_payer_id' => '99726', 'plan_type' => 'tricare', 'website' => 'https://www.tricare.mil/'],
            ['code' => 'VA-CCN', 'name' => 'VA Community Care Network', 'program' => 'va', 'ownership' => 'public', 'electronic_payer_id' => '84146', 'plan_type' => 'va_ccn', 'website' => 'https://www.va.gov/health-care/'],
            ['code' => 'CHAMPVA', 'name' => 'CHAMPVA', 'program' => 'va', 'ownership' => 'public', 'electronic_payer_id' => '84146', 'plan_type' => 'champva'],
            ['code' => 'IHS', 'name' => 'Indian Health Service', 'program' => 'ihs', 'ownership' => 'public', 'electronic_payer_id' => 'IHS01', 'plan_type' => 'ihs', 'website' => 'https://www.ihs.gov/'],
        ];
    }

    public static function taxonomyCodes(): array
    {
        return [
            ['code' => '207Q00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Family Medicine', 'specialization' => null, 'definition' => 'Family Medicine Physician'],
            ['code' => '207R00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Internal Medicine', 'specialization' => null, 'definition' => 'Internal Medicine Physician'],
            ['code' => '208D00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'General Practice', 'specialization' => null, 'definition' => 'General Practice Physician'],
            ['code' => '208M00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Hospitalist', 'specialization' => null, 'definition' => 'Hospitalist Physician'],
            ['code' => '207V00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Obstetrics & Gynecology', 'specialization' => null, 'definition' => 'Obstetrics & Gynecology Physician'],
            ['code' => '208000000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Pediatrics', 'specialization' => null, 'definition' => 'Pediatrics Physician'],
            ['code' => '207L00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Anesthesiology', 'specialization' => null, 'definition' => 'Anesthesiology Physician'],
            ['code' => '207T00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Neurological Surgery', 'specialization' => null, 'definition' => 'Neurological Surgery Physician'],
            ['code' => '207X00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Orthopaedic Surgery', 'specialization' => null, 'definition' => 'Orthopaedic Surgery Physician'],
            ['code' => '207Y00000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Otolaryngology', 'specialization' => null, 'definition' => 'Otolaryngology Physician'],
            ['code' => '207ZP0101X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Pathology', 'specialization' => 'Anatomic Pathology', 'definition' => 'Anatomic Pathology Physician'],
            ['code' => '208600000X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Surgery', 'specialization' => null, 'definition' => 'Surgery Physician'],
            ['code' => '2084P0800X', 'grouping' => 'Allopathic & Osteopathic Physicians', 'classification' => 'Psychiatry & Neurology', 'specialization' => 'Psychiatry', 'definition' => 'Psychiatry Physician'],
            ['code' => '363A00000X', 'grouping' => 'Physician Assistants & Advanced Practice Nursing Providers', 'classification' => 'Physician Assistant', 'specialization' => null, 'definition' => 'Physician Assistant'],
            ['code' => '363LF0000X', 'grouping' => 'Physician Assistants & Advanced Practice Nursing Providers', 'classification' => 'Nurse Practitioner', 'specialization' => 'Family', 'definition' => 'Family Nurse Practitioner'],
            ['code' => '363LP2300X', 'grouping' => 'Physician Assistants & Advanced Practice Nursing Providers', 'classification' => 'Nurse Practitioner', 'specialization' => 'Primary Care', 'definition' => 'Primary Care Nurse Practitioner'],
            ['code' => '225100000X', 'grouping' => 'Respiratory, Rehabilitative & Restorative Service Providers', 'classification' => 'Physical Therapist', 'specialization' => null, 'definition' => 'Physical Therapist'],
            ['code' => '225400000X', 'grouping' => 'Respiratory, Rehabilitative & Restorative Service Providers', 'classification' => 'Rehabilitation Practitioner', 'specialization' => null, 'definition' => 'Rehabilitation Practitioner'],
            ['code' => '235Z00000X', 'grouping' => 'Respiratory, Rehabilitative & Restorative Service Providers', 'classification' => 'Speech-Language Pathologist', 'specialization' => null, 'definition' => 'Speech-Language Pathologist'],
            ['code' => '122300000X', 'grouping' => 'Dental Providers', 'classification' => 'Dentist', 'specialization' => null, 'definition' => 'Dentist'],
            ['code' => '152W00000X', 'grouping' => 'Eye & Vision Services Providers', 'classification' => 'Optometrist', 'specialization' => null, 'definition' => 'Optometrist'],
            ['code' => '213E00000X', 'grouping' => 'Podiatric Medicine & Surgery Service Providers', 'classification' => 'Podiatrist', 'specialization' => null, 'definition' => 'Podiatrist'],
            ['code' => '261QM1300X', 'grouping' => 'Ambulatory Health Care Facilities', 'classification' => 'Clinic/Center', 'specialization' => 'Multi-Specialty', 'definition' => 'Multi-Specialty Clinic/Center'],
            ['code' => '261QR1300X', 'grouping' => 'Ambulatory Health Care Facilities', 'classification' => 'Clinic/Center', 'specialization' => 'Rural Health', 'definition' => 'Rural Health Clinic/Center'],
            ['code' => '282N00000X', 'grouping' => 'Hospitals', 'classification' => 'General Acute Care Hospital', 'specialization' => null, 'definition' => 'General Acute Care Hospital'],
            ['code' => '283X00000X', 'grouping' => 'Hospitals', 'classification' => 'Rehabilitation Hospital', 'specialization' => null, 'definition' => 'Rehabilitation Hospital'],
            ['code' => '314000000X', 'grouping' => 'Nursing & Custodial Care Facilities', 'classification' => 'Skilled Nursing Facility', 'specialization' => null, 'definition' => 'Skilled Nursing Facility'],
            ['code' => '333600000X', 'grouping' => 'Suppliers', 'classification' => 'Pharmacy', 'specialization' => null, 'definition' => 'Pharmacy Supplier'],
            ['code' => '332B00000X', 'grouping' => 'Suppliers', 'classification' => 'Durable Medical Equipment & Medical Supplies', 'specialization' => null, 'definition' => 'DME Supplier'],
        ];
    }
}
