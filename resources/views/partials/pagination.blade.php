@if($paginator->hasPages())
<div style="display:flex;gap:8px;align-items:center;font-size:13px;">
  @if($paginator->onFirstPage())
    <span class="btn btn-sm" style="opacity:.4;">← Anterior</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm">← Anterior</a>
  @endif

  <span style="color:var(--text-muted);padding:0 6px;">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

  @if($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm">Siguiente →</a>
  @else
    <span class="btn btn-sm" style="opacity:.4;">Siguiente →</span>
  @endif
</div>
@endif
