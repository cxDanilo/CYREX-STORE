@extends('admin.layout')

@section('title', 'Resultado de la importación')

@section('content')

<div class="wc-import-summary">
  <div class="wc-import-summary-item">
    <div class="n" style="color:var(--green);">{{ $result['created'] }}</div>
    <div class="l">Productos creados</div>
  </div>
  <div class="wc-import-summary-item">
    <div class="n">{{ $result['updated'] }}</div>
    <div class="l">Productos actualizados</div>
  </div>
  <div class="wc-import-summary-item">
    <div class="n" style="color:{{ count($result['errors']) ? 'var(--red)' : 'var(--text-muted)' }};">{{ count($result['errors']) }}</div>
    <div class="l">Filas con error</div>
  </div>
</div>

@if(count($result['errors']))
  <div class="form-section" style="margin-bottom:20px;">
    <h3>Errores</h3>
    <ul style="color:var(--red);font-size:13px;line-height:1.8;padding-left:20px;">
      @foreach($result['errors'] as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

@if(count($result['warnings'] ?? []))
  <div class="form-section" style="margin-bottom:20px;">
    <h3>Avisos</h3>
    <ul style="color:var(--gold);font-size:13px;line-height:1.8;padding-left:20px;">
      @foreach($result['warnings'] as $warning)
        <li>{{ $warning }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="form-actions" style="margin-top:0;">
  <a href="{{ route('admin.productos.index') }}" class="btn btn-primary">Ver productos</a>
  <a href="{{ route('admin.woocommerce.create') }}" class="btn">Importar otro archivo</a>
</div>

@endsection
