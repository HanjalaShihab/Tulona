<nav class="pagination" role="navigation">
  @if($paginator->onFirstPage())
    <span aria-disabled="true">‹ Prev</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}">‹ Prev</a>
  @endif
  @foreach($elements as $element)
    @if(is_string($element))<span>{{ $element }}</span>@endif
    @if(is_array($element))
      @foreach($element as $page => $url)
        @if($page === $paginator->currentPage())
          <span class="onpage" aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach
    @endif
  @endforeach
  @if($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}">Next ›</a>
  @else
    <span aria-disabled="true">Next ›</span>
  @endif
</nav>
