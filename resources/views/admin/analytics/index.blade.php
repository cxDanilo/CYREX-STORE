@extends('admin.layout')

@section('title', 'Analítica')

@section('content')

<div id="analytics-content">
  @include('admin.analytics._content')
</div>

<script>
(function () {
  var el = document.getElementById('analytics-content');

  // Todo el panel se re-renderiza entero cada 20s (mismo truco que ya usa
  // el carrito: el server manda HTML ya armado en el JSON, acá solo se
  // reemplaza) — así "conectados ahora" y el resto de los números se ven
  // solos, sin que alguien tenga que recargar la página a mano.
  function refresh() {
    fetch('{{ route('admin.analitica.refresh') }}', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) { el.innerHTML = data.html; })
      .catch(function () {});
  }

  setInterval(refresh, 20000);
})();
</script>

@endsection
