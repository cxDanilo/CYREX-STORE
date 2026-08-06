@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title) . ' — Cyrex Store')

@if($page->meta_description)
@section('meta_description', $page->meta_description)
@endif

@section('content')
{!! \App\Support\PageRenderer::render($page) !!}
@endsection
