@extends('layouts.app')

@section('title', 'Arma tu PC — Cyrex Store')

@section('content')

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / Arma tu PC
</div>

<div class="wrap" style="padding-block:60px 100px;max-width:640px;">
  <div class="cat-eyebrow">Arma tu pc</div>
  <h1 style="font-size:clamp(28px,4vw,42px);margin-bottom:16px;">Estamos armando el armador 🛠️</h1>
  <p style="color:var(--text-secondary);font-size:15px;line-height:1.7;margin-bottom:28px;">
    Muy pronto vas a poder elegir procesador, placa madre, RAM, gabinete, tarjeta gráfica, enfriamiento y fuente acá mismo — y te vamos a avisar al toque si algo no es compatible, antes de que lo agregues al carrito.
  </p>
  <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '59177947379') }}?text={{ urlencode('Hola! Quiero armar una PC, ¿me ayudan?') }}" target="_blank" rel="noopener" class="btn-cta-whatsapp" style="display:inline-flex;width:auto;">
    @include('partials.whatsapp-icon')
    <span>Mientras tanto, armamos tu PC por WhatsApp</span>
  </a>
</div>

@endsection
