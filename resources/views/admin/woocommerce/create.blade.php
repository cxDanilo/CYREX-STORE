@extends('admin.layout')

@section('title', 'Importar de WooCommerce')

@section('content')

<div style="max-width:640px;">
  <p class="form-hint" style="margin-bottom:20px;">
    Sube el CSV de exportación estándar de WooCommerce (en tu tienda WooCommerce: <strong>Productos → Todos los productos → Exportar</strong>).
    No hace falta ninguna clave ni conexión — se lee directo del archivo.
  </p>

  <div class="form-section" style="margin-bottom:20px;">
    <h3>Cómo se hace el match</h3>
    <ul style="color:var(--text-secondary);font-size:13.5px;line-height:1.8;padding-left:20px;">
      <li>Si el <strong>SKU</strong> de una fila ya existe en un producto local, se actualiza ese producto en vez de crear uno nuevo — puedes correr el mismo archivo más de una vez sin duplicar nada.</li>
      <li>La <strong>categoría</strong> se busca por nombre; si no existe, se crea sola como categoría principal.</li>
      <li>La primera <strong>imagen</strong> del producto se descarga automáticamente. Si la URL falla, el producto igual se importa sin imagen — la subes después a mano.</li>
      <li>Cada fila importada queda registrada en el <a href="{{ route('admin.historial.index') }}">Historial de cambios</a>.</li>
    </ul>
  </div>

  <form method="POST" action="{{ route('admin.woocommerce.store') }}" enctype="multipart/form-data" class="admin-form">
    @csrf

    <div class="form-group">
      <label for="csv">Archivo CSV</label>
      <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
      @error('csv') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Importar</button>
    </div>
  </form>
</div>

@endsection
