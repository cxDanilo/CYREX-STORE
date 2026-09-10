@extends('layouts.app')

@section('title', 'Armados de la comunidad — Cyrex Store')
@section('meta_description', 'PCs armadas por otros clientes con nuestro armador — mirá combinaciones reales antes de armar la tuya.')

@section('content')

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / Armados de la comunidad
</div>

<div class="page-head wrap">
  <div class="cat-eyebrow">Comunidad</div>
  <h1>Armados de la comunidad</h1>
  <p style="color:var(--text-secondary);font-size:14.5px;max-width:640px;margin-top:10px;line-height:1.6;">
    PCs armadas por otros clientes con <a href="{{ route('pc-builder') }}">Arma tu PC</a> — mirá combinaciones reales para inspirarte, o <a href="{{ route('pc-builder') }}">publicá la tuya</a>.
  </p>
</div>

<div class="wrap">
  @if($builds->isEmpty())
    <p style="color:var(--text-secondary);">Todavía nadie publicó un armado — ¡sé el primero!</p>
  @else
    <div class="build-grid" data-reveal-group>
      @foreach($builds as $build)
        <a class="build-card" href="{{ route('saved-builds.show', $build) }}" data-reveal>
          <div class="build-card-head">
            <span class="build-card-name">{{ $build->visitor_name }}</span>
            @if($build->platform)
              <span class="build-card-platform">{{ $build->platform }}</span>
            @endif
          </div>
          <div class="build-card-meta">{{ $build->items_count }} {{ Str::plural('pieza', $build->items_count) }}{{ $build->wants_assembly ? ' · con armado' : '' }}</div>
          <div class="build-card-price">${{ number_format($build->total_usd, 2) }}</div>
          <div class="build-card-foot">Publicado {{ $build->approved_at?->diffForHumans() ?? $build->created_at->diffForHumans() }}</div>
        </a>
      @endforeach
    </div>

    <div class="pagination-links" style="margin-top:24px;">
      {{ $builds->links('partials.pagination') }}
    </div>
  @endif
</div>

@endsection
