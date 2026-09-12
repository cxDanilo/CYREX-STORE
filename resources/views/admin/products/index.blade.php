@extends('admin.layout')

@section('title', 'Productos')
@section('page-description', 'Gestiona el catálogo: precios, stock, ofertas y visibilidad de cada producto.')

@section('topbar-actions')
  <a href="{{ route('admin.productos.create') }}" class="btn btn-primary" data-tour="producto-nuevo-btn">+ Nuevo producto</a>
@endsection

@section('content')

<form method="GET" class="admin-search" data-live-search>
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre...">
  <button type="submit" class="btn btn-sm">Buscar</button>
</form>

<div data-live-search-results>
  @include('admin.products._table')
</div>

@endsection

@section('scripts')
  <script src="{{ asset('js/admin-live-search.js') }}?v={{ filemtime(public_path('js/admin-live-search.js')) }}"></script>
@endsection
