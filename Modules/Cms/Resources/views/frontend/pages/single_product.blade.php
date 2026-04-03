@extends('cms::frontend.layouts.app')
@section('title', $pageTitle ?? __('sale.product'))
@section('meta')
    <meta name="description" content="{{ e($metaDescription ?? '') }}">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.single_product_body')
@endsection
