@if($paginator->hasPages())
<div style="display:flex;gap:8px;align-items:center;font-size:13px;flex-wrap:wrap;">
  @if($paginator->onFirstPage())
    <span class="btn btn-sm" style="opacity:.4;">← Anterior</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm">← Anterior</a>
  @endif

  @php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $window = 1;
    $pages = collect();
    for ($p = 1; $p <= $last; $p++) {
        if ($p === 1 || $p === $last || ($p >= $current - $window && $p <= $current + $window)) {
            $pages->push($p);
        } elseif ($pages->last() !== '…') {
            $pages->push('…');
        }
    }
  @endphp

  <div style="display:flex;gap:4px;">
    @foreach($pages as $page)
      @if($page === '…')
        <span class="pagination-ellipsis">…</span>
      @elseif($page === $current)
        <span class="btn btn-sm pagination-current">{{ $page }}</span>
      @else
        <a href="{{ $paginator->url($page) }}" class="btn btn-sm">{{ $page }}</a>
      @endif
    @endforeach
  </div>

  @if($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm">Siguiente →</a>
  @else
    <span class="btn btn-sm" style="opacity:.4;">Siguiente →</span>
  @endif
</div>
@endif
