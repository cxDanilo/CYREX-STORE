@extends('admin.layout')

@section('title', 'Página de '.$brand->name)
@section('page-description', 'Contenido de /marcas/'.($brand->slug ?: '...').' — las secciones sin contenido no se muestran solas en la página pública.')

@php
  $pd = $brand->page_data ?? [];
  $hero = $pd['hero'] ?? [];
  $editorial = $pd['editorial'] ?? [];
  $finderCards = $pd['finder_cards'] ?? [];
  $exploreCategories = $pd['explore_categories'] ?? [];
  $selection = $pd['selection'] ?? [];
  $comparisonRows = $pd['comparison']['rows'] ?? [];
  $contentItems = $pd['content_items'] ?? [];
  $communityPosts = $pd['community_posts'] ?? [];
@endphp

@section('content')

<form method="POST" action="{{ route('admin.marcas.update', $brand) }}" enctype="multipart/form-data" class="admin-form brand-page-form">
  @csrf
  @method('PUT')

  <div class="form-section">
    <h3>Datos básicos</h3>
    <div class="form-row">
      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $brand->name) }}" required>
        @error('name')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div class="form-group">
        <label for="slug">Slug (URL: /marcas/...)</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" placeholder="{{ \Illuminate\Support\Str::slug($brand->name) }}">
        @error('slug')<div class="error">{{ $message }}</div>@enderror
        <div class="form-hint">Vacío = se genera del nombre.</div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="logo">Logo</label>
        <input type="file" id="logo" name="logo" accept="image/*">
        @if($brand->logo_url)
          <img src="{{ $brand->logo_url }}" alt="" style="height:32px;margin-top:8px;display:block;">
        @endif
        @error('logo')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div class="form-group">
        <label for="accent_color">Color de acento</label>
        <input type="color" id="accent_color" name="accent_color" value="{{ old('accent_color', $brand->accent_color ?: '#FFD900') }}" style="width:70px;height:42px;padding:4px;">
        <div class="form-hint">Se usa con moderación (bordes, tags) — Cyrex sigue siendo dorado.</div>
        @error('accent_color')<div class="error">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="form-group">
      <input type="hidden" name="is_page_published" value="0">
      <label class="switch">
        <input type="checkbox" name="is_page_published" value="1" {{ old('is_page_published', $brand->is_page_published) ? 'checked' : '' }}>
        <span class="switch-track"></span>
        <span class="switch-label">Publicar /marcas/{{ $brand->slug ?: \Illuminate\Support\Str::slug($brand->name) }}</span>
      </label>
    </div>
  </div>

  {{-- ============ HERO ============ --}}
  <div class="form-section">
    <h3>1. Hero</h3>
    <div class="form-group">
      <label>Headline (debajo del nombre)</label>
      <input type="text" name="hero[subheadline]" value="{{ old('hero.subheadline', $hero['subheadline'] ?? '') }}" placeholder="Periféricos diferentes para setups diferentes.">
    </div>
    <div class="form-group">
      <label>Descripción corta</label>
      <textarea name="hero[description]" rows="2">{{ old('hero.description', $hero['description'] ?? '') }}</textarea>
    </div>
    <div class="form-group" x-data="brandProductPicker(@js($productsForPicker), @js(array_map('strval', $hero['product_ids'] ?? [])))">
      <label>Productos del hero (hasta 3, forman la composición visual)</label>
      @include('admin.brands._product-picker', ['fieldName' => 'hero[product_ids][]'])
    </div>
  </div>

  {{-- ============ EDITORIAL ============ --}}
  <div class="form-section">
    <h3>2. {{ Str::upper($brand->name) }} en Cyrex</h3>
    <div class="form-row">
      <div class="form-group">
        <label>Eyebrow</label>
        <input type="text" name="editorial[eyebrow]" value="{{ old('editorial.eyebrow', $editorial['eyebrow'] ?? Str::upper($brand->name).' EN CYREX') }}">
      </div>
      <div class="form-group">
        <label>Producto de la foto</label>
        <select name="editorial[image_product_id]">
          <option value="">— Ninguno —</option>
          @foreach($productsForPicker as $p)
            <option value="{{ $p['id'] }}" {{ (string) old('editorial.image_product_id', $editorial['image_product_id'] ?? '') === (string) $p['id'] ? 'selected' : '' }}>{{ $p['name'] }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Título</label>
      <input type="text" name="editorial[title]" value="{{ old('editorial.title', $editorial['title'] ?? '') }}" placeholder="No todos buscan el mismo teclado. Y ahí está lo interesante.">
    </div>
    <div class="form-group">
      <label>Texto (por qué Cyrex eligió trabajar con esta marca)</label>
      <textarea name="editorial[body]" rows="4">{{ old('editorial.body', $editorial['body'] ?? '') }}</textarea>
      <div class="form-hint">Evitar "la mejor marca"/"la número uno" — el tono es "seleccionamos esto para distintos usuarios", no venta agresiva.</div>
    </div>
  </div>

  {{-- ============ FINDER CARDS ============ --}}
  <div class="form-section" x-data="{
        cards: {{ collect($finderCards)->map(fn ($c) => array_merge($c, ['_q' => '']))->toJson() ?: '[]' }},
        allProducts: @js($productsForPicker),
        resultsFor(card) {
          const term = (card._q || '').trim().toLowerCase();
          if (!term) return [];
          return this.allProducts.filter(p => !card.product_ids.includes(p.id) && p.name.toLowerCase().includes(term)).slice(0, 6);
        },
        addProduct(card, id) { if (!card.product_ids.includes(id)) card.product_ids.push(id); card._q = ''; },
        removeProduct(card, id) { card.product_ids = card.product_ids.filter(x => x !== id); },
        productName(id) { const p = this.allProducts.find(p => p.id === id); return p ? p.name : ('#' + id); },
     }">
    <h3>3. ¿Qué estás buscando?</h3>
    <p class="form-hint" style="margin-bottom:14px;">Tarjetas tipo "Mi primer teclado" / "Quiero mejorar mi setup" / "Gaming competitivo" — cada una con sus productos.</p>
    <template x-for="(card, i) in cards" :key="i">
      <div class="admin-panel" style="margin-bottom:14px;padding:16px;">
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;">
            <label>Título</label>
            <input type="text" x-model="card.title" :name="'finder_cards[' + i + '][title]'">
          </div>
          <div class="form-group" style="margin-bottom:10px;">
            <label>Texto del botón</label>
            <input type="text" x-model="card.cta_label" :name="'finder_cards[' + i + '][cta_label]'" placeholder="Ver opciones →">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:10px;">
          <label>Descripción</label>
          <textarea x-model="card.description" :name="'finder_cards[' + i + '][description]'" rows="2"></textarea>
        </div>
        <div class="form-group" style="margin-bottom:10px;">
          <label>Productos</label>
          <div style="position:relative;" @click.outside="card._q = ''">
            <input type="text" x-model="card._q" class="admin-product-search" placeholder="Buscar producto..." autocomplete="off">
            <div class="admin-search-results" x-show="resultsFor(card).length" x-cloak>
              <template x-for="p in resultsFor(card)" :key="p.id">
                <button type="button" class="admin-search-result-row" @click="addProduct(card, p.id)">
                  <span x-text="p.name"></span>
                  <span class="admin-search-result-price mono" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></span>
                </button>
              </template>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
            <template x-for="id in card.product_ids" :key="id">
              <span class="brand-admin-chip">
                <span x-text="productName(id)"></span>
                <button type="button" @click="removeProduct(card, id)">×</button>
                <input type="hidden" :name="'finder_cards[' + i + '][product_ids][]'" :value="id">
              </span>
            </template>
          </div>
        </div>
        <button type="button" class="repeater-remove" style="width:auto;padding:0 14px;height:32px;" @click="cards.splice(i, 1)">Quitar tarjeta</button>
      </div>
    </template>
    <button type="button" class="btn btn-sm" @click="cards.push({title:'',description:'',cta_label:'',product_ids:[],_q:''})">+ Agregar tarjeta</button>
  </div>

  {{-- ============ EXPLORE CATEGORIES ============ --}}
  <div class="form-section" x-data="{ items: {{ collect($exploreCategories)->toJson() ?: '[]' }} }">
    <h3>4. Explora {{ Str::upper($brand->name) }}</h3>
    <p class="form-hint" style="margin-bottom:14px;">Bloques grandes por tipo de producto (ej. Teclados / Mouse / Controles).</p>
    <template x-for="(item, i) in items" :key="i">
      <div class="admin-panel" style="margin-bottom:14px;padding:16px;">
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;">
            <label>Título</label>
            <input type="text" x-model="item.title" :name="'explore_categories[' + i + '][title]'">
          </div>
          <div class="form-group" style="margin-bottom:10px;">
            <label>Categoría</label>
            <select x-model="item.category_slug" :name="'explore_categories[' + i + '][category_slug]'">
              <option value="">— Ninguna (va a todos los productos de la marca) —</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;">
            <label>Descripción</label>
            <input type="text" x-model="item.description" :name="'explore_categories[' + i + '][description]'">
          </div>
          <div class="form-group" style="margin-bottom:10px;">
            <label>Texto del botón</label>
            <input type="text" x-model="item.cta_label" :name="'explore_categories[' + i + '][cta_label]'" placeholder="Explorar →">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:10px;">
          <label>Foto</label>
          <input type="file" :name="'explore_categories[' + i + '][image]'" accept="image/*">
          <template x-if="item.image">
            <img :src="'{{ asset('uploads') }}/' + item.image" style="height:60px;border-radius:8px;margin-top:6px;display:block;">
          </template>
        </div>
        <button type="button" class="repeater-remove" style="width:auto;padding:0 14px;height:32px;" @click="items.splice(i, 1)">Quitar bloque</button>
      </div>
    </template>
    <button type="button" class="btn btn-sm" @click="items.push({title:'',description:'',category_slug:'',cta_label:'',image:null})">+ Agregar bloque</button>
  </div>

  {{-- ============ SELECTION ============ --}}
  <div class="form-section">
    <h3>5. Cyrex Selection</h3>
    <div class="form-row">
      <div class="form-group">
        <label>Producto destacado</label>
        <select name="selection[product_id]">
          <option value="">— Ninguno —</option>
          @foreach($productsForPicker as $p)
            <option value="{{ $p['id'] }}" {{ (string) old('selection.product_id', $selection['product_id'] ?? '') === (string) $p['id'] ? 'selected' : '' }}>{{ $p['name'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>Tags (separados por coma)</label>
        <input type="text" name="selection[tags]" value="{{ old('selection.tags', implode(', ', $selection['tags'] ?? [])) }}" placeholder="75%, TRI-MODE, HOT-SWAP, RGB">
      </div>
    </div>
    <div class="form-group">
      <label>¿Por qué lo elegimos?</label>
      <textarea name="selection[why]" rows="3">{{ old('selection.why', $selection['why'] ?? '') }}</textarea>
    </div>
    <div class="form-group">
      <label>Texto del botón</label>
      <input type="text" name="selection[cta_label]" value="{{ old('selection.cta_label', $selection['cta_label'] ?? '') }}" placeholder="Conocer más →">
    </div>
  </div>

  {{-- ============ COMPARISON ============ --}}
  <div class="form-section" x-data="{ rows: {{ collect($comparisonRows)->toJson() ?: '[]' }} }">
    <h3>6. Comparador (¿Cuál es para ti?)</h3>
    <p class="form-hint" style="margin-bottom:14px;">Mínimo 2 productos para que la sección se muestre en la página.</p>
    <template x-for="(row, i) in rows" :key="i">
      <div class="admin-panel" style="margin-bottom:14px;padding:16px;">
        <div class="form-group" style="margin-bottom:10px;">
          <label>Producto</label>
          <select x-model="row.product_id" :name="'comparison_rows[' + i + '][product_id]'">
            <option value="">— Elegir —</option>
            @foreach($productsForPicker as $p)
              <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;"><label>Formato</label><input type="text" x-model="row.formato" :name="'comparison_rows[' + i + '][formato]'" placeholder="75%"></div>
          <div class="form-group" style="margin-bottom:10px;"><label>Conectividad</label><input type="text" x-model="row.conectividad" :name="'comparison_rows[' + i + '][conectividad]'" placeholder="Tri-modo"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;"><label>Para quién</label><input type="text" x-model="row.tipo_usuario" :name="'comparison_rows[' + i + '][tipo_usuario]'" placeholder="Uso diario y gaming"></div>
          <div class="form-group" style="margin-bottom:10px;"><label>Switch</label><input type="text" x-model="row.switch" :name="'comparison_rows[' + i + '][switch]'" placeholder="Hot-swap"></div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:10px;"><label>Tamaño</label><input type="text" x-model="row.tamano" :name="'comparison_rows[' + i + '][tamano]'" placeholder="Compacto"></div>
          <div class="form-group" style="margin-bottom:10px;"><label>Destacado</label><input type="text" x-model="row.destacado" :name="'comparison_rows[' + i + '][destacado]'" placeholder="RGB"></div>
        </div>
        <button type="button" class="repeater-remove" style="width:auto;padding:0 14px;height:32px;" @click="rows.splice(i, 1)">Quitar producto</button>
      </div>
    </template>
    <button type="button" class="btn btn-sm" @click="rows.push({product_id:'',formato:'',conectividad:'',tipo_usuario:'',switch:'',tamano:'',destacado:''})">+ Agregar producto al comparador</button>
  </div>

  {{-- ============ CONTENT ITEMS ============ --}}
  <div class="form-section" x-data="{ items: {{ collect($contentItems)->toJson() ?: '[]' }} }">
    <h3>7. Contenido Cyrex (videos)</h3>
    <p class="form-hint" style="margin-bottom:14px;">Links de YouTube o Vimeo — el primero de la lista se muestra grande, el resto como secundarios.</p>
    <template x-for="(item, i) in items" :key="i">
      <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;margin-bottom:10px;">
        <input type="text" x-model="item.title" :name="'content_items[' + i + '][title]'" placeholder="¿Vale la pena el AK820 Pro?">
        <input type="text" x-model="item.embed_url" :name="'content_items[' + i + '][embed_url]'" placeholder="https://youtube.com/watch?v=...">
        <button type="button" class="repeater-remove" @click="items.splice(i, 1)">×</button>
      </div>
    </template>
    <button type="button" class="btn btn-sm" @click="items.push({title:'',embed_url:''})">+ Agregar video</button>
  </div>

  {{-- ============ COMMUNITY ============ --}}
  <div class="form-section" x-data="{ posts: {{ collect($communityPosts)->toJson() ?: '[]' }} }">
    <h3>8. Comunidad</h3>
    <p class="form-hint" style="margin-bottom:14px;">Setups reales de clientes — no hace falta que todo el setup sea de esta marca.</p>
    <template x-for="(post, i) in posts" :key="i">
      <div class="admin-panel" style="margin-bottom:14px;padding:16px;">
        <div class="form-group" style="margin-bottom:10px;">
          <label>Foto</label>
          <input type="file" :name="'community_posts[' + i + '][image]'" accept="image/*">
          <template x-if="post.image">
            <img :src="'{{ asset('uploads') }}/' + post.image" style="height:60px;border-radius:8px;margin-top:6px;display:block;">
          </template>
        </div>
        <div class="form-group" style="margin-bottom:10px;">
          <label>Descripción</label>
          <input type="text" x-model="post.caption" :name="'community_posts[' + i + '][caption]'">
        </div>
        <div class="form-group" style="margin-bottom:10px;">
          <label>Productos que aparecen (separados por coma)</label>
          <input type="text" x-model="post.product_tags" :name="'community_posts[' + i + '][product_tags]'" placeholder="AJAZZ AK820 Pro, ATK A9, Monitor MSI">
        </div>
        <button type="button" class="repeater-remove" style="width:auto;padding:0 14px;height:32px;" @click="posts.splice(i, 1)">Quitar</button>
      </div>
    </template>
    <button type="button" class="btn btn-sm" @click="posts.push({image:null,caption:'',product_tags:''})">+ Agregar foto de la comunidad</button>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar página</button>
  </div>
</form>

@endsection

@section('scripts')
<script>
  // Compartido entre el picker del hero (por ahora el único fuera de un
  // repeater) -- los de los repeaters van con su lógica propia inline
  // porque necesitan mutar el "card"/"row" de un array, no una variable
  // suelta como acá.
  function brandProductPicker(allProducts, selectedIds) {
    return {
      allProducts: allProducts,
      selected: selectedIds,
      q: '',
      get searchResults() {
        const term = this.q.trim().toLowerCase();
        if (!term) return [];
        return this.allProducts.filter(p => !this.selected.includes(String(p.id)) && p.name.toLowerCase().includes(term)).slice(0, 6);
      },
      addProduct(id) {
        id = String(id);
        if (!this.selected.includes(id)) this.selected.push(id);
        this.q = '';
      },
      removeProduct(id) {
        this.selected = this.selected.filter(x => x !== String(id));
      },
      productName(id) {
        const p = this.allProducts.find(p => String(p.id) === String(id));
        return p ? p.name : ('#' + id);
      },
    };
  }
</script>
@endsection
