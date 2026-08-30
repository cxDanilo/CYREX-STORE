<div class="cat-float" data-category-menu-scope="{{ $categoryMenuScope }}"
    @if($categoryMenuScope !== 'all' && !request()->routeIs('shop')) style="display:none;" @endif
    x-data="{
      hoverCat: null,
      expanded: false,
      closeTimer: null,
      collapseTimer: null,
      showHint: false,
      hintTimer: null,
      hoverEnabled: window.matchMedia('(hover: hover)').matches,
      openCat(id) { clearTimeout(this.closeTimer); this.hoverCat = id; },
      scheduleClose(id) { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => { if (this.hoverCat === id) this.hoverCat = null; }, 300); },
      expand() { clearTimeout(this.collapseTimer); this.expanded = true; if (this.showHint) this.dismissHint(); },
      scheduleCollapse() { clearTimeout(this.collapseTimer); this.collapseTimer = setTimeout(() => { this.expanded = false; this.hoverCat = null; }, 350); },
      dismissHint() {
        clearTimeout(this.hintTimer);
        this.showHint = false;
        document.cookie = 'cyrex_cat_hint_seen=1; max-age=' + (60*60*24*365) + '; path=/; SameSite=Lax';
      },
      // En touch, el navegador dispara un mouseenter 'fantasma' en el
      // primer toque (para simular :hover) ANTES del click — eso ya
      // dejaba hoverCat puesto cuando handleTap corría, y su propio
      // chequeo (hoverCat !== id) daba falso, así que nunca frenaba la
      // navegación: por eso tocar se comportaba igual que un click de
      // PC. Ahora todos los mouseenter/mouseleave de acá se ignoran en
      // touch (hoverEnabled=false) — hoverCat solo lo puede tocar
      // handleTap, así el primer toque SIEMPRE abre el submenú y el
      // segundo (o tocar 'Ver todo en X') navega normal.
      handleTap(event, id) {
        if (this.hoverEnabled) return;
        if (this.hoverCat !== id) {
          event.preventDefault();
          this.expand();
          this.openCat(id);
        }
      },
      // En touch no hay mouseleave que colapse el menú solo al alejar
      // el cursor (como pasa en desktop) — sin esto, tocar una
      // categoría y navegar dejaba el menú expandido pegado arriba de
      // la página nueva, porque el menú vive fuera de <main> y nada lo
      // tocaba durante la navegación suave. Se engancha en el div de
      // afuera (no en cada link) para cubrir TODOS los links de acá
      // (categoría, 'Ver todo en X', cada hijo) con un solo handler.
      // event.defaultPrevented distingue el primer toque (que sólo
      // abre el submenú, sin navegar) de un toque que sí navega de
      // verdad.
      handleAnyLinkClick(event) {
        if (!event.target.closest('a') || event.defaultPrevented) return;
        this.expanded = false;
        this.hoverCat = null;
      }
    }"
    x-init="
      showHint = !document.cookie.split('; ').includes('cyrex_cat_hint_seen=1');
      if (showHint) hintTimer = setTimeout(() => dismissHint(), 7000);
    "
    x-on:mouseenter="hoverEnabled && expand()"
    x-on:mouseleave="hoverEnabled && scheduleCollapse()"
    x-on:click.outside="expanded = false; hoverCat = null;"
    x-on:click="handleAnyLinkClick($event)">
  <div class="cat-float-hint" x-show="showHint" x-transition.opacity.duration.400ms x-cloak>
    <span class="cat-float-hint-arrow">&larr;</span>
    <span>Escoge tus categorías desde acá</span>
    <button type="button" class="cat-float-hint-close" x-on:click.stop="dismissHint()" aria-label="Cerrar">&times;</button>
  </div>
  <div class="cat-float-list" :class="expanded && 'expanded'">
    @foreach($navCategories as $parent)
      <div class="cat-float-item" x-on:mouseenter="hoverEnabled && (expand(), openCat({{ $parent->id }}))" x-on:mouseleave="hoverEnabled && scheduleClose({{ $parent->id }})">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="cat-float-link" :class="hoverCat === {{ $parent->id }} && 'active'" x-on:click="handleTap($event, {{ $parent->id }})">
          <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon, 'iconImage' => $parent->icon_image_url])</span>
          <span class="cat-float-label">{{ $parent->name }}</span>
        </a>

        <div class="cat-flyout" x-show="hoverCat === {{ $parent->id }}" x-transition.opacity.duration.150ms x-on:mouseenter="hoverEnabled && (expand(), openCat({{ $parent->id }}))" x-on:mouseleave="hoverEnabled && scheduleClose({{ $parent->id }})" x-cloak>
          <div class="cat-flyout-title"><a href="{{ route('shop', ['category' => $parent->slug]) }}">Ver todo en {{ $parent->name }}</a></div>
          <div class="cat-flyout-grid">
            @foreach($parent->children as $child)
              <a href="{{ route('shop', ['category' => $child->slug]) }}">{{ $child->name }}</a>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
