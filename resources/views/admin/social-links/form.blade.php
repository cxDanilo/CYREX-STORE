@extends('admin.layout')

@section('title', $socialLink->exists ? 'Editar red social' : 'Nueva red social')

@section('content')

<form method="POST" action="{{ $socialLink->exists ? route('admin.redes.update', $socialLink) : route('admin.redes.store') }}" class="admin-form">
  @csrf
  @if($socialLink->exists) @method('PUT') @endif

  <div class="form-section">
    <div class="form-group">
      <label for="platform">Plataforma</label>
      <select id="platform" name="platform" required>
        @foreach($platforms as $platform)
          <option value="{{ $platform }}" {{ old('platform', $socialLink->platform) === $platform ? 'selected' : '' }} style="text-transform:capitalize;">{{ $platform }}</option>
        @endforeach
      </select>
      @error('platform') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="url">URL del perfil</label>
      <input type="text" id="url" name="url" value="{{ old('url', $socialLink->url) }}" placeholder="https://instagram.com/cyrexstore" required>
      @error('url') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="sort_order">Orden</label>
      <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $socialLink->sort_order ?? 0) }}" required>
      @error('sort_order') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-actions">
    <a href="{{ route('admin.redes.index') }}" class="btn">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </div>
</form>

@endsection
