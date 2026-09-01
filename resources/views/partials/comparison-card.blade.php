<article class="card">
  <div class="card-body">
    <span class="card-brand">&#9878; Comparison</span>
    <a class="card-name" href="{{ route('comparisons.show', $comparison->slug) }}">{{ $comparison->title }}</a>
    @if($comparison->introduction)<p style="color:var(--ink-2);font-size:13px;margin:6px 0">{{ \Illuminate\Support\Str::limit(strip_tags($comparison->introduction), 110) }}</p>@endif
    <p style="color:var(--ink-3);font-size:13px;margin:6px 0">{{ $comparison->products_count ?? 0 }} products compared{{ $comparison->verdict ? ' · verdict included' : '' }}</p>
    <a class="btn btn-primary btn-sm" style="margin-top:8px" href="{{ route('comparisons.show', $comparison->slug) }}">View comparison</a>
  </div>
</article>
