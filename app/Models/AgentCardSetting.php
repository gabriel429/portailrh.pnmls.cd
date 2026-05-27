<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCardSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const DEFAULTS = [
        'country' => 'REPUBLIQUE DEMOCRATIQUE DU CONGO',
        'ministry' => 'Ministere de la Sante Publique, Hygiene et Prevention',
        'program_name' => 'Programme National Multisectoriel de Lutte contre le Sida',
        'card_title' => "CARTE DE L'AGENT PNMLS",
        'subtitle' => 'Personnel PNMLS',
        'authority_title' => 'Le Secretaire Executif National',
        'signature_name' => '',
        'contact_line' => 'PNMLS - Kinshasa, Republique Democratique du Congo',
        'footer_note' => 'Cette carte demeure la propriete du PNMLS et doit etre restituee en cas de cessation de service.',
        'primary_color' => '#0077B5',
        'accent_color' => '#f6c343',
        'logo_primary_path' => '',
        'logo_secondary_path' => '',
    ];

    public static function values(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();

        return array_merge(static::DEFAULTS, $stored);
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
