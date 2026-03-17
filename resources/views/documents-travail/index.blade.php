@extends('layouts.app')

@section('title', ($categorie ? $categorie . ' - ' : '') . 'Documents de travail - Portail RH PNMLS')

@section('css')
<style>
    .dt-hero {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 50%, #9a3412 100%);
        border-radius: 18px;
        padding: 2rem 2.2rem;
        margin-bottom: 1.5rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .dt-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -8%;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .dt-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
    .dt-hero p  { font-size: .85rem; opacity: .8; margin: 0; }
    .dt-hero-stats {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    .dt-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
    .dt-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }

    /* ── Category cards ── */
    .dt-cat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .8rem;
        margin-bottom: 1.5rem;
    }
    .dt-cat-card {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .9rem 1rem;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        text-decoration: none;
        color: #374151;
        transition: all .25s;
        cursor: pointer;
    }
    .dt-cat-card:hover {
        border-color: #ea580c;
        color: #ea580c;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(234,88,12,.1);
    }
    .dt-cat-card.active {
        background: linear-gradient(135deg, #ea580c, #c2410c);
        border-color: #ea580c;
        color: #fff;
        box-shadow: 0 4px 16px rgba(234,88,12,.25);
    }
    .dt-cat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        background: #fff7ed;
        color: #ea580c;
    }
    .dt-cat-card.active .dt-cat-icon {
        background: rgba(255,255,255,.2);
        color: #fff;
    }
    .dt-cat-card:hover .dt-cat-icon {
        background: #fff7ed;
        color: #ea580c;
    }
    .dt-cat-card.active:hover .dt-cat-icon {
        background: rgba(255,255,255,.3);
        color: #fff;
    }
    .dt-cat-info { flex: 1; min-width: 0; }
    .dt-cat-name {
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dt-cat-count {
        font-size: .7rem;
        opacity: .6;
    }
    .dt-cat-card.active .dt-cat-count { opacity: .8; }

    /* All button */
    .dt-cat-all {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .9rem 1rem;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        text-decoration: none;
        color: #374151;
        transition: all .25s;
    }
    .dt-cat-all:hover {
        border-color: #0077B5;
        color: #0077B5;
        transform: translateY(-2px);
    }
    .dt-cat-all.active {
        background: linear-gradient(135deg, #0077B5, #005a87);
        border-color: #0077B5;
        color: #fff;
        box-shadow: 0 4px 16px rgba(0,119,181,.25);
    }
    .dt-cat-all .dt-cat-icon {
        background: #e0f2fe;
        color: #0077B5;
    }
    .dt-cat-all.active .dt-cat-icon {
        background: rgba(255,255,255,.2);
        color: #fff;
    }

    /* ── Section title when filtered ── */
    .dt-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: .6rem;
        border-bottom: 2px solid #f3f4f6;
    }
    .dt-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .dt-section-badge {
        font-size: .72rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 20px;
        background: #fff7ed;
        color: #ea580c;
    }
    .dt-back-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .8rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        background: #f3f4f6;
        color: #6b7280;
        text-decoration: none;
        transition: all .2s;
    }
    .dt-back-btn:hover { background: #e5e7eb; color: #374151; }

    /* ── Document cards ── */
    .dt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }
    .dt-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        overflow: hidden;
        transition: all .2s;
        display: flex;
        flex-direction: column;
    }
    .dt-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,.08);
        transform: translateY(-2px);
    }
    .dt-card-top {
        display: flex;
        align-items: flex-start;
        gap: .8rem;
        padding: 1.2rem 1.2rem .6rem;
    }
    .dt-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .dt-ic-pdf  { background: #fee2e2; color: #dc2626; }
    .dt-ic-doc  { background: #dbeafe; color: #2563eb; }
    .dt-ic-xls  { background: #dcfce7; color: #16a34a; }
    .dt-ic-ppt  { background: #ffedd5; color: #ea580c; }
    .dt-ic-img  { background: #e0f2fe; color: #0284c7; }
    .dt-ic-other{ background: #f1f5f9; color: #64748b; }

    .dt-card-info { flex: 1; min-width: 0; }
    .dt-card-title {
        font-weight: 700;
        font-size: .9rem;
        color: #1e293b;
        margin-bottom: .2rem;
        line-height: 1.3;
    }
    .dt-card-desc {
        font-size: .78rem;
        color: #9ca3af;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .dt-card-meta {
        padding: .5rem 1.2rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
    }
    .dt-meta-badge {
        font-size: .68rem;
        font-weight: 600;
        padding: .2rem .55rem;
        border-radius: 6px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .dt-card-footer {
        border-top: 1px solid #f3f4f6;
        padding: .7rem 1.2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
    }
    .dt-card-date { font-size: .72rem; color: #9ca3af; }
    .dt-card-dl {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .35rem .8rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        background: #fff7ed;
        color: #ea580c;
        text-decoration: none;
        border: 1px solid #fed7aa;
        transition: all .2s;
    }
    .dt-card-dl:hover { background: #ea580c; color: #fff; border-color: #ea580c; }

    /* Empty */
    .dt-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    .dt-empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
        color: #d1d5db;
    }

    @media (max-width: 576px) {
        .dt-cat-grid { grid-template-columns: repeat(2, 1fr); }
        .dt-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="container py-4">

    {{-- Hero --}}
    <div class="dt-hero">
        <h2><i class="fas fa-file-invoice me-2"></i>Documents de travail</h2>
        <p>Documents officiels mis à disposition par l'administration</p>
        <div class="dt-hero-stats">
            <div>
                <div class="dt-hero-stat-val">{{ $totalDocs }}</div>
                <div class="dt-hero-stat-lbl">Documents</div>
            </div>
            <div>
                <div class="dt-hero-stat-val">{{ $categoriesDB->count() }}</div>
                <div class="dt-hero-stat-lbl">Catégories</div>
            </div>
        </div>
    </div>

    {{-- Category cards --}}
    <div class="dt-cat-grid">
        <a href="{{ url('/documents-travail') }}" class="dt-cat-all {{ !$categorie ? 'active' : '' }}">
            <div class="dt-cat-icon"><i class="fas fa-th-large"></i></div>
            <div class="dt-cat-info">
                <div class="dt-cat-name">Toutes</div>
                <div class="dt-cat-count">{{ $totalDocs }} document{{ $totalDocs > 1 ? 's' : '' }}</div>
            </div>
        </a>
        @foreach($categoriesDB as $cat)
        @php $count = $categoryCounts[$cat->nom] ?? 0; @endphp
        <a href="{{ url('/documents-travail?categorie=' . urlencode($cat->nom)) }}"
           class="dt-cat-card {{ $categorie === $cat->nom ? 'active' : '' }}">
            <div class="dt-cat-icon">
                <i class="fas {{ $cat->icone ?? 'fa-folder' }}"></i>
            </div>
            <div class="dt-cat-info">
                <div class="dt-cat-name">{{ $cat->nom }}</div>
                <div class="dt-cat-count">{{ $count }} document{{ $count > 1 ? 's' : '' }}</div>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Section header when filtered --}}
    @if($categorie)
    <div class="dt-section-header">
        <div class="dt-section-title">
            <i class="fas fa-folder-open" style="color:#ea580c;"></i>
            {{ $categorie }}
            <span class="dt-section-badge">{{ $documents->total() }} document{{ $documents->total() > 1 ? 's' : '' }}</span>
        </div>
        <a href="{{ url('/documents-travail') }}" class="dt-back-btn">
            <i class="fas fa-arrow-left"></i> Toutes les catégories
        </a>
    </div>
    @endif

    {{-- Document grid --}}
    @if($documents->count() > 0)
        <div class="dt-grid">
            @foreach($documents as $doc)
            @php
                $ext = strtolower($doc->type_fichier ?? '');
                $iconClass = match($ext) {
                    'pdf' => 'dt-ic-pdf',
                    'doc','docx' => 'dt-ic-doc',
                    'xls','xlsx' => 'dt-ic-xls',
                    'ppt','pptx' => 'dt-ic-ppt',
                    'jpg','jpeg','png' => 'dt-ic-img',
                    default => 'dt-ic-other',
                };
                $iconName = match($ext) {
                    'pdf' => 'fa-file-pdf',
                    'doc','docx' => 'fa-file-word',
                    'xls','xlsx' => 'fa-file-excel',
                    'ppt','pptx' => 'fa-file-powerpoint',
                    'jpg','jpeg','png' => 'fa-file-image',
                    default => 'fa-file-alt',
                };
            @endphp
            <div class="dt-card">
                <div class="dt-card-top">
                    <div class="dt-card-icon {{ $iconClass }}">
                        <i class="fas {{ $iconName }}"></i>
                    </div>
                    <div class="dt-card-info">
                        <div class="dt-card-title">{{ $doc->titre }}</div>
                        @if($doc->description)
                            <div class="dt-card-desc">{{ $doc->description }}</div>
                        @endif
                    </div>
                </div>
                <div class="dt-card-meta">
                    <span class="dt-meta-badge">{{ $doc->categorie }}</span>
                    <span class="dt-meta-badge">.{{ strtoupper($ext) }}</span>
                    @if($doc->taille)
                        <span class="dt-meta-badge">{{ number_format($doc->taille / 1024 / 1024, 1) }} Mo</span>
                    @endif
                </div>
                <div class="dt-card-footer">
                    <span class="dt-card-date"><i class="fas fa-clock me-1"></i>{{ $doc->created_at->format('d/m/Y') }}</span>
                    <a href="{{ route('documents-travail.download', $doc) }}" class="dt-card-dl">
                        <i class="fas fa-download"></i> Télécharger
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($documents->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $documents->links() }}
        </div>
        @endif
    @else
        <div class="dt-empty">
            <div class="dt-empty-icon"><i class="fas fa-folder-open"></i></div>
            @if($categorie)
                <h5>Aucun document dans « {{ $categorie }} »</h5>
                <p>Il n'y a pas encore de documents dans cette catégorie.</p>
                <a href="{{ url('/documents-travail') }}" class="dt-back-btn mt-3" style="display:inline-flex;">
                    <i class="fas fa-arrow-left"></i> Voir toutes les catégories
                </a>
            @else
                <h5>Aucun document pour le moment</h5>
                <p>Les documents de travail seront publiés ici par l'administration.</p>
            @endif
        </div>
    @endif
</div>
@endsection
