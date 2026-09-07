@php
  // Mismo criterio que el menú principal: una categoría (o subcategoría)
  // sin ningún producto activo no se muestra — un padre sin productos
  // propios pero con al menos un hijo con productos sí cuenta.
  $categoriasConProductos = \App\Models\Category::parents()
      ->withActiveProductCounts()
      ->with(['children' => fn ($q) => $q->withActiveProductCounts()])
      ->get()
      ->filter(fn ($cat) => $cat->has_active_products || $cat->children->contains->has_active_products);
@endphp
<div class="wrap cms-cat-strip">
  @foreach($categoriasConProductos as $cat)
    <a class="cms-cat-chip" href="{{ route('shop', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
  @endforeach
</div>
