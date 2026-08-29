@extends('admin._shell')
@section('page-title')
Articles & Guides
@endsection
@section('page')
<table class="data-table">
  <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Published</th><th></th></tr></thead>
  @forelse($articles as $a)
    <tr>
      <td><a href="{{ route('admin.articles.edit', $a) }}"><strong>{{ $a->title }}</strong></a></td>
      <td>{{ ucfirst($a->type) }}</td>
      <td><span class="status-pill status-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
      <td>{{ $a->published_at?->format('M j, Y') ?? '—' }}</td>
      <td style="text-align:right"><form method="POST" action="{{ route('admin.articles.destroy', $a) }}" onsubmit="return confirm('Delete article?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form></td>
    </tr>
  @empty
    <tr><td colspan="5">No articles yet.</td></tr>
  @endforelse
</table>
{{ $articles->links('partials.pagination') }}
<p style="margin-top:14px">
  <a class="btn btn-primary" href="{{ route('admin.articles.create', ['type' => 'guide']) }}">＋ New guide</a>
  <a class="btn btn-outline" href="{{ route('admin.articles.create', ['type' => 'review']) }}">＋ New review</a>
</p>
@endsection
