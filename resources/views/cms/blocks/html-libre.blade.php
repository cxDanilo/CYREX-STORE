{{--
  ADVERTENCIA: este es el único bloque del CMS que renderiza contenido sin
  escapar. Es una válvula de escape deliberada para casos que ningún otro
  bloque cubre (ej. un widget de un tercero) — NO es el método principal
  del CMS. Quien tenga acceso a este bloque puede insertar cualquier HTML,
  incluido JavaScript. Seguro solo mientras el CMS tenga un único
  administrador de confianza (estado actual del proyecto).
--}}
<div class="wrap cms-block cms-html-libre">
  {!! $data['html'] ?? '' !!}
</div>
