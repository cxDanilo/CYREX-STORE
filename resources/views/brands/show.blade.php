@extends('layouts.app')

@section('title', $brand->name.' en Cyrex Store')
@section('meta_description', 'Descubrí '.$brand->name.' en Cyrex Store: productos seleccionados, comparativas y contenido para encontrar el que se adapta a tu setup.')

@php
  // El acento de la marca se usa con moderación (bordes, algún texto,
  // badges) -- nunca como fondo dominante, para que siga siendo
  // evidente que estás en Cyrex, no en un micrositio aparte. Con
  // fallback al dorado de Cyrex si la marca no cargó un color propio.
  $accent = $brand->accent_color ?: '#FFD900';

  // El CTA "Descubrir {marca} ↓" del hero apunta a la primera sección
  // que realmente va a existir en la página -- si no hay tarjetas,
  // categorías, selección ni comparador cargados todavía, cae directo
  // al catálogo (que siempre está, aunque sea con un solo producto) en
  // vez de a un ancla que no existe.
  $discoverAnchor = match (true) {
      $finderCards->isNotEmpty() => '#para-ti',
      $exploreCategories->isNotEmpty() => '#categorias',
      (bool) $selectionProduct => '#seleccion',
      $comparisonRows->count() >= 2 => '#comparar',
      default => '#productos',
  };
@endphp

@section('styles')
<style>
  .brand-page{ --brand-accent: {{ $accent }}; }
</style>
@endsection

@section('content')

