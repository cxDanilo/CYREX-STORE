@extends('layouts.app')

@php
  // El título/descripción varían según lo que se esté mirando (categoría
  // o búsqueda) en vez de ser siempre el mismo "Tienda — Cyrex Store" —
  // sin esto, Google no tenía forma de diferenciar una página de "Mouse"
  // de una de "Teclados" ni de una búsqueda por marca (ej. /tienda?q=ajazz).
  $seoTitle = 'Tienda — Cyrex Store';
  $seoDescription = 'Componentes y periféricos gamer en Cyrex Store — envíos a Santa Cruz y Cochabamba, Bolivia.';

  if ($activeCategory) {
      $seoTitle = $activeCategory->name.' — Cyrex Store';
      $seoDescription = 'Comprá '.$activeCategory->name.' en Cyrex Store Bolivia. Envíos a Santa Cruz y Cochabamba.';
  } elseif (request()->filled('q')) {
      $seoTitle = '"'.request('q').'" — Resultados en Cyrex Store';
      $seoDescription = 'Productos de "'.request('q').'" disponibles en Cyrex Store Bolivia.';
  }
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

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
