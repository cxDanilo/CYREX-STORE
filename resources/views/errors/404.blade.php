@extends('layouts.app')

@section('title', 'Página no encontrada — Cyrex Store')

@section('content')

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

@endsection
