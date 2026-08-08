<div class="cat-float" x-data="{
      hoverCat: null,
      expanded: false,
      closeTimer: null,
      collapseTimer: null,
      showHint: false,
      hintTimer: null,
      openCat(id) { clearTimeout(this.closeTimer); this.hoverCat = id; },
      scheduleClose(id) { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => { if (this.hoverCat === id) this.hoverCat = null; }, 300); },
      expand() { clearTimeout(this.collapseTimer); this.expanded = true; if (this.showHint) this.dismissHint(); },
      scheduleCollapse() { clearTimeout(this.collapseTimer); this.collapseTimer = setTimeout(() => { this.expanded = false; this.hoverCat = null; }, 350); },
      dismissHint() {
        clearTimeout(this.hintTimer);
        this.showHint = false;
        document.cookie = 'cyrex_cat_hint_seen=1; max-age=' + (60*60*24*365) + '; path=/; SameSite=Lax';
      },
      // En tablet/celular no existe :hover real — mouseenter nunca
      // dispara, así que el submenú no había forma de abrirlo con el
      // dedo. Acá interceptamos el primer toque para abrir el submenú
      // en vez de navegar; un segundo toque sobre el mismo ítem (o
      // tocar 'Ver todo en X' adentro) sí navega normal.
      handleTap(event, id) {
        if (window.matchMedia('(hover: hover)').matches) return;
        if (this.hoverCat !== id) {
          event.preventDefault();
          this.expand();
          this.openCat(id);
        }
      }
    }"
    x-init="
      showHint = !document.cookie.split('; ').includes('cyrex_cat_hint_seen=1');
      if (showHint) hintTimer = setTimeout(() => dismissHint(), 7000);
    "
    x-on:mouseenter="expand()"
    x-on:mouseleave="scheduleCollapse()"
    x-on:click.outside="expanded = false; hoverCat = null;">
  <div class="cat-float-hint" x-show="showHint" x-transition.opacity.duration.400ms x-cloak>
    <span class="cat-float-hint-arrow">&larr;</span>
    <span>Escoge tus categorías desde acá</span>
    <button type="button" class="cat-float-hint-close" x-on:click.stop="dismissHint()" aria-label="Cerrar">&times;</button>
  </div>
  <div class="cat-float-list" :class="expanded && 'expanded'">
    @foreach($navCategories as $parent)
      <div class="cat-float-item" x-on:mouseenter="expand(); openCat({{ $parent->id }})" x-on:mouseleave="scheduleClose({{ $parent->id }})">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="cat-float-link" :class="hoverCat === {{ $parent->id }} && 'active'" x-on:click="handleTap($event, {{ $parent->id }})">
          <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon])</span>
          <span class="cat-float-label">{{ $parent->name }}</span>
        </a>

        <div class="cat-flyout" x-show="hoverCat === {{ $parent->id }}" x-transition.opacity.duration.150ms x-on:mouseenter="expand(); openCat({{ $parent->id }})" x-on:mouseleave="scheduleClose({{ $parent->id }})" x-cloak>
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
