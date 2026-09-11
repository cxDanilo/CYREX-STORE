<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\ReferralRouter;
use Illuminate\Database\Seeder;

/**
 * Crea/actualiza la página "quienes-somos" (Quiénes somos / Misión /
 * Visión / Valores), contada como una historia con scroll animado en vez
 * de bloques de texto seguidos. Es un único bloque 'html_libre' (en vez
 * de los bloques genéricos del CMS) porque el diseño necesita secciones a
 * todo el ancho de la pantalla y animaciones de scroll que ningún bloque
 * existente cubre. Busca la página por slug (no por id) para funcionar
 * igual en cualquier entorno. Se puede correr más de una vez sin duplicar
 * nada — cada corrida reemplaza el contenido del bloque por completo.
 */
class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'quienes-somos'],
            ['title' => 'Quiénes Somos', 'status' => 'draft', 'show_in_footer' => true, 'footer_sort_order' => 5]
        );

        $page->update([
            'title' => 'Quiénes Somos',
            'meta_title' => '',
            'meta_description' => 'Conoce la historia de Cyrex Store, nuestra misión, nuestra visión y los valores que nos mueven todos los días.',
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $page->blocks()->delete();

        $whatsappUrl = 'https://wa.me/'.ReferralRouter::whatsappNumber();
        $htmlPart1 = str_replace('__WHATSAPP_URL__', $whatsappUrl, $this->htmlPart1());
        $htmlPart2 = str_replace('__WHATSAPP_URL__', $whatsappUrl, $this->htmlPart2());

        // La foto va como bloque 'imagen' aparte (en vez de ir metida en el
        // HTML crudo) para que se pueda subir/cambiar desde el editor con
        // el selector de medios normal, como cualquier otra imagen del
        // sitio — el HTML crudo no tiene un campo de "subir archivo".
        $blocks = [
            ['type' => 'html_libre', 'data' => ['html' => $htmlPart1]],
            ['type' => 'imagen', 'data' => ['url' => '', 'alt' => 'Equipo Cyrex Store', 'leyenda' => '']],
            ['type' => 'html_libre', 'data' => ['html' => $htmlPart2]],
        ];

        foreach ($blocks as $i => $block) {
            $page->blocks()->create([
                'type' => $block['type'],
                'data' => $block['data'],
                'sort_order' => $i,
            ]);
        }

        $this->command?->info('Página "quienes-somos" creada/actualizada ('.count($blocks).' bloques).');
    }

    private function htmlPart1(): string
    {
        return <<<'HTML'
<style>
  .qs-page{--qs-band-mision:#0f0b02;--qs-band-vision:#0a0a0a;color:var(--text-primary);font-family:var(--font-body);}
  .qs-fullbleed{width:100vw;position:relative;left:50%;right:50%;margin-left:-50vw;margin-right:-50vw;overflow:hidden;}
  .qs-reveal{opacity:0;transform:translateY(36px);transition:opacity .9s cubic-bezier(.22,1,.36,1),transform .9s cubic-bezier(.22,1,.36,1);}
  .qs-reveal.is-in{opacity:1;transform:translateY(0);}
  .qs-reveal-delay-1{transition-delay:.12s;}
  .qs-reveal-delay-2{transition-delay:.24s;}
  @media (prefers-reduced-motion:reduce){.qs-reveal{opacity:1;transform:none;transition:none;}}

  .qs-hero{position:relative;height:88vh;min-height:560px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;}
  .qs-hero-bg-text{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;}
  .qs-hero-bg-text span{font-family:var(--font-display);font-weight:700;font-size:min(24vw,300px);color:transparent;-webkit-text-stroke:1px rgba(255,255,255,.06);white-space:nowrap;will-change:transform;}
  .qs-hero-content{position:relative;z-index:2;max-width:780px;padding:0 24px;}
  .qs-hero-eyebrow{font-family:var(--font-mono);font-size:12px;color:var(--gold);letter-spacing:.14em;text-transform:uppercase;margin-bottom:22px;opacity:0;animation:qs-rise .8s ease forwards .1s;}
  .qs-hero-title{font-family:var(--font-display);font-weight:700;font-size:clamp(28px,5.2vw,54px);line-height:1.1;letter-spacing:-.01em;opacity:0;animation:qs-rise .9s cubic-bezier(.22,1,.36,1) forwards .25s;}
  .qs-scroll-cue{position:absolute;bottom:30px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--text-muted);font-family:var(--font-mono);font-size:10.5px;letter-spacing:.08em;opacity:0;animation:qs-fadein 1s ease forwards 1.1s;}
  .qs-scroll-cue .qs-line{width:1px;height:30px;background:linear-gradient(var(--gold),transparent);animation:qs-cue-drop 1.8s ease-in-out infinite;}
  @keyframes qs-rise{from{opacity:0;transform:translateY(26px);}to{opacity:1;transform:translateY(0);}}
  @keyframes qs-fadein{to{opacity:1;}}
  @keyframes qs-cue-drop{0%{transform:scaleY(0);transform-origin:top;}45%{transform:scaleY(1);transform-origin:top;}55%{transform:scaleY(1);transform-origin:bottom;}100%{transform:scaleY(0);transform-origin:bottom;}}
  @media (prefers-reduced-motion:reduce){.qs-hero-eyebrow,.qs-hero-title,.qs-scroll-cue{opacity:1;animation:none;}}

  .qs-section{padding:110px 24px;max-width:1080px;margin:0 auto;}
  .qs-kicker{font-family:var(--font-mono);font-size:12px;color:var(--gold);letter-spacing:.14em;text-transform:uppercase;margin-bottom:18px;display:flex;align-items:center;gap:12px;}
  .qs-kicker::before{content:'';width:28px;height:1px;background:var(--gold);}

  .qs-about-copy{max-width:680px;margin:0 auto;}
  .qs-about-copy p{font-size:17px;line-height:1.75;color:var(--text-secondary);margin-bottom:20px;}
  .qs-about-copy p:last-child{margin-bottom:0;}
  .qs-about-copy strong{color:var(--text-primary);font-weight:600;}

  .wrap.cms-imagen{max-width:900px;}
  .wrap.cms-imagen img{aspect-ratio:16/9;object-fit:cover;}

  .qs-statement-band{padding:130px 24px;text-align:center;}
  .qs-statement-band .qs-kicker{justify-content:center;}
  .qs-statement-band .qs-kicker::before{display:none;}
  .qs-statement-headline{font-family:var(--font-display);font-weight:700;font-size:clamp(26px,4.2vw,46px);line-height:1.18;letter-spacing:-.01em;max-width:820px;margin:0 auto 26px;}
  .qs-statement-body{font-size:16px;line-height:1.8;color:var(--text-secondary);max-width:640px;margin:0 auto;}
  .qs-statement-body strong{color:var(--text-primary);font-weight:600;}
  .qs-band-mision{background:linear-gradient(180deg,var(--bg) 0%, var(--qs-band-mision) 50%, var(--bg) 100%);}
  .qs-band-vision{background:linear-gradient(180deg,var(--bg) 0%, #0f0d02 50%, var(--bg) 100%);}
  .qs-band-vision .qs-statement-headline{color:var(--gold);}

  .qs-values-head{text-align:center;margin-bottom:56px;}
  .qs-values-head h2{font-family:var(--font-display);font-weight:700;font-size:clamp(24px,3.4vw,36px);}
  .qs-values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:20px;overflow:hidden;}
  .qs-value-card{background:var(--bg);padding:34px 24px;transition:background .3s ease;}
  .qs-value-card:hover{background:var(--bg-elevated);}
  .qs-value-icon{width:44px;height:44px;border-radius:12px;background:var(--bg-elevated-2);display:flex;align-items:center;justify-content:center;color:var(--gold);margin-bottom:20px;transition:transform .35s cubic-bezier(.34,1.56,.64,1);}
  .qs-value-card:hover .qs-value-icon{transform:scale(1.1) rotate(-4deg);}
  .qs-value-title{font-family:var(--font-display);font-weight:700;font-size:17px;margin-bottom:10px;}
  .qs-value-text{font-size:13.5px;line-height:1.65;color:var(--text-secondary);}
  @media (max-width:860px){.qs-values-grid{grid-template-columns:1fr 1fr;}}
  @media (max-width:520px){.qs-values-grid{grid-template-columns:1fr;}}

  .qs-closing{text-align:center;padding:60px 24px 20px;}
  .qs-closing-title{font-family:var(--font-display);font-weight:700;font-size:clamp(22px,3vw,32px);margin-bottom:26px;max-width:580px;margin-inline:auto;}
  .qs-btn-gold{display:inline-flex;align-items:center;gap:8px;background:var(--gold);color:#0A0A0B;font-weight:600;font-size:14px;padding:14px 28px;border-radius:100px;text-decoration:none;}
</style>

<div class="qs-page">

  <section class="qs-hero qs-fullbleed">
    <div class="qs-hero-bg-text" data-qs-parallax><span>CYREX STORE</span></div>
    <div class="qs-hero-content">
      <div class="qs-hero-eyebrow">Quiénes somos</div>
      <h1 class="qs-hero-title">Todo comenzó viendo una oportunidad donde otros solo veían un mercado.</h1>
    </div>
    <div class="qs-scroll-cue"><span>SCROLL</span><span class="qs-line"></span></div>
  </section>

  <section class="qs-section">
    <div class="qs-about-copy">
      <div class="qs-kicker qs-reveal">Nuestra historia</div>
      <p class="qs-reveal qs-reveal-delay-1">CYREX Store nació después de la pandemia, en medio de una etapa donde todo estaba cambiando.</p>
      <p class="qs-reveal qs-reveal-delay-1">Vimos un espacio que todavía podía hacerse diferente. Un lugar donde comprar tecnología no fuera simplemente elegir un producto, pagar y marcharse.</p>
      <p class="qs-reveal qs-reveal-delay-2"><strong>Queríamos construir algo más.</strong></p>
      <p class="qs-reveal qs-reveal-delay-2">Un espacio donde cada persona pudiera recibir una recomendación honesta, encontrar el equipo adecuado para lo que realmente necesita y disfrutar el proceso de armar, mejorar o comprar su primera PC.</p>
      <p class="qs-reveal qs-reveal-delay-2">Desde entonces hemos crecido junto a nuestros clientes, aprendiendo, mejorando y formando una comunidad alrededor de algo que nos apasiona: la tecnología.</p>
      <p class="qs-reveal qs-reveal-delay-2">Somos CYREX Store. Vendemos tecnología, pero buscamos entregar mucho más que eso: <strong>confianza, experiencia y emoción</strong> en cada compra.</p>
    </div>
  </section>

</div>
HTML;
    }

    private function htmlPart2(): string
    {
        return <<<'HTML'
<div class="qs-page">

  <section class="qs-statement-band qs-band-mision qs-fullbleed">
    <div class="qs-section" style="padding:0;">
      <div class="qs-kicker qs-reveal">Nuestra misión</div>
      <h2 class="qs-statement-headline qs-reveal qs-reveal-delay-1">Transformar la manera en que las personas viven la tecnología.</h2>
      <p class="qs-statement-body qs-reveal qs-reveal-delay-2">Queremos que cada persona que llegue a CYREX encuentre asesoramiento, confianza y una experiencia de compra que realmente marque la diferencia. Desde elegir un componente hasta encender una nueva PC por primera vez, acompañamos cada decisión con pasión, conocimiento y compromiso. <strong>Porque para nosotros una buena compra no termina cuando entregamos el producto — termina cuando el cliente siente que tomó la decisión correcta.</strong></p>
    </div>
  </section>

  <section class="qs-statement-band qs-band-vision qs-fullbleed">
    <div class="qs-section" style="padding:0;">
      <div class="qs-kicker qs-reveal">Nuestra visión</div>
      <h2 class="qs-statement-headline qs-reveal qs-reveal-delay-1">Convertirnos en una marca que las personas elijan por confianza, no solamente por precio.</h2>
      <p class="qs-statement-body qs-reveal qs-reveal-delay-2">Aspiramos a posicionar a CYREX Store como uno de los principales referentes tecnológicos de Bolivia, llevando nuestra forma de entender la tecnología a cada vez más personas y ciudades — sin perder aquello que nos hizo comenzar: cercanía, pasión y atención personalizada. <strong>Nuestro objetivo no es simplemente vender más tecnología. Es elevar la experiencia de comprarla.</strong></p>
    </div>
  </section>

  <section class="qs-section">
    <div class="qs-values-head qs-reveal">
      <div class="qs-kicker" style="justify-content:center;">Lo que nos define</div>
      <h2>Nuestros valores</h2>
    </div>
    <div class="qs-values-grid">
      <div class="qs-value-card qs-reveal qs-reveal-delay-1">
        <div class="qs-value-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-4.5-9.5-9C.7 8.2 2 4.5 5.5 4c2-.3 3.7.7 4.5 2.2C10.8 4.7 12.5 3.7 14.5 4c3.5.5 4.8 4.2 3 8-2.5 4.5-9.5 9-9.5 9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></div>
        <div class="qs-value-title">Pasión</div>
        <div class="qs-value-text">Nos gusta lo que hacemos. Disfrutamos descubrir, probar y compartir tecnología, y esa energía está en cada equipo que armamos y cada cliente que atendemos.</div>
      </div>
      <div class="qs-value-card qs-reveal qs-reveal-delay-1">
        <div class="qs-value-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9.5 12l1.8 1.8L15 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="qs-value-title">Confianza</div>
        <div class="qs-value-text">Construimos relaciones a largo plazo. Recomendamos pensando primero en lo que realmente necesita cada persona, no en vender el producto más caro.</div>
      </div>
      <div class="qs-value-card qs-reveal qs-reveal-delay-2">
        <div class="qs-value-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="qs-value-title">Experiencia</div>
        <div class="qs-value-text">Cuidamos cada detalle antes, durante y después de una compra. Desde la atención hasta la presentación y el soporte, cada interacción con CYREX es diferente.</div>
      </div>
      <div class="qs-value-card qs-reveal qs-reveal-delay-2">
        <div class="qs-value-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="8" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="16" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 20c.5-3 2.7-5 5.5-5s5 2 5.5 5M11 20c.5-3 2.7-5 5.5-5s5 2 5.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
        <div class="qs-value-title">Comunidad</div>
        <div class="qs-value-text">No existimos solo detrás de un mostrador. Creamos un espacio donde gamers, creadores, profesionales y amantes de la tecnología se encuentran y crecen.</div>
      </div>
    </div>
  </section>

  <section class="qs-closing qs-section">
    <div class="qs-closing-title qs-reveal">¿Querés ser parte de esta historia?</div>
    <a href="__WHATSAPP_URL__" target="_blank" class="qs-btn-gold qs-reveal qs-reveal-delay-1">Escribinos por WhatsApp</a>
  </section>

</div>

<script>
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('motion-reduced');
  if (!reduced) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
    document.querySelectorAll('.qs-reveal').forEach(function (el) { io.observe(el); });

    var bgText = document.querySelector('[data-qs-parallax]');
    if (bgText) {
      var raf = null;
      window.addEventListener('scroll', function () {
        if (raf) return;
        raf = requestAnimationFrame(function () {
          raf = null;
          var y = window.scrollY;
          bgText.style.transform = 'translateY(' + (y * 0.25) + 'px)';
          bgText.style.opacity = Math.max(0, 1 - y / 500);
        });
      });
    }
  } else {
    document.querySelectorAll('.qs-reveal').forEach(function (el) { el.classList.add('is-in'); });
  }
})();
</script>
HTML;
    }
}
