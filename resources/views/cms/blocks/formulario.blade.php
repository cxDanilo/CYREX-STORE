<div class="wrap cms-block cms-formulario" x-data="{ nombre: '', mensaje: '' }">
  @if(!empty($data['titulo']))
    <h3 class="cms-titulo cms-titulo-mediano" style="margin-bottom:16px;">{{ $data['titulo'] }}</h3>
  @endif
  <div class="cms-form-field">
    <input type="text" x-model="nombre" placeholder="Tu nombre">
  </div>
  <div class="cms-form-field">
    <textarea x-model="mensaje" placeholder="Tu mensaje" rows="4"></textarea>
  </div>
  <a class="btn btn-primary"
     :href="'https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '59177947379') }}?text=' + encodeURIComponent('Hola! Soy ' + (nombre || '(sin nombre)') + '. ' + mensaje)"
     target="_blank">
    {{ $data['boton_texto'] ?? 'Enviar por WhatsApp' }}
  </a>
</div>
