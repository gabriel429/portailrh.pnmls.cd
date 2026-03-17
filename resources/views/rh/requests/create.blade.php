@extends('layouts.app')

@section('title', 'Nouvelle Demande - Portail RH PNMLS')

@section('css')
<style>
    .req-hero {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
        border-radius: 18px;
        padding: 2rem 2.2rem;
        margin-bottom: 1.8rem;
        position: relative;
        overflow: hidden;
        color: #fff;
    }
    .req-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .req-hero::after {
        content: '';
        position: absolute;
        bottom: -50%;
        left: 30%;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.05);
    }
    .req-hero .hero-back {
        color: rgba(255,255,255,.7);
        text-decoration: none;
        font-size: .82rem;
        font-weight: 500;
        transition: color .2s;
    }
    .req-hero .hero-back:hover { color: #fff; }
    .req-hero h2 {
        font-size: 1.45rem;
        font-weight: 700;
        margin: .6rem 0 .25rem;
    }
    .req-hero p {
        font-size: .85rem;
        opacity: .8;
        margin: 0;
    }

    /* Form card */
    .req-form-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .req-form-body { padding: 2rem 2.2rem; }

    /* Section dividers */
    .section-title {
        display: flex;
        align-items: center;
        gap: .6rem;
        font-weight: 700;
        font-size: .92rem;
        color: #1e293b;
        margin-bottom: 1.2rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid #e5e7eb;
    }
    .section-title .st-icon {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        flex-shrink: 0;
    }
    .st-purple { background: #ede9fe; color: #7c3aed; }
    .st-blue   { background: #dbeafe; color: #2563eb; }
    .st-green  { background: #dcfce7; color: #16a34a; }
    .st-orange { background: #ffedd5; color: #ea580c; }

    /* Type cards */
    .type-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .7rem; }
    .type-card {
        padding: .8rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: #fff;
    }
    .type-card:hover {
        border-color: #a5b4fc;
        background: #f5f3ff;
    }
    .type-card.active {
        border-color: #6366f1;
        background: #ede9fe;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
    }
    .type-card .tc-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto .5rem;
        font-size: 1rem;
    }
    .type-card .tc-label {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
    }
    .type-card.active .tc-label { color: #4f46e5; }

    /* Custom labels */
    .req-label {
        font-weight: 600;
        font-size: .82rem;
        color: #374151;
        margin-bottom: .4rem;
    }
    .req-label .text-danger { font-size: .75rem; }

    /* Upload zone */
    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 14px;
        padding: 1.5rem;
        text-align: center;
        transition: all .25s;
        cursor: pointer;
        background: #fafafa;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: #6366f1;
        background: #f5f3ff;
    }
    .upload-zone .uz-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #ede9fe;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto .6rem;
    }
    .upload-zone .uz-title {
        font-weight: 600;
        font-size: .88rem;
        color: #374151;
    }
    .upload-zone .uz-hint {
        font-size: .75rem;
        color: #9ca3af;
        margin-top: .3rem;
    }
    .upload-zone .uz-formats {
        display: flex;
        gap: .4rem;
        justify-content: center;
        margin-top: .6rem;
        flex-wrap: wrap;
    }
    .uz-formats .badge {
        font-size: .65rem;
        font-weight: 600;
        padding: .25rem .5rem;
        border-radius: 6px;
        background: #e5e7eb;
        color: #6b7280;
    }

    /* File preview */
    .file-preview {
        display: none;
        align-items: center;
        gap: .8rem;
        padding: .8rem 1rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        margin-top: .7rem;
    }
    .file-preview.show { display: flex; }
    .file-preview .fp-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #dcfce7;
        color: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .file-preview .fp-info { flex: 1; min-width: 0; }
    .file-preview .fp-name {
        font-weight: 600;
        font-size: .82rem;
        color: #166534;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .file-preview .fp-size {
        font-size: .7rem;
        color: #4ade80;
    }
    .file-preview .fp-remove {
        color: #ef4444;
        background: none;
        border: none;
        cursor: pointer;
        font-size: .8rem;
        padding: .3rem;
        border-radius: 6px;
        transition: background .2s;
    }
    .file-preview .fp-remove:hover { background: #fee2e2; }

    /* Submit bar */
    .submit-bar {
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        padding: 1.2rem 2.2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .submit-bar .sb-hint {
        font-size: .78rem;
        color: #9ca3af;
    }
    .submit-bar .btn {
        padding: .55rem 1.4rem;
        font-weight: 600;
        border-radius: 10px;
        font-size: .85rem;
    }

    /* Agent card when known */
    .agent-banner {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .7rem 1rem;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        margin-bottom: 1.2rem;
    }
    .agent-banner .ab-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #0077B5;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .72rem;
        flex-shrink: 0;
    }
    .agent-banner .ab-info { flex: 1; }
    .agent-banner .ab-name { font-weight: 600; font-size: .85rem; color: #0c4a6e; }
    .agent-banner .ab-id   { font-size: .72rem; color: #0284c7; }
</style>
@endsection

@section('content')
<div class="container py-4" style="max-width: 760px;">

    {{-- Hero header --}}
    <div class="req-hero">
        <a href="{{ route('requests.index') }}" class="hero-back">
            <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
        </a>
        <h2><i class="fas fa-plus-circle me-2"></i>Nouvelle Demande</h2>
        <p>Remplissez le formulaire ci-dessous pour soumettre votre demande</p>
    </div>

    {{-- Form card --}}
    <div class="req-form-card">
        <div class="req-form-body">
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" id="requestForm">
                @csrf

                {{-- Section 1 : Agent --}}
                <div class="section-title">
                    <span class="st-icon st-blue"><i class="fas fa-user"></i></span>
                    Demandeur
                </div>

                @php
                    $currentAgent = auth()->user()->agent;
                @endphp

                @if($currentAgent)
                    <div class="agent-banner">
                        <div class="ab-avatar">
                            {{ mb_strtoupper(mb_substr($currentAgent->prenom ?? '', 0, 1) . mb_substr($currentAgent->nom ?? '', 0, 1)) }}
                        </div>
                        <div class="ab-info">
                            <div class="ab-name">{{ $currentAgent->prenom }} {{ $currentAgent->nom }}</div>
                            <div class="ab-id">{{ $currentAgent->id_agent }}</div>
                        </div>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="req-label">Agent <span class="text-danger">*</span></label>
                    <select name="agent_id" id="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required>
                        <option value="">-- Sélectionner un agent --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(old('agent_id', $currentAgent?->id) == $agent->id)>
                                {{ $agent->prenom }} {{ $agent->nom }} ({{ $agent->id_agent }})
                            </option>
                        @endforeach
                    </select>
                    @error('agent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Section 2 : Type de demande --}}
                <div class="section-title">
                    <span class="st-icon st-purple"><i class="fas fa-list-check"></i></span>
                    Type de demande
                </div>

                <div class="type-cards mb-4">
                    <div class="type-card" data-type="congé">
                        <div class="tc-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-umbrella-beach"></i></div>
                        <div class="tc-label">Congé</div>
                    </div>
                    <div class="type-card" data-type="absence">
                        <div class="tc-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-user-slash"></i></div>
                        <div class="tc-label">Absence</div>
                    </div>
                    <div class="type-card" data-type="permission">
                        <div class="tc-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-door-open"></i></div>
                        <div class="tc-label">Permission</div>
                    </div>
                    <div class="type-card" data-type="formation">
                        <div class="tc-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-graduation-cap"></i></div>
                        <div class="tc-label">Formation</div>
                    </div>
                    <div class="type-card" data-type="renforcement_capacites">
                        <div class="tc-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-chart-line"></i></div>
                        <div class="tc-label">Renforcement</div>
                    </div>
                </div>
                <select name="type" id="type" class="d-none" required>
                    <option value="">--</option>
                    <option value="congé" @selected(old('type') === 'congé')>Congé</option>
                    <option value="absence" @selected(old('type') === 'absence')>Absence</option>
                    <option value="permission" @selected(old('type') === 'permission')>Permission</option>
                    <option value="formation" @selected(old('type') === 'formation')>Formation</option>
                    <option value="renforcement_capacites" @selected(old('type') === 'renforcement_capacites')>Renforcement</option>
                </select>
                @error('type')<div class="text-danger" style="font-size:.8rem;margin-top:-.8rem;margin-bottom:.8rem;">{{ $message }}</div>@enderror

                {{-- Section 3 : Détails --}}
                <div class="section-title">
                    <span class="st-icon st-green"><i class="fas fa-info-circle"></i></span>
                    Détails de la demande
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="req-label"><i class="fas fa-calendar-alt me-1 text-muted"></i> Date de début <span class="text-danger">*</span></label>
                        <input type="date" name="date_debut" id="date_debut"
                               class="form-control @error('date_debut') is-invalid @enderror"
                               value="{{ old('date_debut') }}" required>
                        @error('date_debut')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="req-label"><i class="fas fa-calendar-check me-1 text-muted"></i> Date de fin <span class="text-muted fw-normal">(optionnel)</span></label>
                        <input type="date" name="date_fin" id="date_fin"
                               class="form-control @error('date_fin') is-invalid @enderror"
                               value="{{ old('date_fin') }}">
                        @error('date_fin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="req-label"><i class="fas fa-align-left me-1 text-muted"></i> Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" rows="4"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Décrivez le motif de votre demande..." required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Section 4 : Lettre de demande --}}
                <div class="section-title">
                    <span class="st-icon st-orange"><i class="fas fa-paperclip"></i></span>
                    Lettre de demande <span class="text-muted fw-normal" style="font-size:.78rem;margin-left:.3rem;">(optionnel)</span>
                </div>

                <div class="upload-zone" id="uploadZone">
                    <div class="uz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="uz-title">Glissez votre fichier ici ou cliquez pour parcourir</div>
                    <div class="uz-hint">Joignez la lettre officielle de demande</div>
                    <div class="uz-formats">
                        <span class="badge">PDF</span>
                        <span class="badge">DOC</span>
                        <span class="badge">DOCX</span>
                        <span class="badge">JPG</span>
                        <span class="badge">PNG</span>
                        <span class="badge">Max 5 Mo</span>
                    </div>
                    <input type="file" name="lettre_demande" id="lettre_demande" class="d-none"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                @error('lettre_demande')<div class="text-danger" style="font-size:.8rem;margin-top:.4rem;">{{ $message }}</div>@enderror

                <div class="file-preview" id="filePreview">
                    <div class="fp-icon"><i class="fas fa-file-check"></i></div>
                    <div class="fp-info">
                        <div class="fp-name" id="fileName"></div>
                        <div class="fp-size" id="fileSize"></div>
                    </div>
                    <button type="button" class="fp-remove" id="removeFile" title="Supprimer">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Submit bar --}}
        <div class="submit-bar">
            <div class="sb-hint"><i class="fas fa-lock me-1"></i> Votre demande sera envoyée pour approbation</div>
            <div class="d-flex gap-2">
                <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" form="requestForm" class="btn btn-primary" style="background:#6366f1;border-color:#6366f1;">
                    <i class="fas fa-paper-plane me-1"></i> Soumettre
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    // Type cards selection
    const typeSelect = document.getElementById('type');
    const typeCards = document.querySelectorAll('.type-card');
    const oldType = typeSelect.value;

    typeCards.forEach(card => {
        if (card.dataset.type === oldType) card.classList.add('active');

        card.addEventListener('click', () => {
            typeCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            typeSelect.value = card.dataset.type;
        });
    });

    // File upload
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('lettre_demande');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeBtn = document.getElementById('removeFile');

    uploadZone.addEventListener('click', () => fileInput.click());

    uploadZone.addEventListener('dragover', e => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) showFile(fileInput.files[0]);
    });

    function showFile(file) {
        fileName.textContent = file.name;
        const sizeMb = (file.size / 1024 / 1024).toFixed(2);
        fileSize.textContent = sizeMb + ' Mo';
        filePreview.classList.add('show');
        uploadZone.style.display = 'none';
    }

    removeBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.remove('show');
        uploadZone.style.display = '';
    });

    // Auto-calc duration
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    dateDebut.addEventListener('change', () => {
        if (!dateFin.value && dateDebut.value) {
            dateFin.min = dateDebut.value;
        }
    });
})();
</script>
@endpush
