@if(session('success'))<div class="alert alert-success d-flex gap-2 align-items-start" role="status"><x-icon name="check"/><span>{{ session('success') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Periksa kembali isian berikut.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
