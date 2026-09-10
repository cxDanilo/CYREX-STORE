@extends('layouts.app')

@section('title', 'Armado de ' . $build->visitor_name . ' — Cyrex Store')
@section('meta_description', 'Mirá el armado de PC que publicó ' . $build->visitor_name . ' con Arma tu PC.')

@section('content')

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('saved-builds.index') }}">Armados de la comunidad</a> / {{ $build->visitor_name }}
</div>

<div class="wrap" style="max-width:640px;">

  @if(session('justPublished'))
    <div class="pcb-issue-pill {{ $build->status === 'approved' ? 'ok' : 'warn' }}" style="margin-bottom:20px;">
      @if($build->status === 'approved')
        <span>✓</span> <span>¡Tu armado ya está publicado! Compartí este link con quien quieras.</span>
      @else
        <span>⚠</span> <span>¡Listo! Tu armado va a aparecer en la galería apenas lo revisemos. Guardá este link para verlo mientras tanto.</span>
      @endif
    </div>
  @endif

  <div style="margin-bottom:24px;">
    <div class="cat-eyebrow">Armado de la comunidad</div>
    <h1>{{ $build->visitor_name }}</h1>
    @if($build->platform)
      <p style="color:var(--text-secondary);font-size:14.5px;margin-top:6px;">Plataforma {{ $build->platform }}</p>
    @endif
  </div>

  <div>
    @foreach($build->items as $item)
      <div class="build-detail-item">
        <div class="build-detail-media">
          @if($item->product_image_url)
            <img src="{{ $item->product_image_url }}" alt="" loading="lazy" onload="markCardImageLoaded(this)" onerror="markCardImageLoaded(this)">
          @endif
        </div>
        <div class="build-detail-name">
          <div>{{ $item->product_name }}{{ $item->qty > 1 ? ' ×' . $item->qty : '' }}</div>
          <div class="build-detail-type">{{ config('pc_builder.component_types')[$item->type] ?? $item->type }}</div>
        </div>
        <div class="build-detail-price">${{ number_format($item->lineTotal(), 2) }}</div>
      </div>
    @endforeach

    @if($build->wants_assembly)
      <div class="build-detail-item">
        <div class="build-detail-media"></div>
        <div class="build-detail-name">
          <div>Armado e instalación</div>
          <div class="build-detail-type">Servicio Cyrex</div>
        </div>
        <div class="build-detail-price">${{ number_format($build->assembly_fee_usd, 2) }}</div>
      </div>
    @endif
  </div>

  <div class="pcb-summary-total" style="margin-top:10px;"><span>Total</span><span class="v">${{ number_format($build->total_usd, 2) }}</span></div>
  <div class="pcb-summary-alt">≈ Bs {{ number_format($build->totalBob(), 2) }}</div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
    <a href="{{ route('pc-builder') }}" class="btn btn-primary">Arma la tuya →</a>
    <a href="{{ route('saved-builds.index') }}" class="btn">Ver más armados</a>
  </div>

</div>

@endsection
