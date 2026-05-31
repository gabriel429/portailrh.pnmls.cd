<?php

namespace App\Http\Controllers\Api;

use App\Models\Fonction;
use App\Models\JobDescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class JobDescriptionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = JobDescription::with('fonction')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('titre', 'like', "%{$search}%")
                        ->orWhere('mission_principale', 'like', "%{$search}%")
                        ->orWhere('service_section_departement', 'like', "%{$search}%")
                        ->orWhereHas('fonction', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('fonction_id'), fn ($q) => $q->where('fonction_id', $request->integer('fonction_id')))
            ->when($request->filled('actif'), fn ($q) => $q->where('actif', filter_var($request->input('actif'), FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('updated_at')
            ->orderBy('titre');

        $paginator = $query->paginate($request->integer('per_page', 20));
        $paginator->getCollection()->transform(fn (JobDescription $jobDescription) => $jobDescription->toApiArray());

        return response()->json(array_merge($paginator->toArray(), [
            'summary' => $this->summary(),
        ]));
    }

    public function options(): JsonResponse
    {
        $fonctions = Fonction::withCount('jobDescriptions')
            ->orderInstitutionally()
            ->get()
            ->map(fn (Fonction $fonction) => [
                'id' => $fonction->id,
                'nom' => $fonction->nom,
                'niveau_administratif' => $fonction->niveau_administratif,
                'type_poste' => $fonction->type_poste,
                'job_descriptions_count' => $fonction->job_descriptions_count,
            ])
            ->values();

        return response()->json([
            'fonctions' => $fonctions,
            'summary' => $this->summary(),
        ]);
    }

    public function show(JobDescription $jobDescription): JsonResponse
    {
        return response()->json([
            'data' => $jobDescription->load('fonction')->toApiArray(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        $jobDescription = JobDescription::create($validated);

        return response()->json([
            'message' => 'Job Description créée avec succès.',
            'data' => $jobDescription->load('fonction')->toApiArray(),
            'summary' => $this->summary(),
        ], 201);
    }

    public function update(Request $request, JobDescription $jobDescription): JsonResponse
    {
        $validated = $this->validatedPayload($request, $jobDescription);
        $validated['updated_by'] = $request->user()?->id;

        $jobDescription->update($validated);

        return response()->json([
            'message' => 'Job Description mise à jour avec succès.',
            'data' => $jobDescription->fresh('fonction')->toApiArray(),
            'summary' => $this->summary(),
        ]);
    }

    public function destroy(JobDescription $jobDescription): JsonResponse
    {
        $jobDescription->delete();

        return response()->json([
            'message' => 'Job Description supprimée.',
            'summary' => $this->summary(),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $agent = $request->user()?->agent;

        if (!$agent) {
            return response()->json([
                'data' => [],
                'message' => 'Aucun agent lié à votre compte.',
            ]);
        }

        $descriptions = JobDescription::forAgent($agent)
            ->map(fn (JobDescription $jobDescription) => $jobDescription->toApiArray())
            ->values();

        return response()->json([
            'data' => $descriptions,
        ]);
    }

    private function validatedPayload(Request $request, ?JobDescription $jobDescription = null): array
    {
        $id = $jobDescription?->id;

        $validated = $request->validate([
            'fonction_id' => ['required', 'integer', 'exists:fonctions,id'],
            'titre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_descriptions')
                    ->where(fn ($query) => $query->where('fonction_id', $request->input('fonction_id')))
                    ->ignore($id),
            ],
            'mission_principale' => ['nullable', 'string'],
            'responsabilites_principales' => ['nullable', 'string'],
            'taches_specifiques' => ['nullable', 'string'],
            'competences_attendues' => ['nullable', 'string'],
            'service_section_departement' => ['nullable', 'string', 'max:255'],
            'actif' => ['nullable', 'boolean'],
        ]);

        $validated['actif'] = (bool) ($validated['actif'] ?? true);

        return $validated;
    }

    private function summary(): array
    {
        if (!Schema::hasTable('job_descriptions')) {
            return [
                'total' => 0,
                'actives' => 0,
                'fonctions_couvertes' => 0,
            ];
        }

        return [
            'total' => JobDescription::count(),
            'actives' => JobDescription::where('actif', true)->count(),
            'fonctions_couvertes' => JobDescription::query()->select('fonction_id')->distinct()->count('fonction_id'),
        ];
    }
}
