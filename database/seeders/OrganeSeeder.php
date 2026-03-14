<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organe;

class OrganeSeeder extends Seeder
{
    public function run(): void
    {
        Organe::create([
            'code' => 'SEN',
            'nom' => 'Secrétariat Exécutif National',
            'sigle' => 'SEN',
            'description' => 'Le niveau national du gouvernement',
            'actif' => true,
        ]);

        Organe::create([
            'code' => 'SEP',
            'nom' => 'Secrétariat Exécutif Provincial',
            'sigle' => 'SEP',
            'description' => 'Le niveau provincial du gouvernement',
            'actif' => true,
        ]);

        Organe::create([
            'code' => 'SEL',
            'nom' => 'Secrétariat Exécutif Local',
            'sigle' => 'SEL',
            'description' => 'Le niveau local du gouvernement',
            'actif' => true,
        ]);
    }
}