<div class="brand-page" style="--brand-accent: {{ $accent }};">

  {{-- Nav interna sticky -- desaparece en mobile (ver CSS), nunca
       reemplaza el header principal de Cyrex, solo se agrega debajo. --}}
  <nav class="brand-subnav" aria-label="Navegación de {{ $brand->name }}">
    <div class="wrap brand-subnav-inner">
      <span class="brand-subnav-name">{{ $brand->name }}</span>
      <div class="brand-subnav-links">
        @if($finderCards->isNotEmpty())<a href="#para-ti">Para ti</a>@endif
        @if($exploreCategories->isNotEmpty())<a href="#categorias">Categorías</a>@endif
        @if($selectionProduct)<a href="#seleccion">Selección Cyrex</a>@endif
        @if($comparisonRows->count() >= 2)<a href="#comparar">Comparar</a>@endif
        <a href="#productos">Productos</a>
      </div>
    </div>
  </nav>

  {{-- ============ 1. HERO ============ --}}
  <section class="brand-hero">
    <div class="wrap brand-hero-inner">
      <div class="brand-hero-copy" data-reveal>
        @if($brand->logo_url)
          <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="brand-hero-logo">
        @endif
        <h1 class="brand-hero-title">{{ $brand->name }}</h1>
        @if(!empty($hero['subheadline']))
          <p class="brand-hero-headline">{{ $hero['subheadline'] }}</p>
        @endif
        @if(!empty($hero['description']))
          <p class="brand-hero-desc">{{ $hero['description'] }}</p>
        @endif
        <div class="brand-hero-ctas">
          <a href="{{ $discoverAnchor }}" class="btn-outline-gold brand-hero-cta-scroll">Descubrir {{ $brand->name }} ↓</a>
          <a href="{{ route('shop', ['marca' => $brand->name]) }}" class="btn btn-primary">Ver productos →</a>
        </div>
      </div>
      @if($heroProducts->isNotEmpty())
        <div class="brand-hero-composition" data-reveal>
          @foreach($heroProducts->take(3) as $i => $product)
            <div class="brand-hero-product brand-hero-product-{{ $i + 1 }}">
              @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="eager">
              @endif
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- ============ 2. MARCA EN CYREX ============ --}}
  @if(!empty($editorial['title']) || !empty($editorial['body']))
    <section class="brand-editorial">
      <div class="wrap brand-editorial-inner">
        <div class="brand-editorial-copy" data-reveal>
          @if(!empty($editorial['eyebrow']))
            <div class="cat-eyebrow">{{ $editorial['eyebrow'] }}</div>
          @endif
          @if(!empty($editorial['title']))
            <h2 class="brand-editorial-title">{{ $editorial['title'] }}</h2>
          @endif
          @if(!empty($editorial['body']))
            <p class="brand-editorial-body">{{ $editorial['body'] }}</p>
          @endif
        </div>
        @if($editorialProduct && $editorialProduct->image_url)
          <div class="brand-editorial-media" data-reveal>
            <img src="{{ $editorialProduct->image_url }}" alt="{{ $editorialProduct->name }}" loading="lazy">
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ============ 3. ¿QUÉ ESTÁS BUSCANDO? ============ --}}
  @if($finderCards->isNotEmpty())
    <section class="brand-finder" id="para-ti">
      <div class="wrap">
        <h2 class="brand-section-title" data-reveal>Encuentra tu {{ $brand->name }}</h2>
        <div class="brand-finder-grid">
          @foreach($finderCards as $card)
            <div class="brand-finder-card" data-reveal x-data="{ open: false }" @click="open = !open">
              <div class="brand-finder-card-media">
                @if($card['products']->first()->image_url ?? null)
                  <img src="{{ $card['products']->first()->image_url }}" alt="" loading="lazy">
                @endif
              </div>
              <div class="brand-finder-card-body">
                <h3>{{ $card['title'] }}</h3>
                @if(!empty($card['description']))
                  <p>{{ $card['description'] }}</p>
                @endif
                <div class="brand-finder-card-products" :class="{ 'is-open': open }">
                  @foreach($card['products'] as $p)
                    <a href="{{ route('product.show', $p->slug) }}" class="brand-finder-product-chip">{{ $p->name }}</a>
                  @endforeach
                </div>
                <span class="brand-finder-cta">{{ $card['cta_label'] ?? 'Ver opciones →' }}</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ============ 4. EXPLORA [MARCA] ============ --}}
  @if($exploreCategories->isNotEmpty())
    <section class="brand-explore" id="categorias">
      <div class="wrap">
        @foreach($exploreCategories as $i => $item)
          <a href="{{ $item['category'] ? route('shop', ['category' => $item['category']->slug, 'marca' => $brand->name]) : route('shop', ['marca' => $brand->name]) }}"
             class="brand-explore-block" data-reveal>
            <div class="brand-explore-media">
              @if(!empty($item['image']))
                <img src="{{ asset('uploads/'.$item['image']) }}" alt="" loading="lazy">
              @endif
            </div>
            <div class="brand-explore-body">
              <span class="brand-explore-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <h3>{{ $item['title'] }}</h3>
              @if(!empty($item['description']))<p>{{ $item['description'] }}</p>@endif
              <span class="brand-explore-cta">{{ $item['cta_label'] ?? 'Explorar →' }}</span>
            </div>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ============ 5. CYREX SELECTION ============ --}}
  @if($selectionProduct)
    <section class="brand-selection" id="seleccion">
      <div class="wrap brand-selection-inner">
        <div class="brand-selection-media" data-reveal>
          @if($selectionProduct->image_url)
            <img src="{{ $selectionProduct->image_url }}" alt="{{ $selectionProduct->name }}" loading="lazy">
          @endif
        </div>
        <div class="brand-selection-copy" data-reveal>
          <div class="cat-eyebrow">Cyrex Selection</div>
          <h2>{{ $selectionProduct->name }}</h2>
          @if(!empty($selection['tags']))
            <div class="brand-selection-tags">
              @foreach($selection['tags'] as $tag)
                <span class="brand-selection-tag">{{ $tag }}</span>
              @endforeach
            </div>
          @endif
          @if(!empty($selection['why']))
            <h4 class="brand-selection-why-title">¿Por qué lo elegimos?</h4>
            <p class="brand-selection-why">{{ $selection['why'] }}</p>
          @endif
          <a href="{{ route('product.show', $selectionProduct->slug) }}" class="btn btn-primary">
            {{ $selection['cta_label'] ?? 'Conocer más →' }}
          </a>
        </div>
      </div>
    </section>
  @endif

  {{-- ============ 6. COMPARADOR ============ --}}
  @if($comparisonRows->count() >= 2)
    <section class="brand-compare" id="comparar">
      <div class="wrap">
        <h2 class="brand-section-title" data-reveal>¿Cuál es para ti?</h2>
        <div class="brand-compare-grid" data-reveal>
          @foreach($comparisonRows as $row)
            <div class="brand-compare-card">
              <div class="brand-compare-media">
                @if($row['product']->image_url)
                  <img src="{{ $row['product']->image_url }}" alt="{{ $row['product']->name }}" loading="lazy">
                @endif
              </div>
              <h4>{{ $row['product']->name }}</h4>
              <dl class="brand-compare-specs">
                @foreach(['formato' => 'Formato', 'conectividad' => 'Conectividad', 'tipo_usuario' => 'Para quién', 'switch' => 'Switch', 'tamano' => 'Tamaño', 'destacado' => 'Destacado'] as $key => $label)
                  @if(!empty($row[$key]))
                    <div class="brand-compare-spec-row"><dt>{{ $label }}</dt><dd>{{ $row[$key] }}</dd></div>
                  @endif
                @endforeach
              </dl>
              <a href="{{ route('product.show', $row['product']->slug) }}" class="btn btn-sm">Comparar productos →</a>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ============ 7. CONTENIDO CYREX ============ --}}
  @if($contentItems->isNotEmpty())
    <section class="brand-content">
      <div class="wrap">
        <div class="cat-eyebrow" data-reveal>Lo probamos</div>
        <h2 class="brand-section-title" data-reveal>Antes de recomendar algo, queremos conocerlo.</h2>
        <div class="brand-content-grid">
          <div class="brand-content-main" data-reveal>
            <div class="brand-content-embed">
              <iframe src="{{ $contentItems->first()['embed'] }}" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>
            </div>
            @if(!empty($contentItems->first()['title']))
              <h4>{{ $contentItems->first()['title'] }}</h4>
            @endif
          </div>
          @if($contentItems->count() > 1)
            <div class="brand-content-list" data-reveal>
              @foreach($contentItems->slice(1) as $item)
                <div class="brand-content-item">
                  <div class="brand-content-embed">
                    <iframe src="{{ $item['embed'] }}" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>
                  </div>
                  @if(!empty($item['title']))<span>{{ $item['title'] }}</span>@endif
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>
  @endif

  {{-- ============ 8. COMUNIDAD ============ --}}
  @if($communityPosts->isNotEmpty())
    <section class="brand-community">
      <div class="wrap">
        <h2 class="brand-section-title" data-reveal>{{ Str::upper($brand->name) }} en la comunidad Cyrex</h2>
        <div class="brand-community-grid">
          @foreach($communityPosts as $post)
            <div class="brand-community-card" data-reveal>
              <div class="brand-community-media">
                <img src="{{ asset('uploads/'.$post['image']) }}" alt="" loading="lazy">
              </div>
              @if(!empty($post['caption']) || !empty($post['product_tags']))
                <div class="brand-community-body">
                  @if(!empty($post['caption']))<p>{{ $post['caption'] }}</p>@endif
                  @if(!empty($post['product_tags']))
                    <div class="brand-community-tags">
                      @foreach(is_array($post['product_tags']) ? $post['product_tags'] : array_filter(array_map('trim', explode(',', $post['product_tags']))) as $tag)
                        <span>{{ $tag }}</span>
                      @endforeach
                    </div>
                  @endif
                </div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ============ 9. CATÁLOGO ============ --}}
  <section class="brand-catalog" id="productos">
    <div class="wrap">
      <h2 class="brand-section-title" data-reveal>Todos los productos {{ $brand->name }}</h2>
      @if($catalogCategories->isNotEmpty())
        <div class="brand-catalog-filters">
          <a href="{{ route('brand.show', $brand->slug) }}" class="brand-filter-chip {{ !request('cat') ? 'is-active' : '' }}">Todos</a>
          @foreach($catalogCategories as $cat)
            <a href="{{ route('brand.show', ['brand' => $brand->slug, 'cat' => $cat->slug]) }}" class="brand-filter-chip {{ request('cat') === $cat->slug ? 'is-active' : '' }}">{{ $cat->name }}</a>
          @endforeach
        </div>
      @endif
      @if($catalogProducts->isEmpty())
        <div class="admin-empty" style="color:var(--text-secondary);">Todavía no hay productos {{ $brand->name }} cargados en esta categoría.</div>
      @else
        <div class="product-grid">
          @foreach($catalogProducts as $product)
            @include('partials.product-card', ['product' => $product, 'quickAdd' => true])
          @endforeach
        </div>
        <div style="margin-top:24px;">{{ $catalogProducts->links('partials.pagination') }}</div>
      @endif
    </div>
  </section>

  {{-- ============ 10. CTA FINAL ============ --}}
  <section class="brand-final-cta">
    <div class="wrap brand-final-cta-inner" data-reveal>
      <div class="cat-eyebrow">¿Todavía no sabes cuál elegir?</div>
      <h2>No necesitas conocer todos los switches, sensores y especificaciones.</h2>
      <p>Cuéntanos qué buscas, para qué lo vas a utilizar y cuánto quieres gastar. Te ayudamos a comparar opciones.</p>
      <div class="brand-final-cta-actions">
        <a href="https://wa.me/{{ \App\Support\ReferralRouter::whatsappNumber() }}?text={{ urlencode('Hola, quiero que me ayuden a elegir un producto '.$brand->name) }}" class="btn btn-primary" target="_blank" rel="noopener">Hablar con Cyrex →</a>
        <a href="{{ route('shop', ['marca' => $brand->name]) }}" class="btn-outline-gold">Ver todos los {{ $brand->name }} →</a>
      </div>
    </div>
  </section>

</div>

@endsection
