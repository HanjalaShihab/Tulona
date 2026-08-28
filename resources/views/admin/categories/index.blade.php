@extends('admin._shell')
@section('page-title')
Categories
@endsection
@section('page')
<table class="data-table">
  <thead><tr><th>Name</th><th>Parent</th><th>Products</th><th>Active</th><th style="width:140px"></th></tr></thead>
  @foreach($categories as $c)
    <tr>
      <td><a href="{{ route('admin.categories.edit', $c) }}"><strong>{{ $c->name }}</strong></a> <small style="color:var(--ink-3)">/{{ $c->slug }}</small></td>
      <td>{{ $c->parent?->name ?? '—' }}</td>
      <td>{{ $c->products_count }}</td>
      <td>{{ $c->is_active ? '✅' : '—' }}</td>
      <td style="text-align:right"><form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form></td>
    </tr>
  @endforeach
</table>
<p style="margin-top:14px"><a class="btn btn-primary" href="{{ route('admin.categories.create') }}">＋ New category</a></p>
@endsection
