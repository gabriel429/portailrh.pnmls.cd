<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\AgentCardSetting;
use App\Models\AgentIdCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentCardController extends ApiController
{
    public function settings(): JsonResponse
    {
        return $this->success($this->settingsPayload());
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'nullable|string|max:180',
            'ministry' => 'nullable|string|max:220',
            'program_name' => 'nullable|string|max:220',
            'card_title' => 'nullable|string|max:140',
            'subtitle' => 'nullable|string|max:140',
            'authority_title' => 'nullable|string|max:180',
            'signature_name' => 'nullable|string|max:180',
            'contact_line' => 'nullable|string|max:220',
            'footer_note' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'logo_primary' => 'nullable|image|max:2048',
            'logo_secondary' => 'nullable|image|max:2048',
        ]);

        foreach (array_keys(AgentCardSetting::DEFAULTS) as $key) {
            if (str_ends_with($key, '_path')) {
                continue;
            }

            if (array_key_exists($key, $validated)) {
                AgentCardSetting::setValue($key, $validated[$key]);
            }
        }

        foreach (['logo_primary' => 'logo_primary_path', 'logo_secondary' => 'logo_secondary_path'] as $input => $settingKey) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $previous = AgentCardSetting::values()[$settingKey] ?? '';
            if ($previous) {
                Storage::disk('public')->delete($previous);
            }

            $path = $request->file($input)->store('agent-cards/settings', 'public');
            AgentCardSetting::setValue($settingKey, $path);
        }

        return $this->success($this->settingsPayload(), [], [
            'message' => 'Modele de carte mis a jour.',
        ]);
    }

    public function current(Request $request, Agent $agent): JsonResponse
    {
        $agent = $this->loadAgent($agent);
        $card = $agent->idCards()
            ->whereNull('revoked_at')
            ->latest('issued_at')
            ->first();

        return $this->success([
            'agent' => AgentResource::make($agent)->resolve(),
            'card' => $card ? $this->cardPayload($card) : null,
            'settings' => $this->settingsPayload(),
        ]);
    }

    public function issue(Request $request, Agent $agent): JsonResponse
    {
        $agent = $this->loadAgent($agent);
        $renew = $request->boolean('renew', false);

        $activeCard = $agent->idCards()
            ->whereNull('revoked_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->latest('issued_at')
            ->first();

        if ($activeCard && ! $renew) {
            return $this->success([
                'agent' => AgentResource::make($agent)->resolve(),
                'card' => $this->cardPayload($activeCard),
                'settings' => $this->settingsPayload(),
            ], [], [
                'message' => 'Une carte valide existe deja pour cet agent.',
            ]);
        }

        if ($activeCard) {
            $activeCard->forceFill(['revoked_at' => now()])->save();
        }

        $issuedAt = now()->toDateString();
        $card = AgentIdCard::create([
            'agent_id' => $agent->id,
            'issued_by' => $request->user()?->id,
            'serial' => $this->nextSerial($agent),
            'token' => $this->makeToken(),
            'issued_at' => $issuedAt,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        return $this->success([
            'agent' => AgentResource::make($agent)->resolve(),
            'card' => $this->cardPayload($card),
            'settings' => $this->settingsPayload(),
        ], [], [
            'message' => 'Carte agent generee avec succes.',
        ], 201);
    }

    public function verify(string $token): JsonResponse
    {
        $card = AgentIdCard::query()
            ->where('token', $token)
            ->with(['agent.role', 'agent.province', 'agent.departement', 'agent.grade', 'agent.institution', 'issuer'])
            ->firstOrFail();

        return $this->success([
            'card' => $this->cardPayload($card),
            'agent' => AgentResource::make($card->agent)->resolve(),
            'settings' => $this->settingsPayload(),
        ]);
    }

    private function loadAgent(Agent $agent): Agent
    {
        return $agent->load(['role', 'province', 'departement', 'grade', 'institution']);
    }

    private function settingsPayload(): array
    {
        $settings = AgentCardSetting::values();

        foreach (['logo_primary_path', 'logo_secondary_path'] as $key) {
            $settings[str_replace('_path', '_url', $key)] = $settings[$key]
                ? Storage::disk('public')->url($settings[$key])
                : null;
        }

        if (! $settings['logo_primary_url'] && is_file(public_path('images/logo-pnmls.png'))) {
            $settings['logo_primary_url'] = asset('images/logo-pnmls.png');
        }

        return $settings;
    }

    private function cardPayload(AgentIdCard $card): array
    {
        return [
            'id' => $card->id,
            'serial' => $card->serial,
            'token' => $card->token,
            'issued_at' => optional($card->issued_at)?->toDateString(),
            'expires_at' => optional($card->expires_at)?->toDateString(),
            'revoked_at' => optional($card->revoked_at)?->toIso8601String(),
            'status' => $card->status,
            'verification_url' => url('/agent-cards/verify/' . $card->token),
            'issued_by' => $card->relationLoaded('issuer') && $card->issuer ? [
                'id' => $card->issuer->id,
                'name' => $card->issuer->name,
                'email' => $card->issuer->email,
            ] : null,
        ];
    }

    private function makeToken(): string
    {
        do {
            $token = Str::random(32);
        } while (AgentIdCard::query()->where('token', $token)->exists());

        return $token;
    }

    private function nextSerial(Agent $agent): string
    {
        do {
            $serial = sprintf('PNMLS-CI-%s-%04d-%s', now()->format('Y'), $agent->id, Str::upper(Str::random(5)));
        } while (AgentIdCard::query()->where('serial', $serial)->exists());

        return $serial;
    }
}
