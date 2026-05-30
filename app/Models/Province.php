<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    private const CANONICAL_NAMES = [
        'BAS UELE' => 'Bas-Uele',
        'BAS-UELE' => 'Bas-Uele',
        'EQUATEUR' => 'Equateur',
        'ÉQUATEUR' => 'Equateur',
        'HAUT KATANGA' => 'Haut-Katanga',
        'HAUT-KATANGA' => 'Haut-Katanga',
        'HAUT LOMAMI' => 'Haut-Lomami',
        'HAUT-LOMAMI' => 'Haut-Lomami',
        'HAUT UELE' => 'Haut-Uele',
        'HAUT-UELE' => 'Haut-Uele',
        'ITURI' => 'Ituri',
        'KASAI' => 'Kasai',
        'KASAÏ' => 'Kasai',
        'KASAI CENTRAL' => 'Kasai Central',
        'KASAI-CENTRAL' => 'Kasai Central',
        'KASAÏ CENTRAL' => 'Kasai Central',
        'KASAÏ-CENTRAL' => 'Kasai Central',
        'KASAI ORIENTAL' => 'Kasai Oriental',
        'KASAI-ORIENTAL' => 'Kasai Oriental',
        'KASAÏ ORIENTAL' => 'Kasai Oriental',
        'KASAÏ-ORIENTAL' => 'Kasai Oriental',
        'KINSHASA' => 'Kinshasa',
        'KONGO CENTRAL' => 'Kongo Central',
        'KONGO-CENTRAL' => 'Kongo Central',
        'KWANGO' => 'Kwango',
        'KWILU' => 'Kwilu',
        'LOMAMI' => 'Lomami',
        'LUALABA' => 'Lualaba',
        'MAI NDOMBE' => 'Mai-Ndombe',
        'MAI-NDOMBE' => 'Mai-Ndombe',
        'MAÏ NDOMBE' => 'Mai-Ndombe',
        'MAÏ-NDOMBE' => 'Mai-Ndombe',
        'MANIEMA' => 'Maniema',
        'MONGALA' => 'Mongala',
        'NORD KIVU' => 'Nord-Kivu',
        'NORD-KIVU' => 'Nord-Kivu',
        'NORD UBANGI' => 'Nord-Ubangi',
        'NORD-UBANGI' => 'Nord-Ubangi',
        'SANKURU' => 'Sankuru',
        'SUD KIVU' => 'Sud-Kivu',
        'SUD-KIVU' => 'Sud-Kivu',
        'SUD UBANGI' => 'Sud-Ubangi',
        'SUD-UBANGI' => 'Sud-Ubangi',
        'TANGANYIKA' => 'Tanganyika',
        'TSHOPO' => 'Tshopo',
        'TSHUAPA' => 'Tshuapa',
    ];

    protected $fillable = [
        'code',
        'nom',
        'description',
        'ville_secretariat',
        'adresse',
        'nom_gouverneur',
        'nom_secretariat_executif',
        'email_officiel',
        'telephone_officiel',
    ];

    public static function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $name = trim(preg_replace('/\s+/', ' ', $value));
        if ($name === '') {
            return $name;
        }

        $key = strtoupper(str_replace(['-', '_'], ' ', $name));
        $key = trim(preg_replace('/\s+/', ' ', $key));

        return self::CANONICAL_NAMES[$key]
            ?? self::CANONICAL_NAMES[str_replace(' ', '-', $key)]
            ?? str_replace(' - ', '-', ucwords(strtolower(str_replace('-', ' - ', $name))));
    }

    public function getNomAttribute($value): ?string
    {
        return self::normalizeName($value);
    }

    public function setNomAttribute($value): void
    {
        $this->attributes['nom'] = self::normalizeName($value);
    }

    // Relations HasMany
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function localites(): HasMany
    {
        return $this->hasMany(Localite::class);
    }

    /** Affectations SEP liées à cette province */
    public function affectationsSep(): HasMany
    {
        return $this->hasMany(Affectation::class)->where('niveau_administratif', 'SEP');
    }

    // Scopes
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
