<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-8 col-md-6">
            <div class="form-group">
                {!! Form::label('groq_api_key', __('essentials::lang.groq_api_key') . ':') !!}
                {!! Form::password('groq_api_key', [
                    'class' => 'form-control',
                    'placeholder' => __('essentials::lang.groq_api_key_placeholder'),
                    'autocomplete' => 'new-password',
                ]) !!}
                <p class="help-block">
                    {!! __('essentials::lang.groq_api_key_help') !!}
                </p>
            </div>
        </div>
    </div>
    @if(!empty($settings['groq_api_key']))
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                @lang('essentials::lang.groq_api_key_saved')
            </div>
        </div>
    </div>
    @endif
</div>
