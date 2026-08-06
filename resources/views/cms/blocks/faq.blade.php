<div class="wrap cms-block cms-faq" x-data="{ open: null }">
  @foreach($data['items'] ?? [] as $i => $item)
    <div class="cms-faq-item" :class="open === {{ $i }} && 'is-open'">
      <button type="button" class="cms-faq-question" x-on:click="open = (open === {{ $i }} ? null : {{ $i }})">
        <span>{{ $item['pregunta'] ?? '' }}</span>
        <svg width="10" height="6" viewBox="0 0 10 6" fill="none" :style="open === {{ $i }} && 'transform:rotate(180deg)'" style="transition:transform .2s;flex-shrink:0;">
          <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
        </svg>
      </button>
      <div class="cms-faq-answer" x-show="open === {{ $i }}" x-collapse.duration.200ms x-cloak>{{ $item['respuesta'] ?? '' }}</div>
    </div>
  @endforeach
</div>
