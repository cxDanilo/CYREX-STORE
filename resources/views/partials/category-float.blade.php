<div class="cat-float" data-category-menu-scope="{{ $categoryMenuScope }}"
    @if($categoryMenuScope !== 'all' && !request()->routeIs('shop')) style="display:none;" @endif
    x-data="{
      hoverCat: null,
      pendingCat: null,
      openTimer: null,
      expanded: false,
      closeTimer: null,
      collapseTimer: null,
      // El widget entero sigue en el DOM aun cuando el ajuste lo
      // limita a /tienda (ver comentario en nav.blade.php) — sin este
      // chequeo, el timer de abajo marcaba el hint como visto en
      // CUALQUIER página aunque estuviera con display:none, y el
      // visitante nunca llegaba a verlo de verdad en /tienda.
      visibleHere: {{ ($categoryMenuScope === 'all' || request()->routeIs('shop')) ? 'true' : 'false' }},
      showHint: false,
      hintTimer: null,
      // Antes esto era window.matchMedia('(hover: hover)').matches,
      // calculado una sola vez al cargar la página — en máquinas
      // virtuales, laptops híbridas con pantalla táctil y varias
      // configuraciones de Windows esa consulta devuelve 'sin hover'
      // aunque haya un mouse real conectado, y el menú quedaba sin
      // abrirse ni con clic ni con el mouse encima. pointerdown SIEMPRE
      // llega antes que el click que lo sigue, así que para cuando
      // handleTap corre ya sabemos con certeza qué disparó ESTE toque
      // puntual, sin adivinar por las capacidades generales del equipo.
      lastPointerType: 'mouse',
      // Antes cambiaba de categoría apenas el mouse entraba a otro
      // ítem de la lista de la izquierda — el problema es que para
      // llegar a un hijo del final del flyout abierto (ej. Gabinete en
      // Componentes, que queda a la altura de Periféricos en la lista),
      // el mouse cruza de paso por encima de ese otro ítem, y el flyout
      // se cerraba de golpe antes de llegar a hacer clic. Ahora, si ya
      // hay un flyout abierto, cambiar de categoría espera un toque
      // (ver leaveCat) — si el mouse se va de ese ítem antes de que
      // pase el tiempo, fue solo de paso y no cambia nada.
      openCat(id) {
        clearTimeout(this.closeTimer);
        clearTimeout(this.openTimer);
        this.pendingCat = null;
        if (this.hoverCat === id) return;
        if (this.hoverCat === null) { this.hoverCat = id; return; }
        this.pendingCat = id;
        this.openTimer = setTimeout(() => { this.hoverCat = id; this.pendingCat = null; }, 220);
      },
      leaveCat(id) {
        if (this.pendingCat === id) { clearTimeout(this.openTimer); this.pendingCat = null; }
        this.scheduleClose(id);
      },
      scheduleClose(id) { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => { if (this.hoverCat === id) this.hoverCat = null; }, 300); },
      expand() { clearTimeout(this.collapseTimer); this.expanded = true; if (this.showHint) this.dismissHint(); },
      scheduleCollapse() { clearTimeout(this.collapseTimer); this.collapseTimer = setTimeout(() => { this.expanded = false; this.hoverCat = null; }, 350); },
      dismissHint() {
        clearTimeout(this.hintTimer);
        this.showHint = false;
        document.cookie = 'cyrex_cat_hint_seen=1; max-age=' + (60*60*24*365) + '; path=/; SameSite=Lax';
      },
      // pointerenter/pointerleave de acá abajo ya sólo reaccionan a
      // pointerType 'mouse' (ver más abajo), así que un toque real
      // nunca los dispara — no hace falta la vieja lógica de ignorar
      // el mouseenter fantasma que touch simula antes del click.
      handleTap(event, id) {
        if (this.lastPointerType === 'mouse') return;
        if (this.hoverCat !== id) {
          event.preventDefault();
          this.expand();
          // Directo, sin el margen de espera de openCat() -- ese margen
          // es para no confundir un cruce accidental del mouse con un
          // cambio de categoría a propósito, algo que no existe en
          // touch (cada toque ya es una acción deliberada).
          clearTimeout(this.closeTimer);
          clearTimeout(this.openTimer);
          this.pendingCat = null;
          this.hoverCat = id;
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
      showHint = visibleHere && !document.cookie.split('; ').includes('cyrex_cat_hint_seen=1');
      if (showHint) hintTimer = setTimeout(() => dismissHint(), 7000);
    "
    x-on:pointerdown="lastPointerType = $event.pointerType"
    x-on:pointerenter="$event.pointerType === 'mouse' && expand()"
    x-on:pointerleave="$event.pointerType === 'mouse' && scheduleCollapse()"
    x-on:click.outside="expanded = false; hoverCat = null;"
    x-on:click="handleAnyLinkClick($event)">
  <div class="cat-float-hint" x-show="showHint" x-transition.opacity.duration.400ms x-cloak>
    <span class="cat-float-hint-arrow">&larr;</span>
    <span>Escoge tus categorías desde acá</span>
    <button type="button" class="cat-float-hint-close" x-on:click.stop="dismissHint()" aria-label="Cerrar">&times;</button>
  </div>
  <div class="cat-float-list" :class="expanded && 'expanded'">
    @foreach($navCategories as $parent)
      <div class="cat-float-item" x-on:pointerenter="$event.pointerType === 'mouse' && (expand(), openCat({{ $parent->id }}))" x-on:pointerleave="$event.pointerType === 'mouse' && leaveCat({{ $parent->id }})">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="cat-float-link" :class="hoverCat === {{ $parent->id }} && 'active'" x-on:click="handleTap($event, {{ $parent->id }})">
          <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon, 'iconImage' => $parent->icon_image_url])</span>
          <span class="cat-float-label">{{ $parent->name }}</span>
        </a>

        <div class="cat-flyout" x-show="hoverCat === {{ $parent->id }}" x-transition.opacity.duration.150ms x-on:pointerenter="$event.pointerType === 'mouse' && (expand(), openCat({{ $parent->id }}))" x-on:pointerleave="$event.pointerType === 'mouse' && scheduleClose({{ $parent->id }})" x-cloak>
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
