@extends('admin.layout')

@section('title', 'Compatibilidad (Arma tu PC)')

@section('content')

<p class="form-hint" style="margin-bottom:20px;">
  Estas son las listas de valores que se usan en los datos de compatibilidad de productos (Procesador, Placa madre, etc.) y en el asistente público "Arma tu PC". Agregá acá un socket, tipo de RAM o form factor nuevo apenas salga al mercado — no hace falta tocar código.
</p>

@foreach($groups as $groupKey => $groupLabel)
  @php $groupOptions = $options->get($groupKey, collect()); @endphp
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
              <td class="mono" style="color:var(--text-secondary);">{{ $option->value }}</td>
              <td>
                <span x-show="editing !== {{ $option->id }}">{{ $option->label }}</span>
                <form x-show="editing === {{ $option->id }}" x-cloak method="POST" action="{{ route('admin.pc-builder-options.update', $option) }}" style="display:flex;gap:8px;">
                  @csrf @method('PUT')
                  <input type="text" name="label" value="{{ $option->label }}" style="flex:1;">
                  <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                  <button type="button" class="btn btn-sm" @click="editing = null">Cancelar</button>
                </form>
              </td>
              <td>
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

@endsection
