@extends('admin.layout')

@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')
@section('page-description', $product->exists ? 'Actualiza los datos, precio, imágenes y variantes de este producto.' : 'Completa los datos para publicar un producto nuevo en la tienda.')

@section('content')

@php
  // Nombre/categoría/precio ya NO llevan el atributo required en su
  // <input> — con pestañas, un campo required que queda oculto
  // (display:none en otra pestaña) hace que el navegador bloquee el
  // envío del formulario en silencio (sin mostrar ningún error, ni
  // mandar el POST) apenas se intenta enviar desde una pestaña
  // distinta a la suya. La validación real la sigue haciendo el
  // servidor (ProductController@validated) — lo único que cambia acá
  // es que el chequeo del navegador ya no puede bloquear el envío.
  //
  // Si el guardado falló por un campo en una pestaña que no es la
  // activa, el navegador nunca llega a mostrar el error — así que la
  // pestaña con el error se abre sola, en vez de que se vea como que
  // "no pasó nada" al tocar Guardar.
  $tabErrorFields = [
    'general' => ['name', 'slug', 'category_id', 'category_ids', 'category_ids.*', 'description'],
    'imagenes' => ['image', 'gallery_images.*'],
    'precio' => ['price', 'currency', 'sku', 'status', 'is_sold_out'],
    'specs' => ['compat.*'],
    'variantes' => ['variants.*'],
  ];
  $errorTab = null;
  foreach ($tabErrorFields as $tabKey => $fields) {
      if ($errors->hasAny($fields)) { $errorTab = $tabKey; break; }
  }
@endphp

