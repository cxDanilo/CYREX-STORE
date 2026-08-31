@extends('admin.layout')

@section('title', 'Compatibilidad y atributos')

@section('content')

<div x-data="{ tab: localStorage.getItem('cyrexAdminAttrTab') || 'compat' }" x-init="$watch('tab', v => localStorage.setItem('cyrexAdminAttrTab', v))">

  <div class="admin-tabs">
    <button type="button" class="admin-tab" :class="{ active: tab === 'compat' }" @click="tab = 'compat'">Compatibilidad</button>
    <button type="button" class="admin-tab" :class="{ active: tab === 'custom' }" @click="tab = 'custom'">Atributos personalizados</button>
  </div>

  {{-- ===================== COMPATIBILIDAD ===================== --}}
  <div x-show="tab === 'compat'" x-cloak>
    <p class="form-hint" style="margin:16px 0 20px;">
      Listas de valores para los campos que ya existen (Procesador, Placa madre, Auriculares, etc.). Agrega aquí un socket, tipo de RAM o valor nuevo apenas salga al mercado — no hace falta tocar código ni crear un campo nuevo.
    </p>

    @foreach($compatGroups as $groupKey => $groupLabel)
      @php $groupOptions = $compatOptions->get($groupKey, collect()); @endphp
      <div class="admin-table-wrap" style="margin-bottom:24px;" x-data="{ editing: null }">
        <div style="padding:16px 18px 0;">
          <h3 style="font-size:15px;margin-bottom:2px;">{{ $groupLabel }}</h3>
        </div>

        @if($groupOptions->isEmpty())
          <div class="admin-empty">Todavía no hay opciones en este grupo.</div>
        @else
          <table class="admin-table">
            <thead>
              <tr>
                <th>Valor guardado</th>
                <th>Texto que ve el admin</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($groupOptions as $option)
                <tr>
                  <td class="mono" style="color:var(--text-secondary);" data-label="Valor guardado">{{ $option->value }}</td>
                  <td class="admin-table-title">
                    <span x-show="editing !== {{ $option->id }}">{{ $option->label }}</span>
                    <form x-show="editing === {{ $option->id }}" x-cloak method="POST" action="{{ route('admin.pc-builder-options.update', $option) }}" style="display:flex;gap:8px;">
                      @csrf @method('PUT')
                      <input type="text" name="label" value="{{ $option->label }}" style="flex:1;">
                      <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                      <button type="button" class="btn btn-sm" @click="editing = null">Cancelar</button>
                    </form>
                  </td>
                  <td class="cell-actions">
                    <div class="cell-actions">
                      <button type="button" class="btn btn-sm" x-show="editing !== {{ $option->id }}" @click="editing = {{ $option->id }}">Editar</button>
                      <form method="POST" action="{{ route('admin.pc-builder-options.destroy', $option) }}" onsubmit="return confirm('¿Eliminar \'{{ $option->label }}\'? Los productos que ya la tengan cargada la conservan, pero no va a aparecer más como opción.');">
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

        <form method="POST" action="{{ route('admin.pc-builder-options.store') }}" style="display:flex;gap:10px;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;">
          @csrf
          <input type="hidden" name="group" value="{{ $groupKey }}">
          <input type="text" name="value" placeholder="Valor (ej. LGA1851)" required style="flex:1;min-width:140px;">
          <input type="text" name="label" placeholder="Texto a mostrar (ej. LGA1851 — Core Ultra)" required style="flex:2;min-width:220px;">
          <button type="submit" class="btn btn-sm btn-primary">+ Agregar</button>
        </form>
      </div>
    @endforeach
  </div>

  {{-- ===================== ATRIBUTOS PERSONALIZADOS ===================== --}}
  <div x-show="tab === 'custom'" x-cloak>
    <p class="form-hint" style="margin:16px 0 20px;">
      Campos nuevos para categorías, sin tocar código. Sirven para dos casos: agregar un campo a un tipo que ya existe (ej. "panel mallado" a Gabinetes), o crear un tipo totalmente nuevo para una categoría que hoy no tiene ninguno (tarjeta al final de todo). Si un campo ya incorporado dice que sus opciones se editan en la pestaña Compatibilidad, es porque ya existe — no hace falta (ni se puede) crearlo de nuevo aquí, alcanza con marcarlo como filtro más abajo. Si marcas más de uno para el mismo tipo, la tienda solo usa el primero.
    </p>

    @foreach($allTypes as $typeKey => $typeLabel)
      @php
        $builtInFields = config("pc_builder.fields.$typeKey", []);
        $filterableBuiltIns = collect($builtInFields)->filter(fn ($bf) => ($bf['type'] ?? null) === 'select');
        $otherBuiltInsLabel = collect($builtInFields)
            ->reject(fn ($bf) => ($bf['type'] ?? null) === 'select')
            ->map(fn ($bf) => $bf['label'])
            ->implode(', ');
        $typeFields = $fields->get($typeKey, collect());
      @endphp
      <div class="admin-table-wrap" style="margin-bottom:24px;" x-data="{ editing: null, adding: false }">
        <div style="padding:16px 18px 0;">
          <h3 style="font-size:15px;margin-bottom:2px;">{{ $typeLabel }}</h3>

          @if($filterableBuiltIns->isNotEmpty())
            <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
              @foreach($filterableBuiltIns as $fieldKey => $bf)
                @php
                  $dynamic = is_string($bf['options'] ?? null) && str_starts_with($bf['options'], 'dynamic:');
                  $isFilter = $resolvedFields[$typeKey][$fieldKey]['shop_filter'] ?? false;
                @endphp
                <form method="POST" action="{{ route('admin.attribute-fields.toggle-builtin') }}" style="display:flex;align-items:center;gap:8px;">
                  @csrf @method('PUT')
                  <input type="hidden" name="type_key" value="{{ $typeKey }}">
                  <input type="hidden" name="field_key" value="{{ $fieldKey }}">
                  <input type="hidden" name="enabled" value="0">
                  <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:var(--text-secondary);">
                    <input type="checkbox" name="enabled" value="1" {{ $isFilter ? 'checked' : '' }} onchange="this.form.requestSubmit()" style="flex-shrink:0;margin-top:3px;">
                    <span>
                      {{ $bf['label'] }}
                      @if($dynamic) <span class="mono" style="color:var(--text-muted);">(opciones en Compatibilidad)</span> @endif
                      — usar como filtro en la tienda
                    </span>
                  </label>
                </form>
              @endforeach
            </div>
          @endif

          @if($otherBuiltInsLabel)
            <p class="form-hint" style="margin-top:8px;">Otros campos incorporados (no aplican como filtro de tienda): {{ $otherBuiltInsLabel }}</p>
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
                  <td class="admin-table-title">
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
                  <td data-label="Tipo">{{ ['select' => 'Lista (una)', 'checkboxes' => 'Lista (varias)', 'number' => 'Número'][$field->field_type] ?? $field->field_type }}</td>
                  <td data-label="Filtro tienda">{{ $field->shop_filter ? 'Sí' : 'No' }}</td>
                  <td class="cell-actions">
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
          <form x-show="adding" x-cloak method="POST" action="{{ route('admin.attribute-fields.store') }}" style="margin-top:14px;display:flex;flex-direction:column;gap:12px;max-width:480px;">
            @csrf
            <input type="hidden" name="type_key" value="{{ $typeKey }}">
            <div class="form-group" style="margin:0;">
              <label>Texto del campo</label>
              <input type="text" name="label" placeholder="Ej: ¿Tiene panel mallado tipo pecera?" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label>Clave interna</label>
              <input type="text" name="field_key" placeholder="Ej: panel_mallado" required pattern="[a-z][a-z0-9_]*">
              <div class="form-hint">Solo minúsculas, sin espacios ni tildes — es interna, no se ve en el sitio.</div>
            </div>
            <div class="form-group" style="margin:0;">
              <label>Tipo de campo</label>
              <select name="field_type" x-model="newFieldType">
                <option value="select">Lista de opciones (una sola)</option>
                <option value="checkboxes">Lista de opciones (varias a la vez)</option>
                <option value="number">Número</option>
              </select>
            </div>
            <div x-show="newFieldType === 'select' || newFieldType === 'checkboxes'" x-cloak style="display:flex;flex-direction:column;gap:8px;">
              <label style="font-size:12.5px;color:var(--text-secondary);">Opciones para elegir</label>
              <template x-for="(opt, i) in options" :key="i">
                <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
                  <input type="text" x-model="opt.key" :name="'option_key[' + i + ']'" placeholder="Valor (ej: si)">
                  <input type="text" x-model="opt.label" :name="'option_label[' + i + ']'" placeholder="Texto a mostrar (ej: Sí)">
                  <button type="button" class="repeater-remove" x-on:click="options.splice(i, 1)">×</button>
                </div>
              </template>
              <button type="button" class="btn btn-sm" x-on:click="options.push({key: '', label: ''})" style="align-self:flex-start;">+ Agregar opción</button>
            </div>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
              <input type="checkbox" name="shop_filter" value="1">
              Usar como filtro en la tienda
            </label>
            <button type="submit" class="btn btn-sm btn-primary" style="align-self:flex-start;">Guardar campo</button>
          </form>
        </div>
      </div>
    @endforeach

    <div class="admin-table-wrap" style="margin-bottom:24px;" x-data="{ newFieldType: 'select', options: [{key: '', label: ''}] }">
      <div style="padding:16px 18px;">
        <h3 style="font-size:15px;margin-bottom:2px;">Crear un tipo de atributo nuevo</h3>
        <p class="form-hint" style="margin-top:4px;">Usa esto solo si tienes una categoría que hoy no tiene ningún "Tipo de componente / atributos" para elegir (ej. Mallas para pecera). Si el tipo ya existe en alguna tarjeta de arriba, usa el botón "+ Agregar campo" de esa tarjeta en vez de esto.</p>
      </div>
      <form method="POST" action="{{ route('admin.attribute-fields.store') }}" style="padding:14px 18px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:16px;max-width:480px;">
        @csrf

        <div style="display:flex;flex-direction:column;gap:10px;">
          <p style="margin:0;font-size:12.5px;font-weight:600;color:var(--text-secondary);">1. Nombre del tipo nuevo</p>
          <div class="form-group" style="margin:0;">
            <label>Nombre a mostrar</label>
            <input type="text" name="type_label" placeholder="Ej: Mallas para pecera" required>
            <div class="form-hint">Así va a aparecer en el desplegable "Tipo de componente / atributos" de Categorías.</div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Clave interna</label>
            <input type="text" name="type_key" placeholder="Ej: mallas" required pattern="[a-z][a-z0-9_]*">
            <div class="form-hint">Solo minúsculas, sin espacios ni tildes — es interna, no se ve en el sitio.</div>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;border-top:1px solid var(--border);padding-top:16px;">
          <p style="margin:0;font-size:12.5px;font-weight:600;color:var(--text-secondary);">2. Primer campo de este tipo</p>
          <div class="form-group" style="margin:0;">
            <label>Texto del campo</label>
            <input type="text" name="label" placeholder="Ej: Material" required>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Clave interna del campo</label>
            <input type="text" name="field_key" placeholder="Ej: material" required pattern="[a-z][a-z0-9_]*">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Tipo de campo</label>
            <select name="field_type" x-model="newFieldType">
              <option value="select">Lista de opciones (una sola)</option>
              <option value="checkboxes">Lista de opciones (varias a la vez)</option>
              <option value="number">Número</option>
            </select>
          </div>
          <div x-show="newFieldType === 'select' || newFieldType === 'checkboxes'" x-cloak style="display:flex;flex-direction:column;gap:8px;">
            <label style="font-size:12.5px;color:var(--text-secondary);">Opciones para elegir</label>
            <template x-for="(opt, i) in options" :key="i">
              <div class="repeater-row" style="grid-template-columns:1fr 1fr auto;">
                <input type="text" x-model="opt.key" :name="'option_key[' + i + ']'" placeholder="Valor (ej: acero)">
                <input type="text" x-model="opt.label" :name="'option_label[' + i + ']'" placeholder="Texto a mostrar (ej: Acero)">
                <button type="button" class="repeater-remove" x-on:click="options.splice(i, 1)">×</button>
              </div>
            </template>
            <button type="button" class="btn btn-sm" x-on:click="options.push({key: '', label: ''})" style="align-self:flex-start;">+ Agregar opción</button>
          </div>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
            <input type="checkbox" name="shop_filter" value="1">
            Usar como filtro en la tienda
          </label>
        </div>

        <button type="submit" class="btn btn-sm btn-primary" style="align-self:flex-start;">Crear tipo y campo</button>
      </form>
    </div>
  </div>

</div>

@endsection
