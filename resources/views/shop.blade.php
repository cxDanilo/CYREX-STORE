@extends('layouts.app')

@section('title', 'Tienda — Cyrex Store')

@section('content')

<div class="page-head wrap {{ $shopBannerImage ? 'has-banner' : '' }}" @if($shopBannerImage) style="--shop-banner-image:url('{{ $shopBannerImage }}');" @endif>
  <div class="breadcrumb">
    <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop') }}">Tienda</a>
    @if($activeCategory) / {{ $activeCategory->name }} @endif
  </div>
  <h1>{{ $activeCategory ? $activeCategory->name : 'Tienda' }}</h1>
</div>

<div class="wrap shop-layout">
  <div class="shop-main">
    @include('partials.shop-results')
  </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/shop-ajax.js') }}?v={{ filemtime(public_path('js/shop-ajax.js')) }}"></script>
@endsection
