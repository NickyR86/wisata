@props(['record'])
@if($record['bill'] && in_array('SKRD',$record['documents']))
<strong class="d-block text-nowrap">{{ \App\Support\DemoData::rupiah($record['bill']['total']) }}</strong>
<small class="{{ $record['payment']?'text-success':'text-secondary' }}">{{ $record['payment']?'Lunas':'Menunggu pembayaran' }}</small>
@else<span class="text-secondary">Belum ditetapkan</span>@endif
