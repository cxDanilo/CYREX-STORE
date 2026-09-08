@php
  $ufId = $id ?? $name;
  $ufAccept = $accept ?? 'image/png,image/jpeg,image/webp';
  $ufLabel = $label ?? ($currentUrl ?? null ? 'Reemplazar' : 'Subir imagen');
  $ufRemoveName = $removeName ?? null;
@endphp
<div class="file-upload" data-file-upload data-remove-target="{{ $ufRemoveName ? '#'.$ufRemoveName.'_cb' : '' }}">
  <div class="file-upload-thumb" data-file-upload-thumb>
    @if($currentUrl ?? null)
      <img src="{{ $currentUrl }}" alt="">
    @else
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 16l4.5-6 3.5 4.5 2.5-3L20 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>
    @endif
  </div>
  <div class="file-upload-info">
    <div class="file-upload-name" data-file-upload-name>{{ $currentUrl ?? null ? ($currentLabel ?? 'Archivo actual') : 'Ningún archivo elegido' }}</div>
    <div class="file-upload-meta">{{ $hint ?? '' }}</div>
  </div>
  <div class="file-upload-actions">
    <button type="button" class="btn btn-sm" data-file-upload-trigger>{{ $ufLabel }}</button>
    @if(($currentUrl ?? null) && $ufRemoveName)
      <button type="button" class="btn btn-sm btn-ghost" data-file-upload-remove>Quitar</button>
      <input type="checkbox" name="{{ $ufRemoveName }}" value="1" id="{{ $ufRemoveName }}_cb" class="sr-only-file" data-file-upload-remove-input>
    @endif
  </div>
  <input type="file" id="{{ $ufId }}" name="{{ $name }}" accept="{{ $ufAccept }}" class="sr-only-file" data-file-upload-input>
</div>
