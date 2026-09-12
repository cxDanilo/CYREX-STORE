// Video de fondo del hero cuando es un link de YouTube (bloque CMS
// "Hero con video"): en vez de un <iframe src="..."> fijo, arma el
// reproductor con la API de YouTube para poder:
//   1) tapar con una cortina el destello inicial de la UI nativa de
//      YouTube (aparece un instante como "pausado", con los controles
//      de anterior/play/siguiente del truco de loop con playlist de un
//      solo video, hasta que el autoplay arranca de verdad), y
//   2) forzar que retome el play cuando se vuelve a la pestaña -- por
//      default YouTube pausa el video embebido en cuanto la pestaña
//      deja de estar visible y no lo retoma solo.
(function () {
  var placeholders = document.querySelectorAll('.cms-hero-video-yt');
  if (!placeholders.length) return;

  function loadApi(ready) {
    if (window.YT && window.YT.Player) {
      ready();
      return;
    }
    var previous = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function () {
      if (typeof previous === 'function') previous();
      ready();
    };
    if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
      var tag = document.createElement('script');
      tag.src = 'https://www.youtube.com/iframe_api';
      document.head.appendChild(tag);
    }
  }

  function initPlayer(el) {
    var videoId = el.dataset.ytId;
    if (!videoId) return;

    var start = parseInt(el.dataset.ytStart || '0', 10) || 0;
    var curtain = el.nextElementSibling;
    if (!curtain || !curtain.classList.contains('cms-hero-video-curtain')) curtain = null;

    // Sin loop+playlist acá a propósito: ese truco (el único jeito de
    // hacer loop nativo de YouTube) hace que Chrome trate el video como
    // una "lista de reproducción", y le agrega botones de
    // anterior/siguiente a los controles nativos que aparecen al pasar
    // el mouse por encima de CUALQUIER <video>, incluso con controls:0
    // -- eso es lo que se veía como una franja rota en el medio del
    // hero. El loop se hace a mano en onStateChange (ENDED) para evitar
    // el trigger de esos botones; sigue quedando el botón de
    // play/pausa nativo al pasar el mouse, pero eso ya lo pone el
    // navegador para cualquier video y no se puede evitar del todo.
    var playerVars = {
      autoplay: 1,
      mute: 1,
      controls: 0,
      showinfo: 0,
      modestbranding: 1,
      rel: 0,
      disablekb: 1,
      playsinline: 1,
    };
    if (start > 0) playerVars.start = start;

    var player = new YT.Player(el.id, {
      width: '100%',
      height: '100%',
      videoId: videoId,
      playerVars: playerVars,
      events: {
        onReady: function (e) {
          var iframe = e.target.getIframe();
          if (iframe) {
            iframe.classList.add('cms-hero-video-iframe');
            iframe.setAttribute('tabindex', '-1');
            iframe.setAttribute('aria-hidden', 'true');
          }
          e.target.mute();
          e.target.playVideo();
        },
        onStateChange: function (e) {
          if (e.data === YT.PlayerState.PLAYING && curtain) {
            curtain.classList.add('is-hidden');
          }
          if (e.data === YT.PlayerState.ENDED) {
            e.target.seekTo(start, true);
            e.target.playVideo();
          }
        },
      },
    });

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState !== 'visible') return;
      if (!player || typeof player.getPlayerState !== 'function') return;
      var state = player.getPlayerState();
      if (state === YT.PlayerState.PAUSED || state === YT.PlayerState.CUED) {
        player.playVideo();
      }
    });
  }

  loadApi(function () {
    placeholders.forEach(initPlayer);
  });
})();
