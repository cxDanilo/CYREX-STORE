@extends('admin.layout')

@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')

@section('content')

<div style="display:flex;gap:32px;align-items:flex-start;"
     x-data="{
        specs: {{ collect($product->specs ?? [])->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->toJson() }},
        variants: {{ $product->relationLoaded('variants') ? $product->variants->map(fn($v) => ['id' => $v->id, 'variant_type' => $v->variant_type, 'variant_value' => $v->variant_value, 'sku' => $v->sku, 'stock' => $v->stock, 'price_override' => $v->price_override])->toJson() : '[]' }},
        preview: @js($product->image_url),
        name: @js(old('name', $product->name) ?? ''),
        description: @js(old('description', $product->description) ?? ''),
        price: @js((string) old('price', $product->price ?? '')),
        currency: @js(old('currency', $product->currency ?? 'USD')),
        categoryId: @js((string) old('category_id', $product->category_id ?? '')),
        categories: @js($categories->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name, 'componentType' => $c->component_type])->values()),
        get categoryName() { return (this.categories.find(c => c.id === this.categoryId) || {}).name || ''; },
        componentFields: @js(\App\Support\PcBuilderFields::resolved()),
        get componentType() { return (this.categories.find(c => c.id === this.categoryId) || {}).componentType || null; },
        get activeFields() { return this.componentType ? (this.componentFields[this.componentType] || {}) : {}; },
        compat: {{ Js::from((object) ($product->compat ?: [])) }}
     }">
  <form method="POST" action="{{ $product->exists ? route('admin.productos.update', $product) : route('admin.productos.store') }}" class="admin-form" enctype="multipart/form-data" style="flex:1;min-width:0;">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Información general</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" x-model="name" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug (URL)</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $product->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        <div class="form-hint">Se usa en la URL: /producto/<span x-text="$refs.slug ? $refs.slug.value : ''"></span></div>
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="category_id">Categoría</label>
        <select id="category_id" name="category_id" x-model="categoryId" required>
          <option value="">Seleccioná una categoría</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}">
              {{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}
            </option>
          @endforeach
        </select>
        @error('category_id') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea id="description" name="description" rows="3" maxlength="500" x-model="description"></textarea>
        <div class="form-hint" style="text-align:right;" x-text="description.length + '/500'"></div>
        <div class="form-hint">Para specs técnicas (socket, RAM, watts, etc.) usá los campos de Compatibilidad más abajo, no esta descripción — se muestran juntos en la página del producto.</div>
      </div>

      <div class="form-group">
        <label for="image">Imagen del producto</label>
        <div style="display:flex;gap:16px;align-items:flex-start;">
          <div style="width:96px;height:96px;border-radius:12px;background:var(--bg-elevated-2);border:1px solid var(--border);flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
            <img :src="preview" x-show="preview" style="width:100%;height:100%;object-fit:cover;" alt="">
            <span x-show="!preview" style="color:var(--text-muted);font-size:11px;">Sin imagen</span>
          </div>
          <div style="flex:1;">
            <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp"
                   x-on:change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview">
            <div class="form-hint">JPG, PNG o WEBP, máx. 4 MB.</div>
            @if($product->image)
              <label style="display:flex;align-items:center;gap:6px;margin-top:10px;font-size:13px;color:var(--text-secondary);">
                <input type="checkbox" name="remove_image" value="1" x-on:change="if($event.target.checked) preview = null">
                Quitar imagen actual
              </label>
            @endif
          </div>
        </div>
        @error('image') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label>Galería de imágenes adicionales</label>
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

        <div x-data="{ newGalleryPreviews: [] }">
          <input type="file" name="gallery_images[]" accept="image/png,image/jpeg,image/webp" multiple
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

    <div class="form-section">
      <h3>Precio e inventario</h3>

      <div class="form-row">
        <div class="form-group">
          <label for="price">Precio</label>
          <input type="number" step="0.01" min="0" id="price" name="price" x-model="price" required>
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

      <div class="form-row">
        <div class="form-group">
          <label for="sku">SKU</label>
          <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
        </div>
        <div class="form-group">
          <label for="stock">Stock</label>
          <input type="number" min="0" id="stock" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required>
          @error('stock') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-group">
        <label for="status">Estado</label>
        <select id="status" name="status" required>
          <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Publicado</option>
          <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Privado</option>
        </select>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="is_sold_out" value="1" {{ old('is_sold_out', $product->is_sold_out ?? false) ? 'checked' : '' }}>
          Marcar como agotado
        </label>
        <div class="form-hint">
          Independiente del número de Stock — el producto se ve "Agotado" en el sitio (imagen en blanco y negro, no se puede agregar al carrito) y a los 7 días de marcarlo se pone en Privado solo.
          @if($product->exists && $product->is_sold_out && $product->sold_out_at)
            <br>Agotado desde el {{ $product->sold_out_at->format('d/m/Y') }} — pasa a Privado el {{ $product->sold_out_at->copy()->addDays(7)->format('d/m/Y') }} si sigue así.
          @endif
        </div>
      </div>
    </div>

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
      <h3>Compatibilidad (Arma tu PC)</h3>
      <p class="form-hint" style="margin-bottom:14px;">Esta categoría está marcada como pieza de PC — completá estos datos para que el armador sepa con qué otras piezas es compatible este producto.</p>

      <template x-for="(field, key) in activeFields" :key="key">
        <div class="form-group">
          <label x-text="field.label"></label>

          <template x-if="field.type === 'select'">
            <select :name="'compat[' + key + ']'" x-model="compat[key]">
              <option value="">Seleccioná...</option>
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
        </div>
      </template>
    </div>

    <div class="form-section">
      <h3>Variantes</h3>
      <p class="form-hint" style="margin-bottom:14px;">Si el producto viene en más de una opción (ej. color), agregalas acá — no crees un producto nuevo por cada variante.</p>
      <template x-for="(variant, i) in variants" :key="i">
        <div class="repeater-row">
          <input type="hidden" :name="'variants[' + i + '][id]'" x-model="variant.id">
          <input type="text" x-model="variant.variant_type" :name="'variants[' + i + '][variant_type]'" placeholder="Tipo (ej: Color)">
          <input type="text" x-model="variant.variant_value" :name="'variants[' + i + '][variant_value]'" placeholder="Valor (ej: Blanco)">
          <input type="number" min="0" x-model="variant.stock" :name="'variants[' + i + '][stock]'" placeholder="Stock">
          <input type="number" step="0.01" min="0" x-model="variant.price_override" :name="'variants[' + i + '][price_override]'" placeholder="Precio (opcional)">
          <button type="button" class="repeater-remove" x-on:click="variants.splice(i, 1)">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="variants.push({id: '', variant_type: 'Color', variant_value: '', sku: '', stock: 0, price_override: ''})">+ Agregar variante</button>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.productos.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar producto</button>
    </div>
  </form>

  <aside class="admin-preview">
    <div class="admin-preview-label">Vista previa</div>
    <div class="card" style="pointer-events:none;">
      <div class="card-media">
        <img :src="preview" x-show="preview" alt="">
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
              <div style="display:flex;flex-direction:column;gap:4px;">
                @foreach($log->changes as $field => $change)
                  <div style="font-size:12.5px;">
                    <span class="mono" style="color:var(--text-muted);">{{ $field }}:</span>
                    <span style="color:var(--red);text-decoration:line-through;">{{ \Illuminate\Support\Str::limit((string) ($change['antes'] ?? '—'), 50) }}</span>
                    →
                    <span style="color:var(--green);">{{ \Illuminate\Support\Str::limit((string) ($change['despues'] ?? '—'), 50) }}</span>
                  </div>
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
