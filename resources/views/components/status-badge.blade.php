@props(['status'])
<span class="status-badge status-{{ $status }}"><span class="status-dot"></span>{{ \App\Support\DemoData::STATUS[$status]??$status }}</span>
