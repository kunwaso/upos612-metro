@extends('cms::frontend.layouts.app')
@section('title', $pageTitle ?? __('cms::lang.storefront_request_quote'))
@section('meta')
    <meta name="description" content="{{ e($metaDescription ?? '') }}">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.rfq_body')
@endsection
