@extends('layouts.app')
@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Cookie Policy</h1>
    <p data-reveal data-delay="80">Minimal, privacy-friendly cookie usage.</p>
  </div>
</div>
<div class="container" style="max-width:820px;padding-top:32px;padding-bottom:56px">
<div class="article-body" data-reveal>
<p>Tulona keeps cookie usage minimal:</p>
<ul style="padding-left:20px;margin:12px 0;line-height:1.9">
<li><strong>Session cookie</strong> — required for CSRF protection and basic site function.</li>
<li><strong>"Remember me" cookie</strong> — only ever set for admin dashboard logins.</li>
<li><strong>Local storage</strong> — your compare list (up to 4 products) is stored locally in your browser; we never receive it.</li>
</ul>
<p>We do not run third-party advertising trackers by default.</p>
</div>
</div>
@endsection
