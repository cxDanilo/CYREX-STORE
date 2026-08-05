@php $formClass = $formClass ?? 'nav-search'; @endphp

<div class="search-wrap" x-data="{
      q: '{{ request('q') }}',
      results: [],
      open: false,
      timer: null,
      search() {
        clearTimeout(this.timer);
        const term = this.q.trim();
        if (term.length < 2) { this.results = []; this.open = false; return; }
        this.timer = setTimeout(async () => {
          const res = await fetch('{{ route('shop.suggest') }}?q=' + encodeURIComponent(term));
          const data = await res.json();
          this.results = data.results;
          this.open = this.results.length > 0;
        }, 250);
      }
    }" x-on:click.outside="open = false">
  <form class="{{ $formClass }}" method="GET" action="{{ route('shop') }}" x-on:submit="open = false">
    <button type="submit" class="search-icon-btn" aria-label="Buscar">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <input type="text" name="q" x-model="q" x-on:input="search()" placeholder="Buscar en todo Cyrex Store" autocomplete="off" />
  </form>

  <div class="search-suggestions" x-show="open" x-transition.opacity.duration.150ms x-cloak>
    <template x-for="r in results" :key="r.url">
      <a :href="r.url" class="search-suggestion-item">
        <div class="search-suggestion-media">
          <img :src="r.image" x-show="r.image" alt="">
        </div>
        <div class="search-suggestion-body">
          <div class="search-suggestion-cat" x-text="r.category"></div>
          <div class="search-suggestion-name" x-text="r.name"></div>
        </div>
        <div class="search-suggestion-price mono" x-text="r.price"></div>
      </a>
    </template>
  </div>
</div>
