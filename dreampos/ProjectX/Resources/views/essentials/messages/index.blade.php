@extends('projectx::layouts.main')

@section('title', __('essentials::lang.messages'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.messages')</h1>
    </div>
</div>

<div class="row g-7">
    @if(auth()->user()->can('essentials.create_message'))
        <div class="col-xl-4">
            <div class="card card-flush">
                <div class="card-header pt-7"><h3 class="card-title">@lang('essentials::lang.create_message')</h3></div>
                <div class="card-body pt-5">
                    <form id="projectx_message_form">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">@lang('business.business_location')</label>
                            <select name="location_id" class="form-select form-select-solid" data-control="select2" data-hide-search="false">
                                <option value="">@lang('messages.all')</option>
                                @foreach($business_locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label required">@lang('essentials::lang.type_message')</label>
                            <textarea name="message" rows="5" class="form-control form-control-solid" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">@lang('essentials::lang.submit')</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="{{ auth()->user()->can('essentials.create_message') ? 'col-xl-8' : 'col-12' }}">
        <div class="card card-flush">
            <div class="card-header pt-7"><h3 class="card-title">@lang('essentials::lang.messages')</h3></div>
            <div class="card-body pt-5">
                <div id="projectx_messages_container">
                    @foreach($messages as $message)
                        @include('projectx::essentials.messages.partials._message_div', ['message' => $message])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    var container = $('#projectx_messages_container');

    $('#projectx_message_form').on('submit', function (event) {
        event.preventDefault();

        $.post('{{ route('projectx.essentials.messages.store') }}', $(this).serialize(), function (response) {
            if (response.success) {
                toastr.success(response.msg);
                if (response.html) {
                    container.append(response.html);
                }
                $('#projectx_message_form textarea[name="message"]').val('');
            } else {
                toastr.error(response.msg);
            }
        });
    });

    $(document).on('click', '.projectx-delete-message', function () {
        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.messages.destroy', ['message' => '__ID__'])).replace('__ID__', id);

        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#projectx_message_' + id).remove();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    var latestCheck = @json($last_chat_time);
    setInterval(function () {
        $.get('{{ route('projectx.essentials.messages.get-new') }}', {last_chat_time: latestCheck}, function (html) {
            if (html && html.trim().length > 0) {
                container.append(html);
                latestCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
            }
        });
    }, 15000);
})();
</script>
@endsection
