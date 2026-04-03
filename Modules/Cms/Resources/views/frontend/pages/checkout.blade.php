@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.storefront_checkout'))
@section('meta')
    <meta name="description" content="">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.checkout_body')
@endsection
