<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-8 col-md-6">
            <div class="form-group">
                {!! Form::label('transcript_translation_engine', __('essentials::lang.translation_engine') . ':') !!}
                {!! Form::select(
                    'transcript_translation_engine',
                    [
                        'py_googletrans' => __('essentials::lang.translation_engine_py_googletrans'),
                        'aichat' => __('essentials::lang.translation_engine_aichat'),
                    ],
                    $settings['transcript_translation_engine'] ?? env('ESSENTIALS_TRANSCRIPT_TRANSLATION_DEFAULT_ENGINE', 'py_googletrans'),
                    ['class' => 'form-control']
                ) !!}
                <p class="help-block">
                    {!! __('essentials::lang.translation_engine_help') !!}
                </p>
            </div>
        </div>
    </div>
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
