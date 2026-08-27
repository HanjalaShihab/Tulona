@extends('admin._shell')
@section('page-title')
Users & Roles
@endsection
@section('page')
<form method="POST" action="{{ route('admin.users.store') }}" class="pane form-grid" style="max-width:800px;margin-bottom:22px">
  @csrf
  <div class="field"><label>Name *</label><input type="text" name="name" required></div>
  <div class="field"><label>Email *</label><input type="email" name="email" required></div>
  <div class="field"><label>Password *</label><input type="password" name="password" minlength="8" required></div>
  <div class="field"><label>Role</label><select name="role">@foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach</select></div>
  <div style="grid-column:1/-1"><button class="btn btn-primary btn-sm">＋ Create user</button></div>
</form>

<table class="data-table" style="max-width:960px">
  <thead><tr><th>User</th><th>Role</th><th>Active</th><th style="width:110px"></th></tr></thead>
  @foreach($users as $u)
    <tr>
      <form method="POST" action="{{ route('admin.users.update', $u) }}">
      @csrf @method('PUT')
      <td>{{ $u->name }}<br><small style="color:var(--ink-3)">{{ $u->email }}</small></td>
      <td><select name="role" @cannot('manage-users') disabled @endcannot>@foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}" {{ $u->role===$r ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $r)) }}</option>@endforeach</select></td>
      <td><input type="checkbox" name="is_active" value="1" {{ $u->is_active?'checked':'' }}></td>
      <td style="text-align:right"><button class="btn btn-outline btn-sm">Save</button></td>
      </form>
    </tr>
  @endforeach
</table>
<p style="margin-top:14px;color:var(--ink-3);font-size:13px">Product Managers manage products but not settings; Content Managers manage articles; Analysts are read-only (§57).</p>
@endsection
