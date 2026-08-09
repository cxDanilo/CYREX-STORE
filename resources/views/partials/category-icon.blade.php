@if(!empty($iconImage ?? null))
  <img src="{{ $iconImage }}" alt="" style="width:100%;height:100%;object-fit:contain;">
@else
@switch($icon ?? null)
  @case('i-cpu')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="6" y="6" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
      <rect x="9.5" y="9.5" width="5" height="5" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
      <path d="M9 3v2.2M12 3v2.2M15 3v2.2M9 18.8V21M12 18.8V21M15 18.8V21M3 9h2.2M3 12h2.2M3 15h2.2M18.8 9H21M18.8 12H21M18.8 15H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @case('i-mouse')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="7" y="3" width="10" height="18" rx="5" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 3v7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @case('i-chair')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M7 4v8a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M8 14v6M16 14v6M6 20h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @default
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/>
    </svg>
@endswitch
@endif
