@extends('layouts.app')

@section('title', 'Connexion - Portail RH PNMLS')

@section('content')
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100" style="max-width: 1200px;">
        <!-- Section gauche: promo -->
        <div class="col-md-6 d-none d-md-flex flex-column justify-content-center align-items-center pb-5">
            <div class="text-center">
                <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS Logo" style="max-height: 250px; width: auto; object-fit: contain; margin-bottom: 20px;">
                <h1 class="mt-4 mb-3">Portail RH PNMLS</h1>
                <p class="lead text-muted mb-4">
                    Gestion Intégrée des Ressources Humaines<br>
                    Programme National Multisectoriel de Lutte contre le Sida
                </p>
                <ul class="list-unstyled text-start ms-5">
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Gestion centralisée des agents</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Suivi de carrière numérisé</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Gestion documentaire (GED)</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Workflow des demandes</li>
                    <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Demandes de congés simplifiées</li>
                </ul>
            </div>
        </div>

        <!-- Section droite: formulaire -->
        <div class="col-md-6">
            <div class="card shadow-lg border-0" style="border-radius: 12px;">
                <div class="card-body p-5">
                    <h2 class="mb-4 text-center">
                        <i class="fas fa-sign-in-alt me-2" style="color: #0077B5;"></i> Connexion
                    </h2>

                    <form action="{{ url('/login') }}" method="POST">
                        @csrf

                        <!-- Adresse Email Professionnelle -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Adresse Email Professionnelle</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    placeholder="Ex: patrick.wemba@pnmls.cd"
                                    value="{{ old('email') }}"
                                    required>
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Entrez votre adresse email professionnelle</small>
                        </div>

                        <!-- Mot de passe -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="Votre mot de passe"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Se souvenir -->
                        <div class="mb-4 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember"
                                name="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <!-- Bouton connexion -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i> Se Connecter
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- Liens utiles -->
                    <div class="text-center">
                        <p class="mb-2">
                            <a href="{{ url('/forgot-password') }}" class="text-decoration-none">
                                <i class="fas fa-question-circle me-1"></i> Mot de passe oublié ?
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info de support -->
            <div class="alert alert-info mt-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Besoin d'aide ?</strong> Contactez votre administrateur RH ou le support informatique du PNMLS.
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
@endsection
