<?php

namespace Database\Seeders;

use App\Services\CmsReference\CmsReferenceImporter;
use Illuminate\Database\Seeder;

class CmsReferenceSeeder extends Seeder
{
    public function run(CmsReferenceImporter $importer): void
    {
        $importer->import(true);
    }
}
