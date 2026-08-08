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
    Elegí cada pieza en el orden que quieras. Si algo no es compatible con lo que ya elegiste, te lo decimos antes de que lo agregues al carrito.
  </p>
</div>

<div class="wrap"
     x-data="{
        rate: {{ $rate }},
        types: @js($types),
        catalog: @js($catalog),
        selected: {},
        openPicker: null,

        item(type) { return this.selected[type] || null; },

        priceOf(product) { return product.price_usd; },

        totalUsd() {
          return Object.values(this.selected).reduce((sum, p) => sum + (p ? p.price_usd : 0), 0);
        },

        pick(type, product) {
          this.selected[type] = product;
          this.openPicker = null;
        },

        remove(type) {
          delete this.selected[type];
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
          if (s.motherboard && s.case && mobo.form_factor && kase.form_factors?.length && !kase.form_factors.includes(mobo.form_factor)) {
            issues.push({level:'err', msg: `${s.case.name} no soporta placas ${mobo.form_factor} (la elegida lo es).`});
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
          return issues;
        },

        incompatibleWith(type, product) {
          const test = {...this.selected, [type]: product};
          return this.computeIssues(test).filter(i => i.level === 'err');
        },

        // Se calcula una sola vez por producto acá (en vez de que :class,
        // :disabled y el x-if de abajo llamen incompatibleWith() cada uno
        // por su cuenta) — así el picker solo tiene un getter reactivo del
        // que depende todo, en vez de tres llamadas a función separadas
        // sobre el mismo dato.
        get pickerOptions() {
          return (this.catalog[this.openPicker] || []).map(product => {
            const errs = this.incompatibleWith(this.openPicker, product);
            return { product, blocked: errs.length > 0, reason: errs[0]?.msg || null };
          });
        },

        async addAllToCart() {
          for (const product of Object.values(this.selected)) {
            await $store.cart.add(product.id, null);
          }
        }
     }">

  <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:20px;" x-show="Object.keys(selected).length">
    <template x-for="issue in currentIssues" :key="issue.msg">
      <div class="pcb-issue-pill" :class="issue.level"><span x-text="issue.level === 'err' ? '✕' : '⚠'"></span> <span x-text="issue.msg"></span></div>
    </template>
    <div class="pcb-issue-pill ok" x-show="Object.keys(selected).length && currentIssues.length === 0">✓ Sin conflictos detectados hasta ahora</div>
  </div>

  <div class="pcb-layout">
    <div class="pcb-slots-grid">
      @foreach($types as $typeKey => $typeLabel)
        <div class="pcb-slot" @click="openPicker = '{{ $typeKey }}'">
          <div class="pcb-slot-media">
            <img :src="item('{{ $typeKey }}')?.image_url" x-show="item('{{ $typeKey }}')?.image_url" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
            <span x-show="!item('{{ $typeKey }}')?.image_url" style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);">{{ mb_substr($typeLabel, 0, 1) }}</span>
          </div>
          <div class="pcb-slot-body">
            <div class="pcb-slot-label">{{ $typeLabel }}</div>
            <div class="pcb-slot-value" x-text="item('{{ $typeKey }}')?.name || '+ Elegir'" :class="!item('{{ $typeKey }}') && 'empty'"></div>
            <div class="pcb-slot-sub" x-show="item('{{ $typeKey }}')" x-text="item('{{ $typeKey }}') ? '$' + item('{{ $typeKey }}').price_usd.toFixed(2) : ''"></div>
          </div>
          <button type="button" class="pcb-slot-remove" x-show="item('{{ $typeKey }}')" @click.stop="remove('{{ $typeKey }}')" aria-label="Quitar">&times;</button>
        </div>
      @endforeach
    </div>

    <div class="pcb-summary-panel">
      <h3>Tu build</h3>
      @foreach($types as $typeKey => $typeLabel)
        <div class="pcb-summary-row">
          <span class="k">{{ $typeLabel }}</span>
          <span class="v" :class="!item('{{ $typeKey }}') && 'empty'" x-text="item('{{ $typeKey }}')?.name || 'Sin elegir'"></span>
        </div>
      @endforeach
      <div class="pcb-summary-total"><span>Total</span><span class="v" x-text="'$' + totalUsd().toFixed(2)"></span></div>
      @if($currencyMode === 'both')
        <div class="pcb-summary-alt" x-text="'≈ Bs ' + (totalUsd() * rate).toFixed(2)"></div>
      @endif

      <button type="button" class="btn-cta" style="width:100%;margin-top:16px;" :disabled="!Object.keys(selected).length || hasBlockingIssues" @click="addAllToCart()">
        Agregar todo al carrito
      </button>
      <a :href="'https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '59177947379') }}?text=' + encodeURIComponent('Hola! Quiero armar esta PC:\n' + Object.values(selected).map(p => '- ' + p.name).join('\n') + '\nTotal aprox: $' + totalUsd().toFixed(2))"
         target="_blank" rel="noopener" class="btn-cta-whatsapp" style="width:100%;margin-top:10px;text-decoration:none;" x-show="Object.keys(selected).length">
        Consultar por WhatsApp
      </a>
    </div>
  </div>

  <div class="picker-overlay" x-show="openPicker" x-cloak @click.self="openPicker = null">
    <div class="picker-box">
      <button class="picker-close" @click="openPicker = null">&times;</button>
      <template x-if="openPicker">
        <div>
          <h3 x-text="'Elegir ' + types[openPicker]"></h3>
          <template x-if="!pickerOptions.length">
            <p class="form-hint">Todavía no hay productos cargados en esta categoría.</p>
          </template>
          <div class="pcb-picker-grid">
            <template x-for="opt in pickerOptions" :key="opt.product.id">
              <button type="button" class="pcb-picker-card"
                      :class="opt.blocked && 'disabled'"
                      :disabled="opt.blocked"
                      @click="!opt.blocked && pick(openPicker, opt.product)">
                <div class="pcb-picker-card-media">
                  <img :src="opt.product.image_url" x-show="opt.product.image_url" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div class="opt-name" x-text="opt.product.name"></div>
                <div class="opt-price" x-text="'$' + opt.product.price_usd.toFixed(2)"></div>
                <template x-if="opt.blocked">
                  <div class="opt-reason" x-text="opt.reason"></div>
                </template>
              </button>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>

</div>

@endsection
