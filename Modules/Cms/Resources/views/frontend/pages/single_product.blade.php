@extends('cms::frontend.layouts.app')
@section('title', __('sale.product'))
@section('meta')
    <meta name="description" content="">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.single_product_body')
@endsection
