@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.storefront_cart'))
@section('meta')
    <meta name="description" content="">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.cart_body')
@endsection
