<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $provinces = [
            ['code' => 'BUE', 'nom' => 'Bas-Uele', 'description' => 'Province du Bas-Uele'],
            ['code' => 'EQU', 'nom' => 'Equateur', 'description' => 'Province de l Equateur'],
            ['code' => 'HAU', 'nom' => 'Haut-Katanga', 'description' => 'Province du Haut-Katanga'],
            ['code' => 'HLO', 'nom' => 'Haut-Lomami', 'description' => 'Province du Haut-Lomami'],
            ['code' => 'HUE', 'nom' => 'Haut-Uele', 'description' => 'Province du Haut-Uele'],
            ['code' => 'ITU', 'nom' => 'Ituri', 'description' => 'Province de l Ituri'],
            ['code' => 'KAS', 'nom' => 'Kasai', 'description' => 'Province du Kasai'],
            ['code' => 'KCE', 'nom' => 'Kasai Central', 'description' => 'Province du Kasai Central'],
            ['code' => 'KOR', 'nom' => 'Kasai Oriental', 'description' => 'Province du Kasai Oriental'],
            ['code' => 'KIN', 'nom' => 'Kinshasa', 'description' => 'Province de Kinshasa - Capitale'],
            ['code' => 'KOC', 'nom' => 'Kongo Central', 'description' => 'Province du Kongo Central'],
            ['code' => 'KWA', 'nom' => 'Kwango', 'description' => 'Province du Kwango'],
            ['code' => 'KWI', 'nom' => 'Kwilu', 'description' => 'Province du Kwilu'],
            ['code' => 'LOM', 'nom' => 'Lomami', 'description' => 'Province de Lomami'],
            ['code' => 'LOF', 'nom' => 'Lualaba', 'description' => 'Province de Lualaba'],
            ['code' => 'MND', 'nom' => 'Mai-Ndombe', 'description' => 'Province du Mai-Ndombe'],
            ['code' => 'MAN', 'nom' => 'Maniema', 'description' => 'Province de Maniema'],
            ['code' => 'MON', 'nom' => 'Mongala', 'description' => 'Province de la Mongala'],
            ['code' => 'NOR', 'nom' => 'Nord-Kivu', 'description' => 'Province du Nord-Kivu'],
            ['code' => 'NUB', 'nom' => 'Nord-Ubangi', 'description' => 'Province du Nord-Ubangi'],
            ['code' => 'SAN', 'nom' => 'Sankuru', 'description' => 'Province du Sankuru'],
            ['code' => 'SUD', 'nom' => 'Sud-Kivu', 'description' => 'Province du Sud-Kivu'],
            ['code' => 'SUB', 'nom' => 'Sud-Ubangi', 'description' => 'Province du Sud-Ubangi'],
            ['code' => 'TAN', 'nom' => 'Tanganyika', 'description' => 'Province de Tanganyika'],
            ['code' => 'TSH', 'nom' => 'Tshopo', 'description' => 'Province de la Tshopo'],
            ['code' => 'TUA', 'nom' => 'Tshuapa', 'description' => 'Province de la Tshuapa'],
        ];

        foreach ($provinces as $province) {
            $existing = DB::table('provinces')
                ->where('code', $province['code'])
                ->orWhereRaw('LOWER(nom) = ?', [strtolower($province['nom'])])
                ->first();

            if ($existing) {
                DB::table('provinces')
                    ->where('id', $existing->id)
                    ->update([
                        'code' => $province['code'],
                        'nom' => $province['nom'],
                        'description' => $province['description'],
                        'updated_at' => $now,
                    ]);
                continue;
            }

            DB::table('provinces')->insert($province + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
