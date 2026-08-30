@extends('admin.layout')

@section('title', 'Atributos personalizados')

@section('content')

<p class="form-hint" style="margin-bottom:20px;">
  Campos de filtro para categorías, sin tocar código. Sirven para dos casos: agregar un campo nuevo a un tipo que ya existe (ej. "panel mallado" a Gabinetes), o crear un tipo totalmente nuevo para una categoría que hoy no tiene ninguno (ver la tarjeta al final).
</p>

@foreach($allTypes as $typeKey => $typeLabel)
  @php
    $builtInFields = config("pc_builder.fields.$typeKey", []);
    $typeFields = $fields->get($typeKey, collect());
  @endphp
  <div class="admin-table-wrap" style="margin-bottom:24px;" x-data="{ editing: null, adding: false }">
    <div style="padding:16px 18px 0;">
      <h3 style="font-size:15px;margin-bottom:2px;">{{ $typeLabel }}</h3>
      @if(!empty($builtInFields))
        <p class="form-hint" style="margin-top:4px;">Campos ya incorporados: {{ implode(', ', array_column($builtInFields, 'label')) }}</p>
      @endif
    </div>

    @if($typeFields->isEmpty())
      <div class="admin-empty">Todavía no hay campos personalizados para este tipo.</div>
    @else
      <table class="admin-table">
        <thead>
          <tr>
            <th>Campo</th>
            <th>Tipo</th>
            <th>Filtro tienda</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($typeFields as $field)
            <tr>
              <td>
                <span x-show="editing !== {{ $field->id }}">{{ $field->label }} <span class="mono" style="color:var(--text-muted);">({{ $field->field_key }})</span></span>
                <form x-show="editing === {{ $field->id }}" x-cloak method="POST" action="{{ route('admin.attribute-fields.update', $field) }}"
                      x-data="{ options: {{ Js::from(collect($field->options ?? [])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()) }} }"
                      style="display:flex;flex-direction:column;gap:8px;padding:10px 0;">
                  @csrf @method('PUT')
                  <input type="text" name="label" value="{{ $field->label }}">
                  @if(in_array($field->field_type, ['select', 'checkboxes']))
                    <template x-for="(opt, i) in options" :key="i">
                      <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
                        <input type="text" x-model="opt.key" :name="'option_key[' + i + ']'" placeholder="Valor">
                        <input type="text" x-model="opt.label" :name="'option_label[' + i + ']'" placeholder="Texto a mostrar">
                        <button type="button" class="repeater-remove" x-on:click="options.splice(i, 1)">×</button>
                      </div>
                    </template>
                    <button type="button" class="btn btn-sm" x-on:click="options.push({key: '', label: ''})">+ Agregar opción</button>
                  @endif
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                    <input type="checkbox" name="shop_filter" value="1" {{ $field->shop_filter ? 'checked' : '' }}>
                    Usar como filtro en la tienda
                  </label>
                  <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    <button type="button" class="btn btn-sm" @click="editing = null">Cancelar</button>
                  </div>
                </form>
              </td>
              <td>{{ ['select' => 'Lista (una)', 'checkboxes' => 'Lista (varias)', 'number' => 'Número'][$field->field_type] ?? $field->field_type }}</td>
              <td>{{ $field->shop_filter ? 'Sí' : 'No' }}</td>
              <td>
                <div class="cell-actions">
                  <button type="button" class="btn btn-sm" x-show="editing !== {{ $field->id }}" @click="editing = {{ $field->id }}">Editar</button>
                  <form method="POST" action="{{ route('admin.attribute-fields.destroy', $field) }}" onsubmit="return confirm('¿Eliminar \'{{ $field->label }}\'? Los productos que ya lo tengan cargado lo conservan, pero no se va a mostrar ni poder editar más.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    <div style="padding:14px 18px;border-top:1px solid var(--border);" x-data="{ newFieldType: 'select', options: [{key: '', label: ''}] }">
      <button type="button" class="btn btn-sm" @click="adding = !adding" x-text="adding ? 'Cancelar' : '+ Agregar campo'"></button>
      <form x-show="adding" x-cloak method="POST" action="{{ route('admin.attribute-fields.store') }}" style="margin-top:12px;display:flex;flex-direction:column;gap:10px;max-width:480px;">
        @csrf
        <input type="hidden" name="type_key" value="{{ $typeKey }}">
        <input type="text" name="field_key" placeholder="Clave interna (ej: panel_mallado)" required pattern="[a-z][a-z0-9_]*">
        <input type="text" name="label" placeholder="Texto a mostrar (ej: ¿Tiene panel mallado tipo pecera?)" required>
        <select name="field_type" x-model="newFieldType">
          <option value="select">Lista de opciones (una sola)</option>
          <option value="checkboxes">Lista de opciones (varias a la vez)</option>
          <option value="number">Número</option>
        </select>
        <div x-show="newFieldType === 'select' || newFieldType === 'checkboxes'" x-cloak>
          <template x-for="(opt, i) in options" :key="i">
            <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
              <input type="text" x-model="opt.key" :name="'option_key[' + i + ']'" placeholder="Valor (ej: cableado)">
              <input type="text" x-model="opt.label" :name="'option_label[' + i + ']'" placeholder="Texto a mostrar (ej: Cableado)">
              <button type="button" class="repeater-remove" x-on:click="options.splice(i, 1)">×</button>
            </div>
          </template>
          <button type="button" class="btn btn-sm" x-on:click="options.push({key: '', label: ''})">+ Agregar opción</button>
        </div>
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
          <input type="checkbox" name="shop_filter" value="1">
          Usar como filtro en la tienda
        </label>
        <button type="submit" class="btn btn-sm btn-primary">Guardar campo</button>
      </form>
    </div>
  </div>
