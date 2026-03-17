@extends('layouts.app')

@section('title', 'Notifications - Portail RH PNMLS')

@section('css')
<style>
    .notif-hero {
        background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #003f5c 100%);
        border-radius: 18px;
        padding: 2rem 2.2rem;
        margin-bottom: 1.5rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .notif-hero::before {
        content: '';
        position: absolute;
        top: -40%; right: -8%;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .notif-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
    .notif-hero p { font-size: .85rem; opacity: .8; margin: 0; }
    .notif-hero-stats {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    .notif-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
    .notif-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }

    .notif-actions {
        display: flex;
        gap: .6rem;
        margin-bottom: 1.2rem;
        flex-wrap: wrap;
    }
    .notif-action-btn {
        padding: .4rem .9rem;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 600;
        border: 2px solid #e5e7eb;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
    }
    .notif-action-btn:hover { border-color: #0077B5; color: #0077B5; }
    .notif-action-btn.active { background: #0077B5; border-color: #0077B5; color: #fff; }
    .notif-action-btn.mark-all {
        background: #f0f9ff;
        border-color: #bae6fd;
        color: #0077B5;
    }
    .notif-action-btn.mark-all:hover { background: #0077B5; color: #fff; border-color: #0077B5; }

    /* Notification list */
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .notif-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.2rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        transition: all .2s;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    .notif-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,.06);
        transform: translateY(-1px);
        color: inherit;
    }
    .notif-card.unread {
        background: #f0f9ff;
        border-color: #bae6fd;
    }
    .notif-card.unread::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: #0077B5;
        border-radius: 14px 0 0 14px;
    }
    .notif-card-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .notif-card-content { flex: 1; min-width: 0; }
    .notif-card-title {
        font-weight: 700;
        font-size: .88rem;
        color: #1e293b;
        margin-bottom: .2rem;
        line-height: 1.3;
    }
    .notif-card-msg {
        font-size: .8rem;
        color: #6b7280;
        line-height: 1.4;
    }
    .notif-card-meta {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-top: .3rem;
    }
    .notif-card-time {
        font-size: .72rem;
        color: #9ca3af;
    }
    .notif-card-type {
        font-size: .65rem;
        font-weight: 600;
        padding: .15rem .5rem;
        border-radius: 6px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .notif-card-actions {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex-shrink: 0;
    }
    .notif-card-btn {
        padding: .25rem .6rem;
        border-radius: 6px;
        font-size: .7rem;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all .2s;
        border: none;
        cursor: pointer;
    }
    .notif-card-btn.read-btn {
        background: #f0f9ff;
        color: #0077B5;
    }
    .notif-card-btn.read-btn:hover { background: #0077B5; color: #fff; }
    .notif-card-btn.del-btn {
        background: #fef2f2;
        color: #ef4444;
    }
    .notif-card-btn.del-btn:hover { background: #ef4444; color: #fff; }

    .notif-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    .notif-empty-icon {
        width: 64px; height: 64px; border-radius: 50%;
        background: #f3f4f6; display: flex; align-items: center;
        justify-content: center; font-size: 1.5rem;
        margin: 0 auto 1rem; color: #d1d5db;
    }

    @media (max-width: 576px) {
        .notif-card { flex-wrap: wrap; gap: .6rem; }
        .notif-card-actions { flex-direction: row; width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="container py-4">

    {{-- Hero --}}
    <div class="notif-hero">
        <h2><i class="fas fa-bell me-2"></i>Notifications</h2>
        <p>Restez informé de toutes les activités du portail</p>
        <div class="notif-hero-stats">
            <div>
                <div class="notif-hero-stat-val">{{ $notifications->total() }}</div>
                <div class="notif-hero-stat-lbl">Total</div>
            </div>
            <div>
                <div class="notif-hero-stat-val">{{ $nonLuesCount }}</div>
                <div class="notif-hero-stat-lbl">Non lues</div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="notif-actions">
        <a href="{{ url('/notifications') }}" class="notif-action-btn {{ !request('filtre') ? 'active' : '' }}">Toutes</a>
        <a href="{{ url('/notifications?filtre=non_lues') }}" class="notif-action-btn {{ request('filtre') === 'non_lues' ? 'active' : '' }}">Non lues</a>
        <a href="{{ url('/notifications?filtre=demande') }}" class="notif-action-btn {{ request('filtre') === 'demande' ? 'active' : '' }}">Demandes</a>
        <a href="{{ url('/notifications?filtre=communique') }}" class="notif-action-btn {{ request('filtre') === 'communique' ? 'active' : '' }}">Communiqués</a>
        <a href="{{ url('/notifications?filtre=plan_travail') }}" class="notif-action-btn {{ request('filtre') === 'plan_travail' ? 'active' : '' }}">Plan de travail</a>
        <a href="{{ url('/notifications?filtre=document_travail') }}" class="notif-action-btn {{ request('filtre') === 'document_travail' ? 'active' : '' }}">Documents</a>
        @if($nonLuesCount > 0)
        <form action="{{ url('/notifications/mark-all-read') }}" method="POST" class="d-inline ms-auto">
            @csrf
            <button type="submit" class="notif-action-btn mark-all" style="border:none;cursor:pointer;">
                <i class="fas fa-check-double me-1"></i> Tout marquer lu
            </button>
        </form>
        @endif
    </div>

    {{-- Notification list --}}
    @if($notifications->count() > 0)
    <div class="notif-list">
        @foreach($notifications as $notif)
        @php
            $typeLabel = match($notif->type) {
                'demande' => 'Demande',
                'demande_modifiee' => 'Modification',
                'demande_approuvee' => 'Approbation',
                'demande_rejetee' => 'Rejet',
                'plan_travail' => 'Plan de travail',
                'communique' => 'Communiqué',
                'message' => 'Message',
                'document_travail' => 'Document',
                default => 'Notification',
            };
        @endphp
        <div class="notif-card {{ !$notif->lu ? 'unread' : '' }}">
            <div class="notif-card-icon" style="background: {{ $notif->couleur }}15; color: {{ $notif->couleur }};">
                <i class="fas {{ $notif->icone }}"></i>
            </div>
            <div class="notif-card-content">
                <div class="notif-card-title">{{ $notif->titre }}</div>
                <div class="notif-card-msg">{{ $notif->message }}</div>
                <div class="notif-card-meta">
                    <span class="notif-card-time"><i class="fas fa-clock me-1"></i>{{ $notif->created_at->diffForHumans() }}</span>
                    <span class="notif-card-type">{{ $typeLabel }}</span>
                    @if($notif->emetteur)
                        <span class="notif-card-time"><i class="fas fa-user me-1"></i>{{ $notif->emetteur->name }}</span>
                    @endif
                </div>
            </div>
            <div class="notif-card-actions">
                @if($notif->lien)
                <form action="{{ url('/notifications/' . $notif->id . '/read') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="notif-card-btn read-btn" style="border:none;cursor:pointer;">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </form>
                @elseif(!$notif->lu)
                <form action="{{ url('/notifications/' . $notif->id . '/read') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="notif-card-btn read-btn" style="border:none;cursor:pointer;">
                        <i class="fas fa-check"></i> Lu
                    </button>
                </form>
                @endif
                <form action="{{ url('/notifications/' . $notif->id) }}" method="POST" style="display:inline;">
                    @csrf @method('DELETE')
                    <button class="notif-card-btn del-btn" type="submit"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    @if($notifications->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $notifications->appends(request()->query())->links() }}
    </div>
    @endif

    @else
    <div class="notif-empty">
        <div class="notif-empty-icon"><i class="fas fa-bell-slash"></i></div>
        <h5>Aucune notification</h5>
        <p>Vous n'avez pas encore de notifications{{ request('filtre') ? ' dans cette catégorie' : '' }}.</p>
    </div>
    @endif
</div>
@endsection
