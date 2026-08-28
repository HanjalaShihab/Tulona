@extends('admin._shell')
@section('page-title')
Landing pages
@endsection

@section('page')
<div class="toolbar">
  <div class="spacer"></div>
  <a class="btn btn-primary" href="{{ route('admin.landing-pages.create') }}">＋ New landing page</a>
</div>

<div class="table-wrap">
<table class="data-table">
  <thead><tr><th>Title</th><th>Slug</th><th>Products</th><th>Comparisons</th><th>Status</th><th style="width:150px"></th></tr></thead>
  @forelse($pages as $p)
    <tr>
      <td><a href="{{ route('admin.landing-pages.edit', $p) }}"><strong>{{ $p->title }}</strong></a></td>
      <td><small style="color:var(--ink-3)">/landing/{{ $p->slug }}</small></td>
      <td>{{ $p->products_count }}</td>
      <td>{{ $p->comparisons_count }}</td>
      <td><span class="status-pill status-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
      <td style="text-align:right">
        <a class="btn btn-outline btn-sm" href="{{ route('admin.landing-pages.edit', $p) }}">Edit</a>
        @if($p->status === 'published')
          <a class="btn btn-outline btn-sm" href="{{ route('landing-pages.show', $p->slug) }}" target="_blank" rel="noopener">View</a>
        @endif
        <form method="POST" action="{{ route('admin.landing-pages.destroy', $p) }}" style="display:inline"
              onsubmit="return confirm('Delete this landing page?')">@csrf @method('DELETE')
          <button class="btn btn-danger btn-sm">Delete</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="6">No landing pages yet.</td></tr>
  @endforelse
</table>
</div>
{{ $pages->links('partials.pagination') }}
@endsection
