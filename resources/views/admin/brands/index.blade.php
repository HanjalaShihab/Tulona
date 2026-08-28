@extends('admin._shell')
@section('page-title')
Brands
@endsection
@section('page')
<table class="data-table">
  <thead><tr><th>Name</th><th>Products</th><th>Website</th><th style="width:130px"></th></tr></thead>
  @forelse($brands as $b)
    <tr>
      <td><a href="{{ route('admin.brands.edit', $b) }}"><strong>{{ $b->name }}</strong></a></td>
      <td>{{ $b->products_count }}</td>
      <td>{{ $b->website_url ? str_replace(['https://','www.'],'',$b->website_url) : '—' }}</td>
      <td style="text-align:right"><form method="POST" action="{{ route('admin.brands.destroy', $b) }}" onsubmit="return confirm('Delete brand?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form></td>
    </tr>
  @empty
    <tr><td colspan="4">No brands yet.</td></tr>
  @endforelse
</table>
{{ $brands->links('partials.pagination') }}
<p style="margin-top:14px"><a class="btn btn-primary" href="{{ route('admin.brands.create') }}">＋ New brand</a></p>
@endsection
