<div class="wrap cms-block" style="text-align:center;">
  <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '59177947379') }}" target="_blank" class="footer-whatsapp-btn" style="display:inline-flex;">
    @include('partials.whatsapp-icon')
    {{ $data['texto'] ?? 'Escríbenos por WhatsApp' }}
  </a>
</div>
