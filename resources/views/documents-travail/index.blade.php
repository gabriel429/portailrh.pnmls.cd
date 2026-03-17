@extends('layouts.app')

@section('title', 'Documents de travail - Portail RH PNMLS')

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

    /* Filters */
    .dt-filters {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1.2rem;
    }
    .dt-filter-btn {
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
    .dt-filter-btn:hover { border-color: #ea580c; color: #ea580c; }
    .dt-filter-btn.active { background: #ea580c; border-color: #ea580c; color: #fff; }

    /* Document cards */
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
                <div class="dt-hero-stat-val">{{ $documents->total() }}</div>
                <div class="dt-hero-stat-lbl">Documents</div>
            </div>
            <div>
                <div class="dt-hero-stat-val">{{ $categories->count() }}</div>
                <div class="dt-hero-stat-lbl">Catégories</div>
            </div>
        </div>
    </div>

    {{-- Category filter pills --}}
    @if($categories->count() > 1)
    <div class="dt-filters">
        <span class="dt-filter-btn active" data-cat="all">Tous</span>
        @foreach($categories as $cat)
            <span class="dt-filter-btn" data-cat="{{ Str::slug($cat) }}">{{ $cat }}</span>
        @endforeach
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
            <div class="dt-card" data-category="{{ Str::slug($doc->categorie) }}">
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
                    <a href="{{ asset('storage/' . $doc->fichier) }}" target="_blank" class="dt-card-dl">
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
            <div class="dt-empty-icon"><i class="fas fa-file-invoice"></i></div>
            <h5>Aucun document pour le moment</h5>
            <p>Les documents de travail seront publiés ici par l'administration.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const btns = document.querySelectorAll('.dt-filter-btn');
    const cards = document.querySelectorAll('.dt-card');

    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cat = btn.dataset.cat;
            cards.forEach(card => {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        });
    });
})();
</script>
@endpush
