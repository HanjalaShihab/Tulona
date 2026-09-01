@extends('admin._shell')
@section('page-title')
Categories
@endsection

@section('page')
<div class="ad-head">
  <div class="ad-icon">&#9675;</div>
  <div class="ad-head-text">
    <h1 class="ad-title">Categories</h1>
    <div class="ad-meta">Organize products into browsable groups for comparison and discovery.</div>
  </div>
  <div class="ad-head-actions">
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">&#43; New category</a>
  </div>
</div>

<div class="ad-pane">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Parent</th>
          <th>Products</th>
          <th>Active</th>
          <th style="width:140px"></th>
        </tr>
      </thead>
      @foreach($categories as $c)
        <tr>
          <td>
            <a href="{{ route('admin.categories.edit', $c) }}"><strong>{{ $c->name }}</strong></a>
            <small style="color:var(--ink-3)">/{{ $c->slug }}</small>
          </td>
          <td>{{ $c->parent?->name ?? '&#8212;' }}</td>
          <td>{{ $c->products_count }}</td>
          <td>{{ $c->is_active ? '&#10003;' : '&#8212;' }}</td>
          <td style="text-align:right">
            <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete category?')">
              @csrf @method('DELETE')
              <button class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </table>
  </div>
</div>
@endsection
