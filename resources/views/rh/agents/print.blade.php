<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiche Agent - {{ $agent->prenom }} {{ $agent->nom }} - PNMLS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; font-size: 11pt; line-height: 1.4; }

        .page { max-width: 210mm; margin: 0 auto; padding: 15mm; }

        /* En-tete */
        .header { text-align: center; border-bottom: 3px solid #0077B5; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; color: #0077B5; margin-bottom: 2px; }
        .header h2 { font-size: 12pt; color: #666; font-weight: normal; }
        .header .org { font-size: 9pt; color: #999; margin-top: 5px; }

        /* Photo et identite */
        .identity { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .identity .photo { width: 90px; height: 90px; border-radius: 50%; background: #e5e5e5; display: flex; align-items: center; justify-content: center; font-size: 28pt; color: #999; flex-shrink: 0; overflow: hidden; }
        .identity .photo img { width: 100%; height: 100%; object-fit: cover; }
        .identity .info h3 { font-size: 14pt; margin-bottom: 4px; }
        .identity .info .matricule { font-size: 10pt; color: #0077B5; font-weight: bold; }
        .identity .info .badges { margin-top: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 8pt; font-weight: bold; margin-right: 4px; }
        .badge-blue { background: #0077B5; color: white; }
        .badge-green { background: #28a745; color: white; }
        .badge-yellow { background: #ffc107; color: #333; }
        .badge-gray { background: #6c757d; color: white; }

        /* Sections */
        .section { margin-bottom: 18px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #0077B5; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 10px; }

        /* Grid data */
        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .data-grid.three-cols { grid-template-columns: 1fr 1fr 1fr; }
        .data-item { padding: 5px 0; border-bottom: 1px dotted #eee; }
        .data-item .label { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-item .value { font-size: 10pt; font-weight: 500; }

        /* Timeline */
        .timeline { margin-left: 10px; }
        .timeline-entry { position: relative; padding-left: 20px; padding-bottom: 12px; border-left: 2px solid #ddd; }
        .timeline-entry:last-child { border-left-color: transparent; }
        .timeline-dot { position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; border-radius: 50%; background: #6c757d; }
        .timeline-dot.active { background: #28a745; }
        .timeline-entry .fonction { font-weight: bold; font-size: 10pt; }
        .timeline-entry .details { font-size: 9pt; color: #666; }
        .timeline-entry .dates { font-size: 8pt; color: #999; }

        /* Footer */
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8pt; color: #999; }

        /* Bouton imprimer (cache a l'impression) */
        .print-actions { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 8px 25px; background: #0077B5; color: white; border: none; border-radius: 4px; font-size: 11pt; cursor: pointer; margin-right: 10px; }
        .btn-print:hover { background: #005a87; }
        .btn-back { padding: 8px 25px; background: #6c757d; color: white; border: none; border-radius: 4px; font-size: 11pt; cursor: pointer; text-decoration: none; }

        @media print {
            .print-actions { display: none !important; }
            body { font-size: 10pt; }
            .page { padding: 10mm; }
        }
    </style>
</head>
<body>
    <div class="page">

        {{-- Boutons d'action --}}
        <div class="print-actions">
            <button class="btn-print" onclick="window.print();">
                Imprimer cette fiche
            </button>
            <a href="{{ route('rh.agents.show', $agent) }}" class="btn-back">Retour</a>
        </div>

        {{-- En-tete --}}
        <div class="header">
            <img src="{{ asset('images/logo-pnmls.png') }}" alt="Logo PNMLS" style="height: 70px; margin-bottom: 10px;">
            <h1>PROGRAMME NATIONAL MULTISECTORIEL DE LUTTE CONTRE LE SIDA</h1>
            <h2>Fiche Individuelle de l'Agent</h2>
            <p class="org">Republique Democratique du Congo</p>
        </div>

        {{-- Identite --}}
        <div class="identity">
            <div class="photo">
                @if($agent->photo)
                    <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}">
                @else
                    &#128100;
                @endif
            </div>
            <div class="info">
                <h3>{{ $agent->prenom }} {{ $agent->postnom ?? '' }} {{ $agent->nom }}</h3>
                <div class="matricule">{{ $agent->id_agent }}</div>
                <div class="badges">
                    @if($agent->organe)
                        <span class="badge badge-blue">{{ $agent->organe }}</span>
                    @endif
                    @if($agent->statut === 'actif')
                        <span class="badge badge-green">Actif</span>
                    @elseif($agent->statut === 'suspendu')
                        <span class="badge badge-yellow">Suspendu</span>
                    @else
                        <span class="badge badge-gray">{{ ucfirst($agent->statut) }}</span>
                    @endif
                    @if($agent->role)
                        <span class="badge badge-blue">{{ $agent->role->nom_role }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Informations personnelles --}}
        <div class="section">
            <div class="section-title">Informations Personnelles</div>
            <div class="data-grid three-cols">
                <div class="data-item">
                    <div class="label">Prenom</div>
                    <div class="value">{{ $agent->prenom }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Post-nom</div>
                    <div class="value">{{ $agent->postnom ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Nom</div>
                    <div class="value">{{ $agent->nom }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Sexe</div>
                    <div class="value">{{ $agent->sexe === 'M' ? 'Masculin' : ($agent->sexe === 'F' ? 'Féminin' : ($agent->sexe ?? 'N/A')) }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Date de naissance</div>
                    <div class="value">{{ $agent->date_naissance?->format('d/m/Y') ?? ($agent->annee_naissance ?? 'N/A') }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Lieu de naissance</div>
                    <div class="value">{{ $agent->lieu_naissance ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Situation familiale</div>
                    <div class="value">{{ $agent->situation_familiale ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Nombre d'enfants</div>
                    <div class="value">{{ $agent->nombre_enfants ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Telephone</div>
                    <div class="value">{{ $agent->telephone ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Email</div>
                    <div class="value">{{ $agent->email }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Email prive</div>
                    <div class="value">{{ $agent->email_prive ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Email professionnel</div>
                    <div class="value">{{ $agent->email_professionnel ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="data-grid" style="margin-top: 2px;">
                <div class="data-item" style="grid-column: span 2;">
                    <div class="label">Adresse</div>
                    <div class="value">{{ $agent->adresse ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Informations professionnelles --}}
        <div class="section">
            <div class="section-title">Informations Professionnelles</div>
            <div class="data-grid">
                <div class="data-item">
                    <div class="label">Organe</div>
                    <div class="value">{{ $agent->organe ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Fonction</div>
                    <div class="value">{{ $agent->fonction ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Poste actuel</div>
                    <div class="value">{{ $agent->poste_actuel ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Annee engagement programme</div>
                    <div class="value">{{ $agent->annee_engagement_programme ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Matricule de l'Etat</div>
                    <div class="value">{{ $agent->matricule_etat ?? 'N.U.' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Provenance matricule</div>
                    <div class="value">{{ $agent->institution?->nom ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Grade de l'Etat</div>
                    <div class="value">{{ $agent->grade?->libelle ?? 'N.U.' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Niveau d'études</div>
                    <div class="value">{{ $agent->niveau_etudes ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Domaine d'études</div>
                    <div class="value">{{ $agent->domaine_etudes ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Province</div>
                    <div class="value">{{ $agent->province?->nom ?? 'N/A' }}</div>
                </div>
                <div class="data-item">
                    <div class="label">Departement</div>
                    <div class="value">{{ $agent->departement?->nom ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Parcours / Affectations --}}
        <div class="section">
            <div class="section-title">Parcours au Programme</div>
            @if($agent->affectations->count() > 0)
                <div class="timeline">
                    @foreach($agent->affectations->sortByDesc('date_debut') as $affectation)
                        <div class="timeline-entry">
                            <div class="timeline-dot {{ $affectation->actif ? 'active' : '' }}"></div>
                            <div class="fonction">{{ $affectation->fonction?->nom ?? 'Fonction non definie' }}</div>
                            <div class="details">
                                {{ $affectation->niveau_administratif_label }}
                                @if($affectation->department)
                                    &mdash; {{ $affectation->department->nom }}
                                @endif
                                @if($affectation->province)
                                    &mdash; {{ $affectation->province->nom }}
                                @endif
                                @if($affectation->actif)
                                    <span class="badge badge-green" style="font-size: 7pt;">En cours</span>
                                @endif
                            </div>
                            <div class="dates">
                                {{ $affectation->date_debut?->format('d/m/Y') ?? '?' }}
                                @if($affectation->date_fin)
                                    &rarr; {{ $affectation->date_fin->format('d/m/Y') }}
                                @elseif($affectation->actif)
                                    &rarr; Aujourd'hui
                                @endif
                            </div>
                            @if($affectation->remarque)
                                <div class="details" style="font-style: italic;">{{ $affectation->remarque }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: #999; font-size: 9pt; text-align: center; padding: 15px;">Aucune affectation enregistree</p>
            @endif
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Fiche generee le {{ now()->format('d/m/Y a H:i') }} &mdash; Portail RH PNMLS</p>
            <p>Programme National Multisectoriel de Lutte contre le Sida &mdash; Republique Democratique du Congo</p>
        </div>

    </div>
</body>
</html>