<div style="display:flex;gap:32px;align-items:flex-start;"
     x-data="{
        specs: {{ collect($product->specs ?? [])->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->toJson() }},
        variants: {{ $product->relationLoaded('variants') ? $product->variants->map(fn($v) => ['id' => $v->id, 'variant_type' => $v->variant_type, 'variant_value' => $v->variant_value, 'sku' => $v->sku, 'price_override' => $v->price_override, 'image' => $v->image_url, 'imagePreview' => null, 'removeImage' => false])->toJson() : '[]' }},
        preview: @js($product->image_url),
        name: @js(old('name', $product->name) ?? ''),
        description: @js(old('description', $product->description) ?? ''),
        price: @js((string) old('price', $product->price ?? '')),
        currency: @js(old('currency', $product->currency ?? 'USD')),
        categoryId: @js((string) old('category_id', $product->category_id ?? '')),
        categories: @js($categories->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name, 'label' => ($c->parent_id ? '— ' : '').$c->name, 'componentType' => $c->component_type])->values()),
        get categoryName() { return (this.categories.find(c => c.id === this.categoryId) || {}).name || ''; },
        componentFields: @js(\App\Support\PcBuilderFields::resolved()),
        pcBuilderTypes: @js(array_keys(config('pc_builder.component_types'))),
        get componentType() { return (this.categories.find(c => c.id === this.categoryId) || {}).componentType || null; },
        get isPcPiece() { return this.pcBuilderTypes.includes(this.componentType); },
        get activeFields() { return this.componentType ? (this.componentFields[this.componentType] || {}) : {}; },
        compat: {{ Js::from((object) (old('compat', $product->compat) ?: [])) }},
        compatErrors: @js(collect($errors->messages())->filter(fn ($v, $k) => str_starts_with($k, 'compat.'))->mapWithKeys(fn ($v, $k) => [substr($k, strlen('compat.')) => $v[0]])),
        tab: {!! $errorTab ? "'{$errorTab}'" : "localStorage.getItem('cyrexAdminProductTab') || 'general'" !!},
     }"
     x-init="$watch('tab', v => localStorage.setItem('cyrexAdminProductTab', v))">
  <form method="POST" action="{{ $product->exists ? route('admin.productos.update', $product) : route('admin.productos.store') }}" enctype="multipart/form-data" style="flex:1;min-width:0;">
    @csrf
    @if($product->exists) @method('PUT') @endif
    @if($backUrl)
      <input type="hidden" name="back" value="{{ $backUrl }}">
    @endif

    <div class="admin-tabs" style="margin-bottom:22px;">
      <button type="button" class="admin-tab" :class="{ active: tab === 'general' }" @click="tab = 'general'">General</button>
      <button type="button" class="admin-tab" :class="{ active: tab === 'imagenes' }" @click="tab = 'imagenes'">Imágenes</button>
      <button type="button" class="admin-tab" :class="{ active: tab === 'precio' }" @click="tab = 'precio'">Precio y stock</button>
      <button type="button" class="admin-tab" :class="{ active: tab === 'specs' }" @click="tab = 'specs'">Especificaciones</button>
      <button type="button" class="admin-tab" :class="{ active: tab === 'variantes' }" @click="tab = 'variantes'">Variantes</button>
    </div>

    <div class="admin-form" x-show="tab === 'general'" x-cloak>
    <div class="form-section">
      <h3>Información general</h3>

      <div class="form-group">
        <label for="name">Nombre <span class="required-mark">*</span></label>
        <input type="text" id="name" name="name" x-model="name"
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        <div class="form-hint">Si viene en varios colores/tamaños, no los pongas acá — ej. "Kumara", no "Kumara Negro". El color va abajo, en Variantes, así el cliente elige uno sin salir de la ficha.</div>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug (URL)</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $product->slug) }}"
               x-on:input="$event.target.dataset.touched = true">
        <div class="form-hint">Se usa en la URL: /producto/<span x-text="$refs.slug ? $refs.slug.value : ''"></span></div>
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" x-data="{ catOpen: false, catQuery: '' }" x-init="catQuery = categoryName">
        <label for="category_search">Categoría <span class="required-mark">*</span></label>
        <div class="combobox" @click.outside="catOpen = false; catQuery = categoryName">
          <input type="text" id="category_search" autocomplete="off"
                 x-model="catQuery"
                 @focus="catOpen = true; $el.select()"
                 @keydown.escape="catOpen = false; catQuery = categoryName"
                 @keydown.enter.prevent="
                    const match = categories.filter(c => !catQuery || c.name.toLowerCase().includes(catQuery.toLowerCase()))[0];
                    if (match) { categoryId = match.id; catQuery = match.name; catOpen = false; }
                 "
                 placeholder="Escribe para buscar, ej. &quot;fuente&quot;...">
          <div class="combobox-menu" x-show="catOpen" x-cloak>
            <template x-for="cat in categories.filter(c => !catQuery || c.name.toLowerCase().includes(catQuery.toLowerCase()))" :key="cat.id">
              <button type="button" class="combobox-option" :class="{ 'is-active': cat.id === categoryId }"
                      @click="categoryId = cat.id; catQuery = cat.name; catOpen = false"
                      x-text="cat.label"></button>
            </template>
            <div class="combobox-empty" x-show="!categories.filter(c => !catQuery || c.name.toLowerCase().includes(catQuery.toLowerCase())).length">Sin resultados</div>
          </div>
        </div>
        <input type="hidden" name="category_id" :value="categoryId">
        @error('category_id') <div class="error">{{ $message }}</div> @enderror
      </div>

      @php
        $selectedCategoryIds = old('category_ids', $product->exists ? $product->categories->pluck('id')->all() : []);
      @endphp
      <div class="form-group" x-data="{ open: {{ count($selectedCategoryIds) ? 'true' : 'false' }} }">
        <label>Categorías adicionales (opcional)</label>
        <div class="form-hint" style="margin-bottom:8px;">Además de su categoría de arriba, este producto también se va a listar en las que marques acá — por ejemplo, para que aparezca en una categoría "Promociones" sin dejar de ser lo que es.</div>

        <button type="button" class="btn btn-sm" x-show="!open" @click="open = true">+ Agregar categorías adicionales</button>

        <div x-show="open" x-cloak>
          <div style="max-height:260px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;padding:14px;">
            @foreach($categories as $cat)
              @continue($cat->id === $product->category_id)
              <label class="combo-product-row">
                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                       {{ in_array($cat->id, $selectedCategoryIds) ? 'checked' : '' }}>
                <span class="combo-product-row-info">
                  <span class="combo-product-row-name">{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</span>
                </span>
              </label>
            @endforeach
          </div>
          <button type="button" class="btn btn-sm btn-ghost" style="margin-top:8px;" @click="open = false">Cerrar</button>
        </div>
        @error('category_ids') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea id="description" name="description" rows="3" maxlength="500" x-model="description"></textarea>
        <div class="form-hint" style="text-align:right;" x-text="description.length + '/500'"></div>
        <div class="form-hint">Para specs técnicas (socket, RAM, watts, etc.) usa los campos de Compatibilidad más abajo, no esta descripción — se muestran juntos en la página del producto.</div>
      </div>
    </div>
    </div>

    <div class="admin-form" x-show="tab === 'imagenes'" x-cloak>
    <div class="form-section">
      <h3>Imagen del producto <span class="required-mark">*</span></h3>
      <div class="form-group">
        <div class="file-upload">
          <div class="file-upload-thumb">
            <img :src="preview" x-show="preview" alt="">
            <svg x-show="!preview" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 16l4.5-6 3.5 4.5 2.5-3L20 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>
          </div>
          <div class="file-upload-info">
            <div class="file-upload-name" x-text="preview ? 'Imagen cargada' : 'Ningún archivo elegido'"></div>
            <div class="file-upload-meta">JPG, PNG o WEBP, máx. 4 MB. Obligatoria — salvo que ya le hayas puesto foto a alguna variante en la pestaña Variantes, ahí se usa esa en todos lados.</div>
          </div>
          <div class="file-upload-actions">
            <button type="button" class="btn btn-sm" @click="$refs.imageInput.click()" x-text="preview ? 'Reemplazar' : 'Subir imagen'"></button>
            @if($product->image)
              <button type="button" class="btn btn-sm btn-ghost" @click="preview = null; $refs.removeImage.checked = true">Quitar</button>
            @endif
          </div>
          <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" class="sr-only-file" x-ref="imageInput"
                 x-on:change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview">
        </div>
        @if($product->image)
          <input type="checkbox" name="remove_image" value="1" x-ref="removeImage" class="sr-only-file" x-on:change="if($event.target.checked) preview = null">
        @endif
        @error('image') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Galería de imágenes adicionales</h3>
      <div class="form-hint" style="margin-bottom:10px;">Se muestran en la página del producto además de la imagen principal — el cliente puede pasar entre todas. JPG, PNG o WEBP, máx. 4 MB cada una.</div>

      @if($product->exists && $product->images->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
          @foreach($product->images as $image)
            <div style="width:88px;">
              <div style="width:88px;height:88px;border-radius:10px;background:var(--bg-elevated-2);border:1px solid var(--border);overflow:hidden;">
                <img src="{{ $image->url }}" style="width:100%;height:100%;object-fit:cover;" alt="">
              </div>
              <label style="display:flex;align-items:center;gap:5px;margin-top:6px;font-size:11.5px;color:var(--text-secondary);">
                <input type="checkbox" name="remove_gallery_images[]" value="{{ $image->id }}">
                Quitar
              </label>
            </div>
          @endforeach
        </div>
      @endif

      <div class="form-group" x-data="{ newGalleryPreviews: [] }">
        <label for="gallery_images">Agregar imágenes</label>
        <input type="file" id="gallery_images" name="gallery_images[]" accept="image/png,image/jpeg,image/webp" multiple
               x-on:change="newGalleryPreviews = Array.from($event.target.files).map(f => URL.createObjectURL(f))">
        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:12px;" x-show="newGalleryPreviews.length" x-cloak>
          <template x-for="url in newGalleryPreviews" :key="url">
            <div style="width:88px;height:88px;border-radius:10px;background:var(--bg-elevated-2);border:1px solid var(--gold);overflow:hidden;">
              <img :src="url" style="width:100%;height:100%;object-fit:cover;">
            </div>
          </template>
        </div>
      </div>
      @error('gallery_images.*') <div class="error">{{ $message }}</div> @enderror
    </div>
    </div>

    <div class="admin-form" x-show="tab === 'precio'" x-cloak>
    <div class="form-section">
      <h3>Precio y estado</h3>

      <div class="form-row">
        <div class="form-group">
          <label for="price">Precio <span class="required-mark">*</span></label>
          <input type="number" step="0.01" min="0" id="price" name="price" x-model="price">
          @error('price') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="currency">Moneda</label>
          <select id="currency" name="currency" x-model="currency" required>
            <option value="USD">USD</option>
            <option value="BOB">BOB</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="sku">SKU</label>
        <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
      </div>

      <div class="form-group">
        <label for="status">Estado</label>
        <select id="status" name="status" required>
          <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Publicado</option>
          <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Privado</option>
        </select>
      </div>

      <div class="form-group">
        <label class="switch">
          <input type="checkbox" name="is_sold_out" value="1" {{ old('is_sold_out', $product->is_sold_out ?? false) ? 'checked' : '' }}>
          <span class="switch-track"></span>
          <span class="switch-label">Marcar como agotado</span>
        </label>
        <div class="form-hint">
          El producto se ve "Agotado" en el sitio (imagen en blanco y negro, no se puede agregar al carrito) y a los 7 días de marcarlo se pone en Privado solo.
          @if($product->exists && $product->is_sold_out && $product->sold_out_at)
            <br>Agotado desde el {{ $product->sold_out_at->format('d/m/Y') }} — pasa a Privado el {{ $product->sold_out_at->copy()->addDays(7)->format('d/m/Y') }} si sigue así.
          @endif
        </div>
      </div>
    </div>
    </div>

    <div class="admin-form" x-show="tab === 'specs'" x-cloak>
    <div class="form-section">
      <h3>Especificaciones técnicas</h3>
      <template x-for="(spec, i) in specs" :key="i">
        <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
          <input type="text" x-model="spec.key" :name="'spec_key[' + i + ']'" placeholder="Ej: Sensor">
          <input type="text" x-model="spec.value" :name="'spec_value[' + i + ']'" placeholder="Ej: PAW3395, hasta 26,000 DPI">
          <button type="button" class="repeater-remove" x-on:click="specs.splice(i, 1)">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="specs.push({key: '', value: ''})">+ Agregar especificación</button>
    </div>

    <div class="form-section" x-show="componentType" x-cloak>
      <h3 x-text="isPcPiece ? 'Compatibilidad (Arma tu PC)' : 'Atributos de filtro'"></h3>
      <template x-if="isPcPiece">
        <p class="form-hint" style="margin-bottom:14px;">Esta categoría está marcada como pieza de PC — completa estos datos (todos obligatorios) para que el armador sepa con qué otras piezas es compatible este producto.</p>
      </template>
      <template x-if="!isPcPiece">
        <p class="form-hint" style="margin-bottom:14px;">Estos datos se usan como filtro en la tienda para esta categoría — todos obligatorios.</p>
      </template>

      <template x-for="(field, key) in activeFields" :key="key">
        <div class="form-group">
          <label>
            <span x-text="field.label"></span> <span class="required-mark">*</span>
          </label>

          <template x-if="field.type === 'select'">
            <select :name="'compat[' + key + ']'" x-model="compat[key]">
              <option value="">Selecciona...</option>
              <!-- Si el valor guardado ya no está en la lista de opciones actual
                   (se renombró/borró en Compatibilidad, o viene de datos viejos
                   con otro formato) el <select> lo mostraba en blanco — y al
                   guardar de nuevo se perdía sin avisar. Esta opción extra lo
                   muestra tal cual quedó guardado, para que nunca desaparezca solo. -->
              <template x-if="compat[key] && !(compat[key] in field.options)">
                <option :value="compat[key]" x-text="compat[key] + ' (guardado, ya no está en la lista)'"></option>
              </template>
              <template x-for="[optKey, optLabel] in Object.entries(field.options)" :key="optKey">
                <option :value="optKey" x-text="optLabel"></option>
              </template>
            </select>
          </template>

          <template x-if="field.type === 'number'">
            <input type="number" step="1" min="0" :name="'compat[' + key + ']'" x-model.number="compat[key]">
          </template>

          <template x-if="field.type === 'checkboxes'">
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
              <template x-for="[optKey, optLabel] in Object.entries(field.options)" :key="optKey">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:400;">
                  <input type="checkbox" :value="optKey" :name="'compat[' + key + '][]'"
                         :checked="(compat[key] || []).includes(optKey)"
                         x-on:change="
                            compat[key] = compat[key] || [];
                            $event.target.checked
                              ? compat[key].push(optKey)
                              : compat[key] = compat[key].filter(v => v !== optKey);
                         ">
                  <span x-text="optLabel"></span>
                </label>
              </template>
            </div>
          </template>
          <div class="error" x-show="compatErrors[key]" x-text="compatErrors[key]"></div>
        </div>
      </template>
    </div>
    </div>

    <div class="admin-form" x-show="tab === 'variantes'" x-cloak>
    <div class="form-section">
      <h3>Variantes</h3>
      <p class="form-hint" style="margin-bottom:14px;">Si el producto viene en más de una opción (ej. color), agrégalas aquí — no crees un producto nuevo por cada variante. La foto y el precio de cada una son opcionales: dejalos vacíos y usan los de arriba. Ej. el Kumara cuesta lo mismo en negro y blanco → dejá el precio vacío en las dos variantes, y ponele a cada una la foto de su color para que el cliente vea cuál está eligiendo.</p>
      <template x-for="(variant, i) in variants" :key="i">
        <div class="repeater-row" style="grid-template-columns:auto 1fr 1fr 1fr auto;">
          <div class="variant-thumb" @click="$event.target.closest('.variant-thumb').querySelector('input[type=file]').click()" title="Foto de esta variante (opcional)">
            <img :src="variant.imagePreview || variant.image" x-show="variant.imagePreview || variant.image" alt="">
            <span x-show="!variant.imagePreview && !variant.image" class="variant-thumb-empty">+</span>
            <button type="button" x-show="variant.imagePreview || variant.image" @click.stop="variant.imagePreview = null; variant.image = null; variant.removeImage = true" class="variant-thumb-clear" aria-label="Sacar imagen de la variante">×</button>
            <input type="file" accept="image/png,image/jpeg,image/webp" style="display:none;"
                   :name="'variants[' + i + '][image]'"
                   @change="variant.imagePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; if ($event.target.files[0]) variant.removeImage = false">
          </div>
          <input type="hidden" :name="'variants[' + i + '][id]'" x-model="variant.id">
          <input type="text" x-model="variant.variant_type" :name="'variants[' + i + '][variant_type]'" placeholder="Tipo (ej: Color)">
          <input type="text" x-model="variant.variant_value" :name="'variants[' + i + '][variant_value]'" placeholder="Valor (ej: Blanco)">
          <input type="number" step="0.01" min="0" x-model="variant.price_override" :name="'variants[' + i + '][price_override]'" placeholder="Precio (opcional)">
          <input type="hidden" :name="'variants[' + i + '][remove_image]'" :value="variant.removeImage ? '1' : '0'">
          <button type="button" class="repeater-remove" x-on:click="variants.splice(i, 1)">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="variants.push({id: '', variant_type: 'Color', variant_value: '', sku: '', price_override: '', image: null, imagePreview: null, removeImage: false})">+ Agregar variante</button>
    </div>
    </div>

    <div class="form-actions">
      <a href="{{ $backUrl ?? route('admin.productos.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar producto</button>
    </div>
  </form>

  <aside class="admin-preview">
    <div class="admin-preview-label">Vista previa</div>
    <div class="card" style="pointer-events:none;">
      <div class="card-media">
        <img :src="preview" x-show="preview" alt="" style="opacity:1;">
        <span x-show="variants.length" class="badge">Variantes</span>
      </div>
      <div class="card-body">
        <div class="card-cat" x-text="categoryName || 'Categoría'"></div>
        <div class="card-name" x-text="name || 'Nombre del producto'"></div>
        <div class="card-price">
          <span x-show="currency === 'USD'">$<span x-text="(parseFloat(price) || 0).toFixed(2)"></span> <small>USD</small></span>
          <span x-show="currency === 'BOB'">Bs <span x-text="(parseFloat(price) || 0).toFixed(2)"></span> <small>BOB</small></span>
        </div>
      </div>
    </div>
    <p class="form-hint" style="margin-top:12px;">Así se va a ver la tarjeta en la tienda.</p>
  </aside>
</div>

@if($product->exists)
  <div class="form-section" style="margin-top:24px;">
    <h3>Historial de este producto</h3>
    @if($activityLogs->isEmpty())
      <p class="form-hint">Todavía no hay cambios registrados para este producto.</p>
    @else
      <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($activityLogs as $log)
          <div style="padding:12px 14px;background:var(--bg-elevated-2);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:{{ empty($log->changes) ? '0' : '8px' }};">
              <span>
                <strong>{{ $log->user_name }}</strong>
                @php
                  $actionLabel = ['created' => 'creó el producto', 'updated' => 'editó el producto', 'deleted' => 'eliminó el producto'][$log->action] ?? $log->action;
                  $actionColor = ['created' => 'var(--green)', 'updated' => 'var(--gold)', 'deleted' => 'var(--red)'][$log->action] ?? 'var(--text-secondary)';
                @endphp
                <span style="color:{{ $actionColor }};">{{ $actionLabel }}</span>
              </span>
              <span class="mono" style="color:var(--text-muted);font-size:12px;">{{ $log->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if(!empty($log->changes))
              <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($log->changes as $field => $change)
                  @foreach(\App\Models\ProductActivityLog::describeChange($change['antes'] ?? null, $change['despues'] ?? null, $field) as $row)
                    <div style="font-size:12.5px;">
                      <span style="color:var(--text-secondary);font-weight:600;">{{ \App\Models\ProductActivityLog::fieldLabel($field) }}{{ $row['label'] ? " ({$row['label']})" : '' }}:</span>
                      @if($row['before'] === null)
                        <span style="color:var(--text-muted);">sin cambios visibles</span>
                      @else
                        <span style="color:var(--red);text-decoration:line-through;">{{ $row['before'] }}</span>
                        →
                        <span style="color:var(--green);">{{ $row['after'] }}</span>
                      @endif
                    </div>
                  @endforeach
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endif

@endsection
