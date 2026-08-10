@extends('layouts.app')

@section('title', 'Arma tu PC — Cyrex Store')

@section('content')

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / Arma tu PC
</div>

<div class="wrap page-head">
  <div class="cat-eyebrow">Arma tu pc</div>
  <h1>Armá tu equipo pieza por pieza</h1>
  <p style="color:var(--text-secondary);font-size:14.5px;max-width:640px;margin-top:10px;line-height:1.6;">
    Te vamos guiando paso a paso — elegí una pieza a la vez y te mostramos solo lo que es compatible con lo que ya elegiste.
  </p>
</div>

<div class="wrap"
     x-data="{
        rate: {{ $rate }},
        types: @js($types),
        catalog: @js($catalog),
        gpuTiers: @js(\App\Models\PcBuilderOption::optionsFor('gpu_tier')),
        stepHints: @js([
          'platform' => '¿Con qué plataforma querés armar? Esto define qué procesadores y placas madre te vamos a mostrar después.',
          'cpu' => 'El procesador es el cerebro de tu PC — te mostramos los de la plataforma que elegiste.',
          'motherboard' => 'La placa madre conecta todo. Ya filtramos las que no sirven con el procesador elegido.',
          'ram' => 'La memoria RAM define cuántas cosas podés tener abiertas a la vez sin que se ponga lento.',
          'storage' => 'Acá se guardan tu sistema operativo, tus juegos y tus archivos — un SSD NVMe carga todo mucho más rápido que un disco tradicional.',
          'gpu' => 'La tarjeta gráfica es la que más impacta en juegos y edición de video.',
          'psu' => 'La fuente de poder alimenta todo el equipo — te avisamos si la elegida se queda corta.',
          'case' => 'El gabinete tiene que tener espacio para tu placa madre, tu tarjeta gráfica y tu enfriamiento.',
          'cooler' => 'El enfriamiento evita que el procesador se recaliente bajo uso exigente.',
        ]),

        platform: null,
        selected: {},
        step: 0,
        furthestStep: 0,
        ramQty: 1,
        // Estos dos pasos se pueden saltar sin elegir nada — no todos los
        // armados necesitan una tarjeta dedicada (CPU con gráficos
        // integrados) ni un cooler aparte (CPU que ya incluye uno de stock).
        optionalSteps: ['gpu', 'cooler'],

        get stepTypes() { return Object.keys(this.types); },

        get stepList() {
          return [
            { key: 'platform', label: 'Plataforma' },
            ...this.stepTypes.map(t => ({ key: t, label: this.types[t] })),
            { key: 'review', label: 'Listo' },
          ];
        },

        get currentType() {
          return this.step >= 1 && this.step <= this.stepTypes.length ? this.stepTypes[this.step - 1] : null;
        },

        get isReviewStep() { return this.step > this.stepTypes.length; },

        item(type) { return this.selected[type] || null; },

        totalUsd() {
          return Object.entries(this.selected).reduce((sum, [type, p]) => {
            if (!p) return sum;
            const qty = type === 'ram' ? this.ramQty : 1;
            return sum + p.price_usd * qty;
          }, 0);
        },

        get gpuTierLabel() {
          const gpuTierKey = this.selected.gpu?.compat?.tier;
          return gpuTierKey ? (this.gpuTiers[gpuTierKey] || null) : null;
        },

        // Hint extra según lo que ya se eligió — ej. avisar que el CPU
        // elegido ya trae gráficos integrados justo en el paso de GPU.
        get extraHint() {
          if (this.currentType === 'gpu' && this.selected.cpu?.compat?.graficos_integrados === 'si') {
            return 'Tu procesador ya tiene gráficos integrados — si no vas a jugar a alto nivel ni editar video pesado, podés saltar este paso.';
          }
          if (this.currentType === 'cooler' && this.selected.cpu?.compat?.incluye_cooler === 'si') {
            return 'Tu procesador ya incluye un cooler de stock — podés saltar este paso si te alcanza con eso.';
          }
          return null;
        },

        pick(type, product) {
          this.selected[type] = product;
          this.next();
        },

        remove(type) {
          delete this.selected[type];
        },

        resetBuild() {
          this.selected = {};
          this.platform = null;
          this.ramQty = 1;
          this.step = 0;
          this.furthestStep = 0;
        },

        get canProceed() {
          if (this.step === 0) return !!this.platform;
          if (this.currentType) {
            if (this.optionalSteps.includes(this.currentType)) return true;
            return !!this.selected[this.currentType];
          }
          return true;
        },

        next() {
          if (!this.canProceed) return;
          if (this.step < this.stepTypes.length + 1) this.step++;
          this.furthestStep = Math.max(this.furthestStep, this.step);
        },

        back() {
          if (this.step > 0) this.step--;
        },

        goToStep(i) {
          if (i <= this.furthestStep) this.step = i;
        },

        choosePlatform(p) {
          this.platform = p;
          this.next();
        },

        // Compatibilidad tolerante a datos faltantes: si a cualquiera de
        // los dos productos le falta el campo, no se marca error — recién
        // se compara cuando AMBOS lados tienen el dato cargado. Así el
        // armador no queda 'roto' mientras se va completando el catálogo
        // con los campos de compatibilidad.
        get currentIssues() {
          return this.computeIssues(this.selected);
        },

        get hasBlockingIssues() {
          return this.currentIssues.some(i => i.level === 'err');
        },

        // Función pura: nunca toca this.selected. incompatibleWith() la
        // usaba antes pisando this.selected temporalmente para 'probar'
        // una elección y después restaurarlo — eso competía con la
        // reactividad de Alpine (que puede re-evaluar bindings mientras
        // esa mutación temporal todavía estaba activa) y corrompía la
        // selección real. Separarla en una función pura sobre un objeto
        // aparte evita el problema de raíz.
        computeIssues(s) {
          const cpu = s.cpu?.compat || {};
          const mobo = s.motherboard?.compat || {};
          const ram = s.ram?.compat || {};
          const kase = s.case?.compat || {};
          const gpu = s.gpu?.compat || {};
          const cooler = s.cooler?.compat || {};
          const psu = s.psu?.compat || {};
          const issues = [];

          if (s.cpu && s.motherboard && cpu.socket && mobo.socket && cpu.socket !== mobo.socket) {
            issues.push({level:'err', msg: `${s.motherboard.name} usa socket ${mobo.socket}, pero ${s.cpu.name} necesita ${cpu.socket}.`});
          }
          if (s.motherboard && s.ram && mobo.ram_type && ram.type && mobo.ram_type !== ram.type) {
            issues.push({level:'err', msg: `${s.ram.name} es ${ram.type} y ${s.motherboard.name} solo soporta ${mobo.ram_type}.`});
          }
          // Una placa más chica siempre entra en un gabinete pensado para
          // una más grande (un ATX trae los parantes para mATX/ITX
          // también) — comparamos por tamaño, no por si está tildado el
          // valor exacto. Si algún form factor no es de los 3 estándar
          // (ATX/mATX/ITX), cae al chequeo exacto de siempre.
          if (s.motherboard && s.case && mobo.form_factor && kase.form_factors?.length) {
            const FORM_FACTOR_RANK = { ITX: 1, mATX: 2, ATX: 3 };
            const moboRank = FORM_FACTOR_RANK[mobo.form_factor];
            const caseRanks = kase.form_factors.map(f => FORM_FACTOR_RANK[f]);
            const allKnown = moboRank !== undefined && caseRanks.every(r => r !== undefined);
            const fits = allKnown
              ? Math.max(...caseRanks) >= moboRank
              : kase.form_factors.includes(mobo.form_factor);
            if (!fits) {
              issues.push({level:'err', msg: `${s.case.name} no soporta placas ${mobo.form_factor} (la elegida lo es).`});
            }
          }
          if (s.gpu && s.case && gpu.length_mm && kase.max_gpu_length_mm && Number(gpu.length_mm) > Number(kase.max_gpu_length_mm)) {
            issues.push({level:'err', msg: `${s.gpu.name} mide ${gpu.length_mm}mm y no entra en ${s.case.name} (máx ${kase.max_gpu_length_mm}mm).`});
          }
          if (s.cooler && s.case && Number(cooler.radiator_mm) > 0 && kase.max_radiator_mm !== undefined && Number(cooler.radiator_mm) > Number(kase.max_radiator_mm)) {
            issues.push({level:'err', msg: `${s.case.name} no soporta un radiador de ${cooler.radiator_mm}mm.`});
          }
          if (s.cooler && s.cpu && cooler.sockets?.length && cpu.socket && !cooler.sockets.includes(cpu.socket)) {
            issues.push({level:'err', msg: `${s.cooler.name} no tiene kit de montaje para socket ${cpu.socket}.`});
          }
          if (s.psu && (s.cpu || s.gpu) && psu.watts) {
            const need = Math.ceil((100 + Number(cpu.tdp_w || 0) + Number(gpu.power_draw_w || 0)) * 1.3 / 10) * 10;
            if (Number(psu.watts) < need) {
              issues.push({level:'warn', msg: `Se recomienda una fuente de al menos ${need}W para esta combinación; la elegida es de ${psu.watts}W.`});
            }
          }
          // Una fuente 'genérica' (sin certificación 80 Plus) casi nunca
          // entrega de verdad el wattage que anuncia en la caja — se avisa
          // aparte del cálculo de watts necesarios, que asume que el dato
          // cargado es real.
          if (s.psu && (!psu.certificacion || psu.certificacion === 'ninguna')) {
            issues.push({level:'warn', msg: `${s.psu.name} no tiene certificación 80 Plus — las fuentes genéricas suelen no entregar el wattage real que anuncian. Se recomienda una certificada, sobre todo para este armado.`});
          }
          return issues;
        },

        incompatibleWith(type, product) {
          const test = {...this.selected, [type]: product};
          return this.computeIssues(test).filter(i => i.level === 'err');
        },

        // Se calcula una sola vez por producto acá (en vez de que :class,
        // :disabled y el x-if de abajo llamen incompatibleWith() cada uno
        // por su cuenta) — así el paso solo tiene un getter reactivo del
        // que depende todo, en vez de varias llamadas a función separadas
        // sobre el mismo dato. Para 'cpu' además filtra por la plataforma
        // elegida en el paso 0.
        get currentOptions() {
          const type = this.currentType;
          if (!type) return [];
          let list = this.catalog[type] || [];
          if (type === 'cpu' && this.platform) {
            list = list.filter(p => (p.compat?.platform || null) === this.platform);
          }
          return list.map(product => {
            const errs = this.incompatibleWith(type, product);
            return { product, blocked: errs.length > 0, reason: errs[0]?.msg || null };
          });
        },

        // El carrito no maneja cantidades (decisión de diseño ya tomada
        // para todo el sitio) — si se eligieron 2 memorias, acá solo se
        // agrega 1 unidad al carrito; la cantidad real sí queda reflejada
        // en el total de este armador y en el mensaje de WhatsApp.
        async addAllToCart() {
          for (const product of Object.values(this.selected)) {
            await $store.cart.add(product.id, null);
          }
        }
     }">

  <div class="pcb-stepper">
    <template x-for="(s, i) in stepList" :key="s.key">
      <button type="button" class="pcb-step-dot" :class="{done: i < furthestStep, active: i === step, upcoming: i > furthestStep}" :disabled="i > furthestStep" @click="goToStep(i)">
        <span class="pcb-step-dot-circle" x-text="i < furthestStep && i !== step ? '✓' : (i + 1)"></span>
        <span class="pcb-step-dot-label" x-text="s.label"></span>
      </button>
    </template>
  </div>

  <div style="display:flex;flex-direction:column;gap:6px;margin:20px 0;" x-show="Object.keys(selected).length">
    <template x-for="issue in currentIssues" :key="issue.msg">
      <div class="pcb-issue-pill" :class="issue.level"><span x-text="issue.level === 'err' ? '✕' : '⚠'"></span> <span x-text="issue.msg"></span></div>
    </template>
    <div class="pcb-issue-pill ok" x-show="Object.keys(selected).length && currentIssues.length === 0">✓ Sin conflictos detectados hasta ahora</div>
  </div>

  <div class="pcb-layout">
    <div class="pcb-wizard-panel">

      <template x-if="step === 0">
        <div>
          <div class="pcb-step-hint">
            <svg class="pcb-step-hint-arrow" width="14" height="18" viewBox="0 0 14 18" fill="none"><path d="M7 1v14M1 9l6 7 6-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span x-text="stepHints.platform"></span>
          </div>
          <div class="pcb-platform-grid">
            <button type="button" class="pcb-platform-card pcb-platform-amd" @click="choosePlatform('AMD')">
              <span class="pcb-platform-name">AMD</span>
              <span class="pcb-platform-sub">Ryzen y compatibles</span>
            </button>
            <button type="button" class="pcb-platform-card pcb-platform-intel" @click="choosePlatform('Intel')">
              <span class="pcb-platform-name">Intel</span>
              <span class="pcb-platform-sub">Core y compatibles</span>
            </button>
          </div>
        </div>
      </template>

      <template x-for="type in stepTypes" :key="type">
        <template x-if="currentType === type">
          <div>
            <div class="pcb-step-hint">
              <svg class="pcb-step-hint-arrow" width="14" height="18" viewBox="0 0 14 18" fill="none" x-show="step <= 1"><path d="M7 1v14M1 9l6 7 6-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span x-text="stepHints[type]"></span>
            </div>
            <template x-if="extraHint">
              <div class="pcb-step-hint pcb-step-hint-extra">
                <span x-text="extraHint"></span>
              </div>
            </template>
            <h3 style="margin-bottom:14px;">Elegí: <span x-text="types[type]"></span></h3>
            <template x-if="type === 'ram' && item('ram')">
              <div class="pcb-ram-qty">
                <span>Cantidad:</span>
                <button type="button" :class="ramQty === 1 && 'active'" @click="ramQty = 1">×1</button>
                <button type="button" :class="ramQty === 2 && 'active'" @click="ramQty = 2">×2 (dual-channel)</button>
              </div>
            </template>
            <template x-if="!currentOptions.length">
              <p class="form-hint">
                <template x-if="type === 'cpu' && platform">
                  <span>Todavía no hay procesadores <span x-text="platform"></span> cargados.</span>
                </template>
                <template x-if="!(type === 'cpu' && platform)">
                  <span>Todavía no hay productos cargados en esta categoría.</span>
                </template>
              </p>
            </template>
            <div class="pcb-picker-grid">
              <template x-for="opt in currentOptions" :key="opt.product.id">
                <button type="button" class="pcb-picker-card"
                        :class="[opt.blocked && 'disabled', item(type)?.id === opt.product.id && 'selected']"
                        :disabled="opt.blocked"
                        @click="!opt.blocked && pick(type, opt.product)">
                  <div class="pcb-picker-card-media">
                    <img :src="opt.product.image_url" x-show="opt.product.image_url" style="width:100%;height:100%;object-fit:cover;">
                  </div>
                  <div class="opt-name" x-text="opt.product.name"></div>
                  <div class="opt-price" x-text="'$' + opt.product.price_usd.toFixed(2)"></div>
                  <template x-if="type === 'gpu' && gpuTiers[opt.product.compat?.tier]">
                    <div class="opt-tier" x-text="gpuTiers[opt.product.compat?.tier]"></div>
                  </template>
                  <template x-if="opt.blocked">
                    <div class="opt-reason" x-text="opt.reason"></div>
                  </template>
                </button>
              </template>
            </div>
          </div>
        </template>
      </template>

      <template x-if="isReviewStep">
        <div class="pcb-review">
          <div class="pcb-review-check">✓</div>
          <h3>¡Listo! Este es tu build</h3>
          <p class="form-hint">Revisá el resumen acá al lado — podés volver a cualquier paso de arriba para cambiar una pieza.</p>
          <template x-if="gpuTierLabel">
            <div class="pcb-tier-badge">🎮 Rendimiento estimado: <strong x-text="gpuTierLabel"></strong></div>
          </template>
        </div>
      </template>

      <div class="pcb-step-nav">
        <button type="button" class="btn" @click="back()" x-show="step > 0">← Atrás</button>
        <button type="button" class="btn btn-primary" @click="next()" x-show="!isReviewStep && currentType" :disabled="!canProceed" style="margin-left:auto;">
          <span x-text="optionalSteps.includes(currentType) && !item(currentType) ? 'Saltar este paso →' : 'Siguiente →'"></span>
        </button>
      </div>
    </div>

    <div class="pcb-summary-panel">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin-bottom:0;">Tu build</h3>
        <button type="button" class="pcb-reset-btn" @click="resetBuild()" x-show="platform || Object.keys(selected).length">Limpiar todo</button>
      </div>
      @foreach($types as $typeKey => $typeLabel)
        <div class="pcb-summary-row">
          <span class="k">{{ $typeLabel }}</span>
          @if($typeKey === 'ram')
            <span class="v" :class="!item('ram') && 'empty'" x-text="item('ram') ? (ramQty > 1 ? ramQty + '× ' : '') + item('ram').name : 'Sin elegir'"></span>
          @else
            <span class="v" :class="!item('{{ $typeKey }}') && 'empty'" x-text="item('{{ $typeKey }}')?.name || 'Sin elegir'"></span>
          @endif
        </div>
      @endforeach
      <div class="pcb-summary-total"><span>Total</span><span class="v" x-text="'$' + totalUsd().toFixed(2)"></span></div>
      @if($currencyMode === 'both')
        <div class="pcb-summary-alt" x-text="'≈ Bs ' + (totalUsd() * rate).toFixed(2)"></div>
      @endif

      <button type="button" class="btn-cta" style="width:100%;margin-top:16px;" :disabled="!Object.keys(selected).length || hasBlockingIssues" @click="addAllToCart()">
        Agregar todo al carrito
      </button>
      <a :href="'https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '59177947379') }}?text=' + encodeURIComponent('Hola! Quiero armar esta PC:\n' + Object.entries(selected).map(([type, p]) => '- ' + (type === 'ram' && ramQty > 1 ? ramQty + 'x ' : '') + p.name).join('\n') + '\nTotal aprox: $' + totalUsd().toFixed(2))"
         target="_blank" rel="noopener" class="btn-cta-whatsapp" style="width:100%;margin-top:10px;text-decoration:none;" x-show="Object.keys(selected).length">
        Consultar por WhatsApp
      </a>
    </div>
  </div>

</div>

@endsection
