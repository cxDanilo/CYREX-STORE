{{-- Espera un x-data="brandProductPicker(allProducts, selectedIds)" en el
     elemento padre (ver admin/brands/edit.blade.php, sección Hero) --}}
<div style="position:relative;" @click.outside="q = ''">
  <input type="text" x-model="q" class="admin-product-search" placeholder="Buscar producto..." autocomplete="off">
  <div class="admin-search-results" x-show="searchResults.length" x-cloak>
    <template x-for="p in searchResults" :key="p.id">
      <button type="button" class="admin-search-result-row" @click="addProduct(p.id)">
        <span x-text="p.name"></span>
        <span class="admin-search-result-price mono" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></span>
      </button>
    </template>
  </div>
</div>
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
  <template x-for="id in selected" :key="id">
    <span class="brand-admin-chip">
      <span x-text="productName(id)"></span>
      <button type="button" @click="removeProduct(id)">×</button>
      <input type="hidden" name="{{ $fieldName }}" :value="id">
    </span>
  </template>
</div>
