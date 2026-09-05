@php
  $selParentId = (int) ($selCategory?->parent_id ?? 0);
  $selChildId = (int) ($selCategory?->id ?? 0);
  $tree = $categoryTree;
  $childOptions = $selParentId ? collect($tree['byParentId'][$selParentId] ?? []) : collect();
@endphp

<div class="field" style="grid-column:1/-1">
  <label>Search categories</label>
  <input type="text" id="cat-search" list="cat-search-list" placeholder="Type to search... e.g. Mouse, Keyboard, Camera, Books" autocomplete="off" style="width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:8px;font-size:13px">
  <datalist id="cat-search-list">
    @foreach($tree['parents'] as $parent)
      <option value="{{ $parent->name }}"></option>
      @foreach($tree['byParentId'][$parent->id] ?? [] as $child)
        <option value="{{ $child->name }}"></option>
        <option value="{{ $parent->name }} → {{ $child->name }}"></option>
      @endforeach
    @endforeach
  </datalist>
  <small style="color:var(--ink-3)">Type to filter — selecting a suggestion auto-fills the dropdowns below.</small>
</div>
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
  <small style="color:var(--ink-3)">Pick a category — its subcategories appear beside it. Or type above to search 644 categories.</small>
</div>

<script>
(function () {
  var parent = document.getElementById('cat-parent'),
      child  = document.getElementById('cat-child'),
      search = document.getElementById('cat-search'),
      tree   = @json($tree['byParentId']),
      byId   = @json($tree['byId']),
      parents = @json($tree['parents']);

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

  // Searchable filter + auto-select
  if (search) {
    var allCats = [];
    parents.forEach(function(p){ allCats.push({id:p.id,name:p.name,parent_id:null}); });
    Object.keys(tree).forEach(function(pid){
      (tree[pid]||[]).forEach(function(c){ allCats.push({id:c.id,name:c.name,parent_id:parseInt(pid)}); });
    });
    function findByName(name){
      var n = name.toLowerCase().trim();
      // exact match first
      for(var i=0;i<allCats.length;i++){ if(allCats[i].name.toLowerCase()===n) return allCats[i]; }
      // "Parent → Child" format
      if(n.includes('→')){
        var parts=n.split('→').map(s=>s.trim());
        var childName=parts[parts.length-1];
        for(var i=0;i<allCats.length;i++){ if(allCats[i].name.toLowerCase()===childName) return allCats[i]; }
      }
      // contains match (first)
      for(var i=0;i<allCats.length;i++){ if(allCats[i].name.toLowerCase().includes(n) || n.includes(allCats[i].name.toLowerCase())) return allCats[i]; }
      return null;
    }
    search.addEventListener('input', function(){
      var q = search.value.toLowerCase().trim();
      // Filter parent dropdown - show parent if its name or any child matches
      for(var i=0;i<parent.options.length;i++){
        var opt=parent.options[i];
        if(!opt.value){ continue; }
        var pname=opt.textContent.toLowerCase();
        var show = !q;
        if(q){
          if(pname.includes(q)) show=true;
          else {
            var kids=tree[opt.value]||[];
            for(var k=0;k<kids.length;k++){ if(kids[k].name.toLowerCase().includes(q)){ show=true; break; } }
          }
        }
        opt.hidden = !show;
        opt.style.display = show ? '' : 'none';
      }
    });
    // Auto-select only on exact match (when user picks from datalist or types full name)
    search.addEventListener('change', function(){
      var hit=findByName(search.value);
      if(hit){
        if(hit.parent_id){
          parent.value=hit.parent_id;
          repopulate();
          child.value=hit.id;
        } else {
          parent.value=hit.id;
          repopulate();
          child.value='';
        }
      }
    });
  }
})();
</script>