@extends('admin.layout')
@section('admin-content')
<div style="min-height:100vh;display:grid;grid-template-columns:1fr 1fr;background:linear-gradient(135deg,#060d18 0%,#0b1626 50%,#111827 100%);position:relative;overflow:hidden">

  <div style="position:absolute;top:-100px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.2),transparent 70%);filter:blur(60px);pointer-events:none"></div>
  <div style="position:absolute;bottom:-80px;right:-80px;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.18),transparent 70%);filter:blur(60px);pointer-events:none"></div>

  <div style="display:flex;flex-direction:column;justify-content:center;padding:60px 64px;position:relative;z-index:1">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:32px">
      <span style="display:grid;place-items:center;width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#10b981,#8b5cf6);color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.4rem;box-shadow:0 4px 14px rgba(139,92,246,.3)">T</span>
      <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.6rem;color:#fff">Tulona</span>
    </div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:2.4rem;color:#fff;line-height:1.2;margin:0 0 16px">The comparison<br>platform for<br>modern commerce.</h1>
    <p style="color:rgba(255,255,255,.55);font-size:15px;line-height:1.7;max-width:400px;margin:0">Track prices across merchants, publish comparison content, and earn through affiliate performance &#8212; all from a single dashboard.</p>
  </div>

  <div style="display:flex;align-items:center;justify-content:center;padding:60px;position:relative;z-index:1">
    <form method="POST" action="{{ route('admin.authenticate') }}" style="width:min(400px,100%);background:rgba(255,255,255,.97);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:40px;box-shadow:0 32px 64px -16px rgba(0,0,0,.4)">
      @csrf

      <div style="margin-bottom:28px">
        <h2 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.25rem;color:#0a0f1e;margin:0 0 6px">Sign in to Admin</h2>
        <p style="color:#7c86a0;font-size:13.5px;margin:0">Authorized staff only.</p>
      </div>

      @if($errors->any())
        <div class="alert alert-err">{{ $errors->first() }}</div>
      @endif

      <div class="field" style="margin-bottom:16px">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
      </div>

      <div class="field" style="margin-bottom:28px">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required placeholder="Enter your password">
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit" style="font-size:1rem;padding:14px">Sign in &#8594;</button>
    </form>
  </div>

</div>
@endsection