@endforeach

<div class="admin-table-wrap" style="margin-bottom:24px;" x-data="{ newFieldType: 'select', options: [{key: '', label: ''}] }">
  <div style="padding:16px 18px;">
    <h3 style="font-size:15px;margin-bottom:2px;">Crear un tipo de atributo nuevo</h3>
    <p class="form-hint" style="margin-top:4px;">Para una categoría que hoy no tiene ningún "Tipo de componente / atributos" — después de crearlo acá, andá a Categorías y asignáselo a la categoría que corresponda.</p>
  </div>
  <form method="POST" action="{{ route('admin.attribute-fields.store') }}" style="padding:14px 18px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:10px;max-width:480px;">
    @csrf
    <input type="text" name="type_key" placeholder="Clave interna del tipo (ej: mallas)" required pattern="[a-z][a-z0-9_]*">
    <input type="text" name="type_label" placeholder="Nombre a mostrar (ej: Mallas para pecera)" required>
    <input type="text" name="field_key" placeholder="Clave del primer campo (ej: material)" required pattern="[a-z][a-z0-9_]*">
    <input type="text" name="label" placeholder="Texto del campo (ej: Material)" required>
    <select name="field_type" x-model="newFieldType">
      <option value="select">Lista de opciones (una sola)</option>
      <option value="checkboxes">Lista de opciones (varias a la vez)</option>
      <option value="number">Número</option>
    </select>
    <div x-show="newFieldType === 'select' || newFieldType === 'checkboxes'" x-cloak>
      <template x-for="(opt, i) in options" :key="i">
        <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
          <input type="text" x-model="opt.key" :name="'option_key[' + i + ']'" placeholder="Valor">
          <input type="text" x-model="opt.label" :name="'option_label[' + i + ']'" placeholder="Texto a mostrar">
          <button type="button" class="repeater-remove" x-on:click="options.splice(i, 1)">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="options.push({key: '', label: ''})">+ Agregar opción</button>
    </div>
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
      <input type="checkbox" name="shop_filter" value="1">
      Usar como filtro en la tienda
    </label>
    <button type="submit" class="btn btn-sm btn-primary">Crear tipo y campo</button>
  </form>
</div>

@endsection
