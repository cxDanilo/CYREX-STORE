<nav>
  <div class="wrap nav-inner">
    <div class="logo"><a href="{{ route('home') }}" style="color:inherit;">CYREX<span>.</span></a></div>

    <form class="nav-search" method="GET" action="{{ route('shop') }}">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en todo Cyrex Store" />
      <button class="search-btn" type="submit">Buscar</button>
    </form>

    <div class="nav-actions">
      <a href="{{ route('shop') }}" class="btn-gold" style="text-decoration:none;">Ver tienda</a>
    </div>
  </div>
</nav>
