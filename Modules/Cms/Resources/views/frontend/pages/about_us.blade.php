@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.about_us'))
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')
    @include('cms::frontend.pages.partials.decor.about_body')
@endsection
