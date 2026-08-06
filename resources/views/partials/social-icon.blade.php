@switch($platform ?? null)
  @case('instagram')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.5"/>
      <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
      <circle cx="17.2" cy="6.8" r="1" fill="currentColor"/>
    </svg>
    @break
  @case('facebook')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M14 9h2.5V6H14c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.2l.8-3H14V9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
    </svg>
    @break
  @case('tiktok')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M14 4v9.5a3 3 0 1 1-2-2.83" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M14 4c.3 2 1.8 3.5 4 3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @case('youtube')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="6" width="18" height="12" rx="3" stroke="currentColor" stroke-width="1.5"/>
      <path d="M10.5 9.5l4 2.5-4 2.5v-5z" fill="currentColor"/>
    </svg>
    @break
  @case('x')
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @break
  @default
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
    </svg>
@endswitch
