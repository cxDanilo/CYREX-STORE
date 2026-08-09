@extends('layouts.app')

@php
  // El contenido de esta página es editable desde Admin → Páginas
  // (slug "404") — se renderiza con el mismo motor de bloques que
  // cualquier otra página (\App\Support\PageRenderer), así lo que se ve
  // en el editor es exactamente lo que se ve en el sitio. Si esa página
  // no existe o no está publicada, cae a un aviso mínimo sin depender
  // de datos que podrían faltar.
  $page404 = \App\Models\Page::published()->where('slug', '404')->with('blocks')->first();
@endphp

@section('title', ($page404?->title ?: 'Página no encontrada') . ' — Cyrex Store')

@section('content')

@if($page404)
  {!! \App\Support\PageRenderer::render($page404) !!}
@else
  <div class="wrap error-404">
    <img src="{{ asset('favicon-512.png') }}" alt="" class="error-404-mascot">
    <div class="cat-eyebrow">Error 404</div>
    <h1>Esta página se quedó sin señal</h1>
    <p>El link que seguiste no existe o se movió de lugar. Probá volver al inicio o directo a la tienda.</p>
    <div class="error-404-actions">
      <a href="{{ route('home') }}" class="btn-gold" style="text-decoration:none;">Volver al inicio</a>
      <a href="{{ route('shop') }}" class="btn-outline-gold">Ver tienda</a>
    </div>
  </div>
@endif

@endsection
