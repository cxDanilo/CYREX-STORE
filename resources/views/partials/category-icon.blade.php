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
  @case('i-monitor')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="4" width="18" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
      <path d="M9 20h6M12 16v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @case('i-plug')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M9 3v4M15 3v4M7 7h10v4a5 5 0 0 1-5 5 5 5 0 0 1-5-5V7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M12 16v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @case('i-shield-bolt')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <path d="M13 8.5l-3 4.3h2.2l-1.1 3.7 3-4.3h-2.2l1.1-3.7Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/>
    </svg>
    @break
  @case('i-tools')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.47 2.47-2.3-.7-.7-2.3 2.47-2.47Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @break
  @case('i-tag')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M11.5 3H6a2 2 0 0 0-2 2v5.5a2 2 0 0 0 .586 1.414l8.5 8.5a2 2 0 0 0 2.828 0l5.5-5.5a2 2 0 0 0 0-2.828l-8.5-8.5A2 2 0 0 0 11.5 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <circle cx="8" cy="8" r="1.3" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    @break
  @default
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/>
    </svg>
@endswitch
@endif
