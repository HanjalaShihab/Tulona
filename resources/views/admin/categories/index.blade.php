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
  <form method="POST" action="{{ route('admin.categories.bulk') }}" id="bulk-form">
    @csrf
    <div style="display:flex;gap:8px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:6px;font-weight:600;font-size:13px;cursor:pointer">
        <input type="checkbox" id="select-all" onclick="toggleAll(this)"> Select all
      </label>
      <span style="color:var(--ink-3);font-size:13px" id="selected-count">0 selected</span>
      <select name="action" id="bulk-action" class="input" style="flex:1;min-width:150px;max-width:220px">
        <option value="">— Bulk action —</option>
        <option value="delete">Delete selected</option>
      </select>
      <button class="btn btn-outline" type="submit">Apply</button>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:28px"></th>
            <th>Name</th>
            <th>Parent</th>
            <th>Products</th>
            <th>Active</th>
            <th style="width:140px"></th>
          </tr>
        </thead>
        @forelse($categories as $c)
          <tr>
            <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" class="row-check"></td>
            <td>
              <a href="{{ route('admin.categories.edit', $c) }}"><strong>{{ $c->name }}</strong></a>
              <small style="color:var(--ink-3)">/{{ $c->slug }}</small>
            </td>
            <td>{{ $c->parent?->name ?? '—' }}</td>
            <td>{{ $c->products_count }}</td>
            <td>{{ $c->is_active ? '✓' : '—' }}</td>
            <td style="text-align:right">
              <button type="submit" form="delete-form-{{ $c->id }}" class="btn btn-danger btn-sm" onclick="return confirm('Delete category &quot;{{ addslashes($c->name) }}&quot;?')">Delete</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="ad-table-empty">
                <b>No categories found</b>
                Add your first category to organize products.
              </div>
            </td>
          </tr>
        @endforelse
      </table>
    </div>
  </form>

  @foreach($categories as $c)
    <form id="delete-form-{{ $c->id }}" method="POST" action="{{ route('admin.categories.destroy', $c) }}" hidden>
      @csrf @method('DELETE')
    </form>
  @endforeach
</div>

<script>
function toggleAll(src) {
  document.querySelectorAll('.row-check').forEach(function (c) { c.checked = src.checked; });
  updateCount();
}
function updateCount() {
  document.getElementById('selected-count').textContent = document.querySelectorAll('.row-check:checked').length + ' selected';
  var all = document.querySelectorAll('.row-check').length;
  var checked = document.querySelectorAll('.row-check:checked').length;
  var selAll = document.getElementById('select-all');
  if (selAll) selAll.checked = all > 0 && checked === all;
}
document.querySelectorAll('.row-check').forEach(function (c) { c.addEventListener('change', updateCount); });

document.getElementById('bulk-form').addEventListener('submit', function (e) {
  var action = document.getElementById('bulk-action').value,
      n = document.querySelectorAll('.row-check:checked').length;
  if (!action) { e.preventDefault(); alert('Choose a bulk action.'); return; }
  if (!n) { e.preventDefault(); alert('Select at least one category.'); return; }
  if (action === 'delete' && !confirm('Delete ' + n + ' categor' + (n === 1 ? 'y' : 'ies') + '? Categories with products will be skipped.')) e.preventDefault();
});
</script>
@endsection
