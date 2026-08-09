@extends('layouts.app')

@php
  // El texto de esta página es editable desde Admin → Páginas (slug
  // "404", bloque hero_simple) — estos valores son solo el respaldo por
  // si esa página no existe o se borró por error.
  $block404 = \App\Models\Page::published()->where('slug', '404')->with('blocks')->first()?->blocks->first();
  $d404 = array_merge([
      'eyebrow' => 'Error 404',
      'titulo' => 'Esta página se quedó sin señal',
      'subtitulo' => 'El link que seguiste no existe o se movió de lugar. Probá volver al inicio o directo a la tienda.',
      'cta_label' => 'Volver al inicio',
      'cta_url' => route('home'),
      'cta2_label' => 'Ver tienda',
      'cta2_url' => route('shop'),
      'personaje_url' => '',
  ], $block404?->data ?? []);
@endphp

@section('title', ($d404['titulo'] ?: 'Página no encontrada') . ' — Cyrex Store')

@section('content')

<div class="wrap error-404">
  <img src="{{ $d404['personaje_url'] ?: asset('favicon-512.png') }}" alt="" class="error-404-mascot">
  @if(!empty($d404['eyebrow']))<div class="cat-eyebrow">{{ $d404['eyebrow'] }}</div>@endif
  <h1>{{ $d404['titulo'] }}</h1>
  <p>{{ $d404['subtitulo'] }}</p>
  <div class="error-404-actions">
    @if(!empty($d404['cta_label']) && !empty($d404['cta_url']))
      <a href="{{ $d404['cta_url'] }}" class="btn-gold" style="text-decoration:none;">{{ $d404['cta_label'] }}</a>
    @endif
    @if(!empty($d404['cta2_label']) && !empty($d404['cta2_url']))
      <a href="{{ $d404['cta2_url'] }}" class="btn-outline-gold">{{ $d404['cta2_label'] }}</a>
    @endif
  </div>
</div>

@endsection
