@php
  $selParentId = (int) ($selCategory?->parent_id ?? 0);
  $selChildId = (int) ($selCategory?->id ?? 0);
  $tree = $categoryTree;
  $childIdsOf = function ($pid) use ($tree) { return array_keys($tree['byParentId'][$pid] ?? []); };
  $childOptions = $selParentId ? collect($childIdsOf($selParentId))->map(fn ($cid) => $tree['byId'][$cid]) : collect();
@endphp

<div class="field" style="grid-column:1/-1">
  <label>Category *</label>
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <select name="category_id" id="cat-parent" data-cat-parent style="flex:1;min-width:220px">
      <option value="">— Select a category —</option>
      @foreach($tree['parents'] as $parent)
        <option value="{{ $parent->id }}" @selected($parent->id === $selParentId)>{{ $parent->name }}</option>
      @endforeach
    </select>
    <select name="subcategory_id" id="cat-child" data-cat-child style="flex:1;min-width:220px" data-selected="{{ $selChildId ?: '' }}">
      <option value="">Subcategory (optional)</option>
      @foreach($childOptions as $childOpt)
        <option value="{{ $childOpt->id }}" @selected($childOpt->id === $selChildId)>{{ $childOpt->name }}</option>
      @endforeach
    </select>
  </div>
  <small style="color:var(--ink-3)">Pick a category — its subcategories appear beside it. Subcategory is optional; posted into the category if left blank.</small>
</div>

<script>
(function () {
  var parent = document.getElementById('cat-parent'),
      child  = document.getElementById('cat-child'),
      tree   = @json($tree['byParentId']);

  function repopulate() {
    var sel = child.value;
    child.innerHTML = '<option value="">Subcategory (optional)</option>';
    var children = tree[parent.value] || [];
    for (var i = 0; i < children.length; i++) {
      var o = document.createElement('option');
      o.value = children[i].id;
      o.textContent = children[i].name;
      child.appendChild(o);
    }
    child.value = sel;
  }

  parent.addEventListener('change', repopulate);
  repopulate();
})();
</script>