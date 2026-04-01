@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp
<div class="row g-5 g-xl-8">
	<div class="col-12">
		<div class="card card-flush mb-5 mb-xl-10">
			<div class="card-header pt-7">
				<div class="card-title">
					<h3 class="fw-bold text-gray-900 m-0">@lang('lang_v1.more_info')</h3>
				</div>
			</div>
			<div class="card-body pt-0">
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.dob')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">@if(!empty($user->dob)) {{ format_date_value($user->dob) }} @endif</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.gender')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">@if(!empty($user->gender)) @lang('lang_v1.' .$user->gender) @endif</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.marital_status')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">@if(!empty($user->marital_status)) @lang('lang_v1.' .$user->marital_status) @endif</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.blood_group')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->blood_group ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.mobile_number')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->contact_number ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('business.alternate_number')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->alt_number ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-10">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.family_contact_number')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->family_number ?? ''}}</span>
					</div>
				</div>

				<div class="separator separator-dashed my-8"></div>

				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.fb_link')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->fb_link ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.twitter_link')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->twitter_link ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.social_media', ['number' => 1])</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->social_media_1 ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-10">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.social_media', ['number' => 2])</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->social_media_2 ?? ''}}</span>
					</div>
				</div>

				<div class="separator separator-dashed my-8"></div>

				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">{{ $custom_labels['user']['custom_field_1'] ?? __('lang_v1.user_custom_field1' )}}</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->custom_field_1 ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">{{ $custom_labels['user']['custom_field_2'] ?? __('lang_v1.user_custom_field2' )}}</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->custom_field_2 ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">{{ $custom_labels['user']['custom_field_3'] ?? __('lang_v1.user_custom_field3' )}}</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->custom_field_3 ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-10">
					<label class="col-lg-4 fw-semibold text-muted">{{ $custom_labels['user']['custom_field_4'] ?? __('lang_v1.user_custom_field4' )}}</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->custom_field_4 ?? ''}}</span>
					</div>
				</div>

				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.id_proof_name')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->id_proof_name ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-10">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.id_proof_number')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$user->id_proof_number ?? ''}}</span>
					</div>
				</div>

				<div class="separator separator-dashed my-8"></div>

				<div class="row g-5 mb-10">
					<div class="col-lg-6">
						<div class="rounded border border-gray-300 border-dashed p-5 h-100">
							<div class="fw-bold text-gray-900 mb-3">@lang('lang_v1.permanent_address')</div>
							<div class="text-gray-700 fs-6 lh-lg">{{$user->permanent_address ?? ''}}</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="rounded border border-gray-300 border-dashed p-5 h-100">
							<div class="fw-bold text-gray-900 mb-3">@lang('lang_v1.current_address')</div>
							<div class="text-gray-700 fs-6 lh-lg">{{$user->current_address ?? ''}}</div>
						</div>
					</div>
				</div>

				<div class="separator separator-dashed my-8"></div>

				<div class="mb-5">
					<h3 class="fw-bold text-gray-900 mb-1">@lang('lang_v1.bank_details')</h3>
				</div>
				@php
					$bank_details = !empty($user->bank_details) ? json_decode($user->bank_details, true) : [];
				@endphp
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.account_holder_name')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['account_holder_name'] ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.account_number')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['account_number'] ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.bank_name')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['bank_name'] ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.bank_code')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['bank_code'] ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-7">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.branch')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['branch'] ?? ''}}</span>
					</div>
				</div>
				<div class="row mb-10">
					<label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.tax_payer_id')</label>
					<div class="col-lg-8">
						<span class="fw-bold fs-6 text-gray-800">{{$bank_details['tax_payer_id'] ?? ''}}</span>
					</div>
				</div>

				@if(!empty($view_partials))
					<div class="separator separator-dashed my-8"></div>
					@foreach($view_partials as $partial)
						{!! $partial !!}
					@endforeach
				@endif
			</div>
		</div>
	</div>
</div>
