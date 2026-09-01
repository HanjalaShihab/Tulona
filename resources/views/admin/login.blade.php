@extends('admin.layout')
@section('admin-content')
<div style="min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#060d18 0%,#0b1626 50%,#111827 100%);position:relative;overflow:hidden">
  <div style="position:absolute;top:-100px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.2),transparent 70%);filter:blur(60px);pointer-events:none"></div>
  <div style="position:absolute;bottom:-80px;right:-80px;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.18),transparent 70%);filter:blur(60px);pointer-events:none"></div>

  <form method="POST" action="{{ route('admin.authenticate') }}" style="position:relative;background:rgba(255,255,255,.97);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:40px;width:min(420px,92vw);box-shadow:0 32px 64px -16px rgba(0,0,0,.4)">
    @csrf
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
      <span style="display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#10b981,#8b5cf6);color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.2rem;box-shadow:0 4px 14px rgba(139,92,246,.3)">T</span>
      <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.4rem;color:#0a0f1e">Tulona <span style="color:#10b981">Admin</span></span>
    </div>
    <p style="color:#7c86a0;font-size:13.5px;margin-bottom:24px">Authorized staff only. Please sign in to continue.</p>
    @if($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <div class="field" style="margin-bottom:14px">
      <label for="email">Email address</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
    </div>
    <div class="field" style="margin-bottom:24px">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required placeholder="Enter your password">
    </div>
    <button class="btn btn-primary btn-block btn-lg" type="submit" style="font-size:1rem;padding:14px">Sign in →</button>
  </form>
</div>
@endsection
