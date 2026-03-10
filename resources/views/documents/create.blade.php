@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- En-tête -->
            <div class="mb-4">
                <a href="{{ $agent ? route('rh.agents.show', $agent) : route('documents.index') }}" class="btn btn-link text-muted mb-3">
                    <i class="fas fa-arrow-left me-2"></i> Retour
                </a>
                <h2><i class="fas fa-cloud-upload-alt me-2"></i>
                    @if($agent)
                        Ajouter un Document pour {{ $agent->prenom }} {{ $agent->nom }}
                    @else
                        Uploader un Document
                    @endif
                </h2>
            </div>

            <!-- Messages d'erreur -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs :</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <!-- Info sur l'agent si fourni -->
                    @if($agent)
                        <div class="alert alert-info mb-4" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Document pour :</strong> {{ $agent->prenom }} {{ $agent->nom }} ({{ $agent->matricule_pnmls }})
                        </div>
                    @endif

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Agent ID hidden field -->
                        @if($agent)
                            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                        @endif

                        <!-- Nom du fichier -->
                        <div class="mb-4">
                            <label for="nom_document" class="form-label">
                                <strong>Nom du document</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nom_document" id="nom_document" class="form-control @error('nom_document') is-invalid @enderror" placeholder="Ex: Diplôme d'ingénieur" value="{{ old('nom_document') }}" required>
                            <small class="text-muted">Donnez un nom explicite à votre document</small>
                            @error('nom_document')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Catégorie -->
                        <div class="mb-4">
                            <label for="type" class="form-label">
                                <strong>Catégorie</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">-- Sélectionner une catégorie --</option>
                                <option value="identite" {{ old('type') === 'identite' ? 'selected' : '' }}>
                                    <i class="fas fa-id-card"></i> Identité (carte d'identité, passeport...)
                                </option>
                                <option value="parcours" {{ old('type') === 'parcours' ? 'selected' : '' }}>
                                    <i class="fas fa-graduation-cap"></i> Parcours (diplômes, certificats...)
                                </option>
                                <option value="carriere" {{ old('type') === 'carriere' ? 'selected' : '' }}>
                                    <i class="fas fa-briefcase"></i> Carrière (expériences, références...)
                                </option>
                                <option value="mission" {{ old('type') === 'mission' ? 'selected' : '' }}>
                                    <i class="fas fa-plane"></i> Mission (rapports, attestations...)
                                </option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <strong>Description</strong> (optionnel)
                            </label>
                            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Décrivez le contenu du document..." maxlength="500">{{ old('description') }}</textarea>
                            <small class="text-muted">Max 500 caractères</small>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Upload fichier -->
                        <div class="mb-4">
                            <label for="fichier" class="form-label">
                                <strong>Fichier</strong>
                                <span class="text-danger">*</span>
                            </label>

                            <div class="upload-area border-2 border-dashed rounded-3 p-5 text-center @error('fichier') border-danger @else border-secondary @enderror" id="uploadArea">
                                <input type="file" name="fichier" id="fichier" class="form-control d-none @error('fichier') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>

                                <div class="upload-icon mb-3">
                                    <i class="fas fa-cloud-upload-alt fa-4x text-muted"></i>
                                </div>

                                <p class="mb-1">
                                    <strong>Cliquez ou glissez votre fichier</strong>
                                </p>
                                <p class="text-muted small mb-3">
                                    Formats acceptés : PDF, JPG, JPEG, PNG<br>
                                    Taille max : 5 MB
                                </p>

                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectFileBtn">
                                    <i class="fas fa-folder-open me-2"></i> Sélectionner un fichier
                                </button>
                            </div>

                            <!-- Aperçu du fichier sélectionné -->
                            <div id="filePreview" class="mt-3" style="display: none;">
                                <div class="alert alert-light border">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-file fa-2x" id="fileIcon"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1"><strong id="fileName"></strong></p>
                                            <small class="text-muted" id="fileSize"></small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearFileBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @error('fichier')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ $agent ? route('rh.agents.show', $agent) : route('documents.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-cloud-upload-alt me-2"></i> Uploader le document
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info supplémentaire -->
            <div class="alert alert-info mt-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Information :</strong> Vos documents sont sécurisés et accessibles uniquement par vous et l'administration RH.
            </div>
        </div>
    </div>
</div>

<style>
    .upload-area {
        transition: all 0.3s ease;
        cursor: pointer;
        background-color: #f8f9fa;
    }

    .upload-area:hover {
        background-color: #e9ecef;
        border-color: #0d6efd !important;
    }

    .upload-area.drag-over {
        background-color: #e7f3ff;
        border-color: #0d6efd !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fichier');
    const filePreview = document.getElementById('filePreview');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const clearFileBtn = document.getElementById('clearFileBtn');

    // Click to select file
    selectFileBtn.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('click', () => fileInput.click());

    // File selected
    fileInput.addEventListener('change', handleFileSelect);

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect();
    });

    // Clear file
    clearFileBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.style.display = 'none';
    });

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024).toFixed(2);
            const ext = fileName.split('.').pop().toLowerCase();

            // Update preview
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('fileSize').textContent = fileSize + ' KB';

            // Set icon based on type
            const iconEl = document.getElementById('fileIcon');
            if (ext === 'pdf') {
                iconEl.className = 'fas fa-file-pdf fa-2x text-danger';
            } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
                iconEl.className = 'fas fa-file-image fa-2x text-info';
            }

            filePreview.style.display = 'block';
        }
    }
});
</script>
@endsection
