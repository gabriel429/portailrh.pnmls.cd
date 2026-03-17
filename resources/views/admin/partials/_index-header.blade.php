{{--
    Index Header Banner
    Usage: @include('admin.partials._index-header', [
        'icon'  => 'fa-building',
        'title' => 'Départements',
        'desc'  => 'Gérez les départements du programme',
        'color' => '#6366f1',
        'bg'    => '#ede9fe',
        'stats' => [['label' => 'Total', 'value' => 12], ['label' => 'Agents', 'value' => 45]],
    ])
--}}
<div class="index-banner" style="--acc:{{ $color }};--acc-bg:{{ $bg }}">
    <div class="index-banner-left">
        <div class="index-banner-ico"><i class="fas {{ $icon }}"></i></div>
        <div>
            <div class="index-banner-title">{{ $title }}</div>
            <div class="index-banner-desc">{{ $desc }}</div>
        </div>
    </div>
    @if(!empty($stats))
    <div class="index-banner-stats">
        @foreach($stats as $s)
        <div class="index-banner-stat">
            <div class="index-banner-stat-val">{{ $s['value'] }}</div>
            <div class="index-banner-stat-lbl">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>
    @endif
</div>
