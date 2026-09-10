@extends('admin.layout')

@section('title', 'Armado de ' . $build->visitor_name)
@section('page-description', 'Revisá cada pieza antes de aprobar — se muestra exactamente como va a verse en la galería pública.')

@section('topbar-actions')
  <a href="{{ route('admin.saved-builds.index') }}" class="btn btn-sm">← Volver</a>
@endsection

@section('content')

<div class="admin-panel" style="max-width:640px;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
    <h3 style="margin-bottom:0;">{{ $build->visitor_name }}</h3>
    @if($build->status === 'approved')
      <span class="status-badge active">Publicado</span>
    @elseif($build->status === 'rejected')
      <span class="status-badge inactive">Rechazado</span>
    @else
      <span class="status-badge teaser">Pendiente</span>
    @endif
  </div>
  <p class="form-hint" style="margin-bottom:20px;">
    {{ $build->platform ? $build->platform . ' — ' : '' }}Publicado {{ $build->created_at->diffForHumans() }}
    @if($build->status === 'approved')
      · <a href="{{ route('saved-builds.show', $build) }}" target="_blank">Ver en la galería →</a>
    @endif
  </p>

  @foreach($build->items as $item)
    <div class="repeater-row" style="grid-template-columns:auto 1fr auto;align-items:center;margin-bottom:8px;">
      <div class="variant-thumb" style="cursor:default;">
        @if($item->product_image_url)
          <img src="{{ $item->product_image_url }}" alt="">
        @else
          <span class="variant-thumb-empty">?</span>
        @endif
      </div>
      <div>
        <div>{{ $item->product_name }}</div>
        <div style="color:var(--text-muted);font-size:12px;">{{ config('pc_builder.component_types')[$item->type] ?? $item->type }}@if($item->qty > 1) · ×{{ $item->qty }}@endif</div>
      </div>
      <div class="mono" style="color:var(--text-secondary);">${{ number_format($item->lineTotal(), 2) }}</div>
    </div>
  @endforeach

  @if($build->wants_assembly)
    <div class="repeater-row" style="grid-template-columns:auto 1fr auto;align-items:center;margin-bottom:8px;">
      <div></div>
      <div>Armado e instalación</div>
      <div class="mono" style="color:var(--text-secondary);">${{ number_format($build->assembly_fee_usd, 2) }}</div>
    </div>
  @endif

  <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:14px;margin-top:10px;font-weight:600;">
    <span>Total</span>
    <span class="mono">${{ number_format($build->total_usd, 2) }}</span>
  </div>

  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
    @if($build->status !== 'approved')
      <form method="POST" action="{{ route('admin.saved-builds.approve', $build) }}">
        @csrf @method('PATCH')
        <button type="submit" class="btn btn-primary">Aprobar y publicar</button>
      </form>
    @endif
    @if($build->status !== 'rejected')
      <form method="POST" action="{{ route('admin.saved-builds.reject', $build) }}">
        @csrf @method('PATCH')
        <button type="submit" class="btn">Rechazar</button>
      </form>
    @endif
    <form method="POST" action="{{ route('admin.saved-builds.destroy', $build) }}" onsubmit="return confirm('¿Eliminar el armado de {{ $build->visitor_name }}? Esta acción no se puede deshacer.');">
      @csrf @method('DELETE')
      <button type="submit" class="btn btn-danger">Eliminar</button>
    </form>
  </div>
</div>

@endsection
