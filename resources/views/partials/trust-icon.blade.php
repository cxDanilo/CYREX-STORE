@switch($icon ?? null)
  @case('shield')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @break
  @case('truck')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M3 7h11v9H3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <path d="M14 10h4l3 3v3h-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <circle cx="7" cy="18" r="1.6" stroke="currentColor" stroke-width="1.5"/>
      <circle cx="17.5" cy="18" r="1.6" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    @break
  @case('support')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4 13v-1a8 8 0 0 1 16 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <rect x="3" y="13" width="4" height="5" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
      <rect x="17" y="13" width="4" height="5" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    @break
  @default
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M5 12l4 4 10-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endswitch
