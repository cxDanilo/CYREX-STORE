<div class="cat-float" x-data="{ hoverCat: null }">
  <div class="cat-float-list">
    @foreach($navCategories as $parent)
      <div class="cat-float-item" x-on:mouseenter="hoverCat = {{ $parent->id }}" x-on:mouseleave="hoverCat = null">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="cat-float-link" :class="hoverCat === {{ $parent->id }} && 'active'">
          <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon])</span>
          {{ $parent->name }}
        </a>

        <div class="cat-flyout" x-show="hoverCat === {{ $parent->id }}" x-cloak>
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
