<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_agent' => $this->id_agent,
            'matricule_etat' => $this->matricule_etat,
            'provenance_matricule' => $this->provenance_matricule,
            'nom' => $this->nom,
            'postnom' => $this->postnom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'email_prive' => $this->email_prive,
            'email_professionnel' => $this->email_professionnel,
            'telephone' => $this->telephone,
            'photo' => $this->photo,
            'sexe' => $this->sexe,
            'date_naissance' => optional($this->date_naissance)->toDateString(),
            'annee_naissance' => $this->annee_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'situation_familiale' => $this->situation_familiale,
            'nombre_enfants' => $this->nombre_enfants,
            'adresse' => $this->adresse,
            'organe' => $this->organe,
            'fonction' => $this->fonction,
            'poste_actuel' => $this->poste_actuel,
            'grade_etat' => $this->grade_etat,
            'niveau_etudes' => $this->niveau_etudes,
            'domaine_etudes' => $this->domaine_etudes,
            'annee_engagement_programme' => $this->annee_engagement_programme,
            'anciennete' => $this->annee_engagement_programme ? now()->year - $this->annee_engagement_programme : null,
            'date_embauche' => optional($this->date_embauche)->toDateString(),
            'statut' => $this->statut,
            'institution_id' => $this->institution_id,
            'grade_id' => $this->grade_id,
            'departement_id' => $this->departement_id,
            'province_id' => $this->province_id,
            'section_id' => $this->section_id,
            'province' => $this->whenLoaded('province', fn () => [
                'id' => $this->province->id,
                'nom' => $this->province->nom_province ?? $this->province->nom,
            ]),
            'departement' => $this->whenLoaded('departement', fn () => [
                'id' => $this->departement->id,
                'nom' => $this->departement->nom,
            ]),
            'grade' => $this->whenLoaded('grade', fn () => [
                'id' => $this->grade->id,
                'libelle' => $this->grade->libelle,
            ]),
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution->id,
                'nom' => $this->institution->nom,
            ]),
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'nom_role' => $this->role->nom_role,
            ]),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->type,
                'description' => $document->description,
                'statut' => $document->statut,
                'fichier' => $document->fichier,
            ])->values()),
            'requests' => $this->whenLoaded('requests', fn () => $this->requests->map(fn ($requestModel) => [
                'id' => $requestModel->id,
                'type' => $requestModel->type,
                'description' => $requestModel->description,
                'statut' => $requestModel->statut,
                'date_debut' => optional($requestModel->date_debut)->toDateString(),
                'date_fin' => optional($requestModel->date_fin)->toDateString(),
            ])->values()),
            'affectations' => $this->whenLoaded('affectations', fn () => $this->affectations->map(fn ($affectation) => [
                'id' => $affectation->id,
                'actif' => (bool) $affectation->actif,
                'niveau_administratif' => $affectation->niveau_administratif,
                'date_debut' => optional($affectation->date_debut)->toDateString(),
                'date_fin' => optional($affectation->date_fin)->toDateString(),
                'fonction' => $affectation->relationLoaded('fonction') && $affectation->fonction ? [
                    'id' => $affectation->fonction->id,
                    'nom' => $affectation->fonction->nom,
                ] : null,
                'department' => $affectation->relationLoaded('department') && $affectation->department ? [
                    'id' => $affectation->department->id,
                    'nom' => $affectation->department->nom,
                ] : null,
                'province' => $affectation->relationLoaded('province') && $affectation->province ? [
                    'id' => $affectation->province->id,
                    'nom' => $affectation->province->nom_province ?? $affectation->province->nom,
                ] : null,
            ])->values()),
        ];
    }
}