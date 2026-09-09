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
<script src="{{ asset('js/page-nav.js') }}?v={{ filemtime(public_path('js/page-nav.js')) }}"></script>
{{-- Esta página no tiene ningún [data-reveal] propio — se carga igual
     para que window.initScrollReveal ya exista si desde acá se navega
     (suave, vía page-nav.js) hacia un producto, que sí los tiene. --}}
<script src="{{ asset('js/scroll-reveal.js') }}?v={{ filemtime(public_path('js/scroll-reveal.js')) }}"></script>
@endsection
