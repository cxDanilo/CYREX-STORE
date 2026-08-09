@extends('admin.layout')

@section('title', 'Categorías')

@section('topbar-actions')
  <a href="{{ route('admin.categorias.create') }}" class="btn btn-primary">+ Nueva categoría</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Las categorías padre son las que aparecen como principales en la tienda y en el menú flotante. Las hijas solo se muestran al pasar el mouse sobre su categoría padre. Arrastrá una fila desde <strong>⋮⋮</strong> para cambiar el orden — se guarda solo.</p>

<div class="admin-table-wrap">
  @if($categories->isEmpty())
    <div class="admin-empty">Todavía no hay categorías.</div>
  @else
    <table class="admin-table" id="categories-table">
      <thead>
        <tr>
          <th></th>
          <th></th>
          <th>Nombre</th>
          <th>Slug</th>
          <th>Productos</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="categories-root-group" data-group="root">
        @foreach($categories as $parent)
          <tr draggable="true" data-id="{{ $parent->id }}" class="cat-row">
            <td class="cat-drag-handle" title="Arrastrar para reordenar">⋮⋮</td>
            <td style="width:32px;color:var(--gold);">@include('partials.category-icon', ['icon' => $parent->icon, 'iconImage' => $parent->icon_image_url])</td>
            <td><strong>{{ $parent->name }}</strong></td>
            <td class="mono" style="color:var(--text-secondary);">{{ $parent->slug }}</td>
            <td class="mono">{{ $parent->products_count }}</td>
            <td>
              <div class="cell-actions">
                <a href="{{ route('admin.categorias.edit', $parent) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.categorias.destroy', $parent) }}" onsubmit="return confirm('¿Eliminar {{ $parent->name }}? Esta acción no se puede deshacer.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          @if($parent->children->isNotEmpty())
            <tr class="cat-children-anchor" data-parent="{{ $parent->id }}">
              <td colspan="6" style="padding:0;border:none;">
                <table class="admin-table" style="margin:0;">
                  <tbody data-group="children-{{ $parent->id }}">
                    @foreach($parent->children as $child)
                      <tr draggable="true" data-id="{{ $child->id }}" class="cat-row">
                        <td class="cat-drag-handle" style="width:32px;" title="Arrastrar para reordenar">⋮⋮</td>
                        <td style="width:32px;"></td>
                        <td style="padding-left:34px;color:var(--text-secondary);">— {{ $child->name }}</td>
                        <td class="mono" style="color:var(--text-secondary);">{{ $child->slug }}</td>
                        <td class="mono">{{ $child->products_count }}</td>
                        <td>
                          <div class="cell-actions">
                            <a href="{{ route('admin.categorias.edit', $child) }}" class="btn btn-sm">Editar</a>
                            <form method="POST" action="{{ route('admin.categorias.destroy', $child) }}" onsubmit="return confirm('¿Eliminar {{ $child->name }}? Esta acción no se puede deshacer.');">
                              @csrf @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </td>
            </tr>
          @endif
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<script>
(function () {
  const reorderUrl = @js(route('admin.categorias.reorder'));
  const csrfToken = document.querySelector('meta[name=csrf-token]').content;
  let dragging = null;

  function persistGroup(tbody) {
    const ids = Array.from(tbody.querySelectorAll(':scope > tr.cat-row')).map(tr => tr.dataset.id);
    fetch(reorderUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ ids }),
    }).catch(() => {});
  }

  // Las filas padre pueden tener una fila "ancla" justo después con la
  // tabla anidada de sus hijas — al arrastrar el padre, esa ancla tiene
  // que viajar pegada a él para no romper la agrupación visual.
  function anchorFor(row) {
    const next = row.nextElementSibling;
    return (next && next.classList.contains('cat-children-anchor') && next.dataset.parent === row.dataset.id) ? next : null;
  }

  function groupEnd(row) {
    return anchorFor(row) || row;
  }

  document.querySelectorAll('tbody[data-group]').forEach(function (tbody) {
    tbody.addEventListener('dragstart', function (e) {
      const row = e.target.closest('tr.cat-row');
      if (!row || row.parentElement !== tbody) return;
      dragging = row;
      row.classList.add('is-dragging');
    });

    tbody.addEventListener('dragend', function () {
      if (dragging) dragging.classList.remove('is-dragging');
      dragging = null;
    });

    tbody.addEventListener('dragover', function (e) {
      if (!dragging || dragging.parentElement !== tbody) return;
      e.preventDefault();
      const row = e.target.closest('tr.cat-row');
      if (!row || row === dragging || row.parentElement !== tbody) return;
      const rect = row.getBoundingClientRect();
      const before = (e.clientY - rect.top) < rect.height / 2;
      const draggingAnchor = anchorFor(dragging);
      const ref = before ? row : groupEnd(row).nextSibling;
      tbody.insertBefore(dragging, ref);
      if (draggingAnchor) tbody.insertBefore(draggingAnchor, dragging.nextSibling);
    });

    tbody.addEventListener('drop', function (e) {
      e.preventDefault();
      if (dragging) persistGroup(tbody);
    });
  });
})();
</script>

@endsection
