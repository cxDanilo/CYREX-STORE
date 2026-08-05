<div class="cat-float" x-data="{
      hoverCat: null,
      expanded: false,
      closeTimer: null,
      openCat(id) { clearTimeout(this.closeTimer); this.hoverCat = id; },
      scheduleClose(id) { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => { if (this.hoverCat === id) this.hoverCat = null; }, 300); }
    }"
    x-on:mouseenter="expanded = true"
    x-on:mouseleave="expanded = false; hoverCat = null">
  <div class="cat-float-list" :class="expanded && 'expanded'">
    @foreach($navCategories as $parent)
      <div class="cat-float-item" x-on:mouseenter="openCat({{ $parent->id }})" x-on:mouseleave="scheduleClose({{ $parent->id }})">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="cat-float-link" :class="hoverCat === {{ $parent->id }} && 'active'">
          <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon])</span>
          <span class="cat-float-label">{{ $parent->name }}</span>
        </a>

        <div class="cat-flyout" x-show="hoverCat === {{ $parent->id }}" x-transition.opacity.duration.150ms x-on:mouseenter="openCat({{ $parent->id }})" x-on:mouseleave="scheduleClose({{ $parent->id }})" x-cloak>
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
