@extends('admin.layout')
@section('admin-content')
<div style="min-height:100vh;display:grid;place-items:center;background:var(--bg)">
  <form method="POST" action="{{ route('admin.authenticate') }}" style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:32px;width:min(400px,92vw);box-shadow:var(--shadow-lg)">
    @csrf
    <h1 style="font-size:22px">Tulona <span style="color:var(--brand)">Admin</span></h1>
    <p style="color:var(--ink-2);font-size:13.5px;margin-bottom:18px">Authorized staff only.</p>
    @if($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <div class="field" style="margin-bottom:12px">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="field" style="margin-bottom:18px">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
  </form>
</div>
@endsection
