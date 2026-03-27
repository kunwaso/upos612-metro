@if(session('status') && is_array(session('status')))
    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show d-flex align-items-center mb-6" role="alert">
        <i class="ki-duotone fs-2hx me-4 {{ session('status.success') ? 'ki-check-circle text-success' : 'ki-information-5 text-danger' }}"><span class="path1"></span><span class="path2"></span></i>
        <div class="d-flex flex-column pe-7">
            <span>{{ session('status.msg') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
