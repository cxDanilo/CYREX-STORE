{{-- Franja de confianza (garantía / envíos / soporte) — contenido fijo,
     no viene del CMS: son las mismas 3 promesas en toda página de
     producto, no algo que un admin necesite reordenar o editar seguido.
     Si más adelante hace falta lo mismo en otras páginas, se puede
     mover a un bloque de CMS reutilizando esta misma vista + partials.trust-icon. --}}
<div class="trust-badges">
  <div class="trust-badge">
    <span class="trust-badge-icon">@include('partials.trust-icon', ['icon' => 'shield'])</span>
    <h4>Garantía real</h4>
    <p>Trabajamos solo con hardware 100% original, sellado de fábrica. Cada componente tiene garantía real ante cualquier falla.</p>
  </div>
  <div class="trust-badge">
    <span class="trust-badge-icon">@include('partials.trust-icon', ['icon' => 'truck'])</span>
    <h4>Envíos a nivel nacional</h4>
    <p>Llegamos a todos los departamentos y provincias de Bolivia, además de delivery directo a tu domicilio.</p>
  </div>
  <div class="trust-badge">
    <span class="trust-badge-icon">@include('partials.trust-icon', ['icon' => 'support'])</span>
    <h4>Soporte técnico</h4>
    <p>Atención y soporte técnico presencial en tienda, más asesoría personalizada por WhatsApp para resolver tus dudas.</p>
  </div>
</div>
