@extends('layouts.app')

@section('title', 'Uploader un Document')

@section('css')
<style>
    /* ── Hero Header ── */
    .upload-hero {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 50%, #155d27 100%);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        color: #fff;
    }
    .upload-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .upload-hero .hero-content { position: relative; z-index: 2; }
    .upload-hero h2 { font-weight: 800; font-size: 1.6rem; margin-bottom: .25rem; }
    .upload-hero p { opacity: .85; margin-bottom: 0; font-size: .95rem; }
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: rgba(255,255,255,.8);
        text-decoration: none;
        font-weight: 600;
        font-size: .9rem;
        margin-bottom: .75rem;
        transition: color .2s;
    }
    .btn-back:hover { color: #fff; }

    /* ── Form Card ── */
    .form-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.07);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 2px solid #f0f2f5;
    }
    .form-card-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; color: #fff; flex-shrink: 0;
    }
    .form-card-header h5 { font-weight: 700; margin-bottom: 0; color: #1a1a2e; font-size: 1.05rem; }
    .form-card-body { padding: 1.5rem; }

    /* ── Form Fields ── */
    .field-label {
        font-weight: 700;
        color: #1a1a2e;
        font-size: .9rem;
        margin-bottom: .4rem;
    }
    .field-label .req { color: #dc3545; margin-left: .15rem; }
    .field-hint {
        font-size: .78rem;
        color: #999;
        margin-top: .3rem;
    }
    .form-card .form-control,
    .form-card .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: .65rem 1rem;
        font-size: .92rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .form-card .form-control:focus,
    .form-card .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 .2rem rgba(40,167,69,.12);
    }

    /* ── Upload Zone ── */
    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        background: linear-gradient(180deg, #f9fafb 0%, #fff 100%);
        cursor: pointer;
        transition: all .3s;
        position: relative;
    }
    .upload-zone:hover {
        border-color: #28a745;
        background: linear-gradient(180deg, #f0faf3 0%, #fff 100%);
    }
    .upload-zone.drag-over {
        border-color: #28a745;
        background: #e6f7ef;
        transform: scale(1.01);
    }
    .upload-zone-icon {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e6f7ef, #c8f0d8);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.25rem;
        transition: transform .3s;
    }
    .upload-zone:hover .upload-zone-icon { transform: scale(1.1); }
    .upload-zone-icon i { font-size: 2rem; color: #28a745; }
    .upload-zone h6 { font-weight: 700; color: #1a1a2e; margin-bottom: .25rem; }
    .upload-zone .upload-hint { color: #999; font-size: .85rem; }
    .upload-zone .upload-formats {
        display: inline-flex;
        gap: .5rem;
        margin-top: .75rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    .format-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .25rem .6rem;
        border-radius: 8px;
        font-size: .75rem;
        font-weight: 600;
    }
    .format-badge.pdf { background: #fdeaec; color: #dc3545; }
    .format-badge.img { background: #e0f4ff; color: #0dcaf0; }
    .btn-browse {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: .5rem 1.25rem;
        border-radius: 10px;
        margin-top: 1rem;
        transition: all .2s;
    }
    .btn-browse:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(40,167,69,.3);
        color: #fff;
    }

    /* ── File Preview ── */
    .file-preview {
        display: none;
        margin-top: 1rem;
        background: #f8f9fc;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem 1.25rem;
    }
    .file-preview.show { display: block; }
    .file-preview-inner {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .file-thumb {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.3rem;
    }
    .file-thumb.pdf { background: #fdeaec; color: #dc3545; }
    .file-thumb.image { background: #e0f4ff; color: #0dcaf0; }
    .file-thumb.other { background: #f5f5f5; color: #6c757d; }
    .file-info { flex: 1; }
    .file-info .file-name { font-weight: 700; color: #1a1a2e; font-size: .9rem; }
    .file-info .file-size { font-size: .78rem; color: #999; }
    .btn-clear-file {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: none;
        background: #fef5f5;
        color: #dc3545;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s;
        cursor: pointer;
    }
    .btn-clear-file:hover { background: #fdeaec; transform: scale(1.1); }

    /* ── Category Cards ── */
    .cat-option {
        display: none;
    }
    .cat-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .75rem;
    }
    .cat-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .cat-card:hover { border-color: #28a745; background: #f0faf3; }
    .cat-card.selected {
        border-color: #28a745;
        background: #e6f7ef;
    }
    .cat-card-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .cat-card-text { font-weight: 600; font-size: .88rem; color: #1a1a2e; }
    .cat-card-sub { font-size: .75rem; color: #999; }

    /* ── Info Alert ── */
    .info-banner {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        background: linear-gradient(135deg, #e6f7ef, #f0faf3);
        border: 1px solid #c8e6c9;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .info-banner-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: #28a745;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .info-banner p { margin: 0; font-size: .88rem; color: #1a1a2e; }
    .info-banner strong { color: #155d27; }

    /* ── Agent Banner ── */
    .agent-banner {
        display: flex;
        align-items: center;
        gap: .75rem;
        background: linear-gradient(135deg, #e3f2fd, #f0f7ff);
        border: 1px solid #bbdefb;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .agent-banner-icon {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: #0077B5;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .agent-banner-name { font-weight: 700; color: #1a1a2e; }
    .agent-banner-id { font-size: .82rem; color: #6c757d; }

    /* ── Buttons ── */
    .btn-submit {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        color: #fff;
        font-weight: 700;
        padding: .7rem 1.75rem;
        border-radius: 12px;
        font-size: .95rem;
        transition: all .2s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40,167,69,.3);
        color: #fff;
    }
    .btn-cancel {
        background: #f5f5f5;
        border: 2px solid #e9ecef;
        color: #666;
        font-weight: 600;
        padding: .7rem 1.5rem;
        border-radius: 12px;
        transition: all .2s;
    }
    .btn-cancel:hover { background: #e9ecef; color: #333; }

    @media (max-width: 576px) {
        .cat-cards { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- ═══ Hero ═══ --}}
            <div class="upload-hero">
                <div class="hero-content">
                    <a href="{{ $agent ? route('rh.agents.show', $agent) : route('documents.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <h2>
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        @if($agent)
                            Ajouter un Document
                        @else
                            Uploader un Document
                        @endif
                    </h2>
                    <p>
                        @if($agent)
                            Ajoutez un document au dossier de {{ $agent->prenom }} {{ $agent->nom }}
                        @else
                            Ajoutez un nouveau document à votre dossier personnel
                        @endif
                    </p>
                </div>
            </div>

            {{-- Agent info banner --}}
            @if($agent)
                <div class="agent-banner">
                    <div class="agent-banner-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="agent-banner-name">{{ $agent->prenom }} {{ $agent->nom }}</div>
                        <div class="agent-banner-id">{{ $agent->id_agent }}</div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                @csrf
                @if($agent)
                    <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                @endif

                {{-- ═══ Section 1 : Informations du document ═══ --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="form-card-icon" style="background:linear-gradient(135deg,#0077B5,#005885);">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h5>Informations du document</h5>
                    </div>
                    <div class="form-card-body">
                        {{-- Nom du document --}}
                        <div class="mb-4">
                            <label class="field-label">Nom du document <span class="req">*</span></label>
                            <input type="text" name="nom_document" id="nom_document"
                                   class="form-control @error('nom_document') is-invalid @enderror"
                                   placeholder="Ex: Diplôme de licence en informatique"
                                   value="{{ old('nom_document') }}" required>
                            <div class="field-hint">Donnez un nom clair et explicite à votre document</div>
                            @error('nom_document')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Catégorie (cards visuelles) --}}
                        <div class="mb-4">
                            <label class="field-label">Catégorie <span class="req">*</span></label>
                            <select name="type" id="type" class="cat-option @error('type') is-invalid @enderror" required>
                                <option value="">--</option>
                                <option value="identite" {{ old('type') === 'identite' ? 'selected' : '' }}>Identité</option>
                                <option value="parcours" {{ old('type') === 'parcours' ? 'selected' : '' }}>Parcours</option>
                                <option value="carriere" {{ old('type') === 'carriere' ? 'selected' : '' }}>Carrière</option>
                                <option value="mission" {{ old('type') === 'mission' ? 'selected' : '' }}>Mission</option>
                            </select>
                            <div class="cat-cards">
                                <div class="cat-card" data-value="identite">
                                    <div class="cat-card-icon" style="background:#e8f4fd;color:#0077B5;"><i class="fas fa-id-card"></i></div>
                                    <div>
                                        <div class="cat-card-text">Identité</div>
                                        <div class="cat-card-sub">Carte d'identité, passeport...</div>
                                    </div>
                                </div>
                                <div class="cat-card" data-value="parcours">
                                    <div class="cat-card-icon" style="background:#fff3e0;color:#e67e22;"><i class="fas fa-graduation-cap"></i></div>
                                    <div>
                                        <div class="cat-card-text">Parcours</div>
                                        <div class="cat-card-sub">Diplômes, certificats...</div>
                                    </div>
                                </div>
                                <div class="cat-card" data-value="carriere">
                                    <div class="cat-card-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-briefcase"></i></div>
                                    <div>
                                        <div class="cat-card-text">Carrière</div>
                                        <div class="cat-card-sub">Expériences, références...</div>
                                    </div>
                                </div>
                                <div class="cat-card" data-value="mission">
                                    <div class="cat-card-icon" style="background:#e6f7ef;color:#28a745;"><i class="fas fa-plane"></i></div>
                                    <div>
                                        <div class="cat-card-text">Mission</div>
                                        <div class="cat-card-sub">Rapports, attestations...</div>
                                    </div>
                                </div>
                            </div>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="field-label">Description <span style="color:#999;font-weight:400;">(optionnel)</span></label>
                            <textarea name="description" id="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Décrivez brièvement le contenu du document..."
                                      maxlength="500">{{ old('description') }}</textarea>
                            <div class="field-hint">Max 500 caractères</div>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ═══ Section 2 : Upload fichier ═══ --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="form-card-icon" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h5>Fichier à uploader</h5>
                    </div>
                    <div class="form-card-body">
                        <input type="file" name="fichier" id="fichier"
                               class="d-none @error('fichier') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png" required>

                        <div class="upload-zone" id="uploadArea">
                            <div class="upload-zone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h6>Glissez votre fichier ici</h6>
                            <p class="upload-hint">ou cliquez pour parcourir vos fichiers</p>
                            <div class="upload-formats">
                                <span class="format-badge pdf"><i class="fas fa-file-pdf"></i> PDF</span>
                                <span class="format-badge img"><i class="fas fa-file-image"></i> JPG</span>
                                <span class="format-badge img"><i class="fas fa-file-image"></i> JPEG</span>
                                <span class="format-badge img"><i class="fas fa-file-image"></i> PNG</span>
                            </div>
                            <button type="button" class="btn btn-browse" id="selectFileBtn">
                                <i class="fas fa-folder-open me-2"></i>Parcourir
                            </button>
                        </div>

                        {{-- File preview --}}
                        <div class="file-preview" id="filePreview">
                            <div class="file-preview-inner">
                                <div class="file-thumb other" id="fileThumb">
                                    <i class="fas fa-file" id="fileIcon"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name" id="fileName"></div>
                                    <div class="file-size" id="fileSize"></div>
                                </div>
                                <button type="button" class="btn-clear-file" id="clearFileBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="field-hint mt-2"><i class="fas fa-info-circle me-1"></i>Taille maximum : 5 MB</div>

                        @error('fichier')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ═══ Actions ═══ --}}
                <div class="d-flex gap-2 justify-content-between align-items-center mb-4">
                    <a href="{{ $agent ? route('rh.agents.show', $agent) : route('documents.index') }}" class="btn btn-cancel">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                    <button type="submit" class="btn btn-submit" id="submitBtn">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Uploader le document
                    </button>
                </div>
            </form>

            {{-- Info sécurité --}}
            <div class="info-banner">
                <div class="info-banner-icon"><i class="fas fa-shield-alt"></i></div>
                <p><strong>Sécurité :</strong> Vos documents sont chiffrés et accessibles uniquement par vous et l'administration RH autorisée.</p>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fichier');
    const filePreview = document.getElementById('filePreview');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const clearFileBtn = document.getElementById('clearFileBtn');
    const typeSelect = document.getElementById('type');
    const catCards = document.querySelectorAll('.cat-card');

    // ── Category card selection ──
    const oldType = typeSelect.value;
    if (oldType) {
        document.querySelector('.cat-card[data-value="' + oldType + '"]')?.classList.add('selected');
    }

    catCards.forEach(card => {
        card.addEventListener('click', function() {
            catCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            typeSelect.value = this.dataset.value;
        });
    });

    // ── File upload ──
    selectFileBtn.addEventListener('click', (e) => { e.stopPropagation(); fileInput.click(); });
    uploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);

    // Drag & drop
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect();
    });

    clearFileBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.remove('show');
        uploadArea.style.display = '';
    });

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();
        const sizeTxt = file.size >= 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : (file.size / 1024).toFixed(1) + ' KB';

        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = sizeTxt;

        const thumb = document.getElementById('fileThumb');
        const icon = document.getElementById('fileIcon');
        thumb.className = 'file-thumb';
        if (ext === 'pdf') {
            thumb.classList.add('pdf');
            icon.className = 'fas fa-file-pdf';
        } else if (['jpg','jpeg','png'].includes(ext)) {
            thumb.classList.add('image');
            icon.className = 'fas fa-file-image';
        } else {
            thumb.classList.add('other');
            icon.className = 'fas fa-file';
        }

        filePreview.classList.add('show');
        uploadArea.style.display = 'none';
    }
});
</script>
@endpush
