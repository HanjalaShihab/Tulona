@extends('layouts.app')
@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Privacy Policy</h1>
    <p data-reveal data-delay="80">Designed to be used anonymously — no account required for any feature.</p>
  </div>
</div>
<div class="container" style="max-width:820px;padding-top:32px;padding-bottom:56px">
<div class="article-body" data-reveal>
<p>Tulona is designed to be used anonymously — no account is required to use any feature of the site.</p>
<h2>What we collect</h2>
<ul style="padding-left:20px;margin:12px 0;line-height:1.9">
<li><strong>Outbound click analytics:</strong> when you click a store link we record which offer was clicked, when, the internal page it came from, a coarse device family (mobile/desktop) and a salted one-way hash of your IP address. Raw IPs are never stored; hashes cannot be reversed to identify you.</li>
<li><strong>Basic server logs</strong> maintained for security and abuse prevention.</li>
</ul>
<h2>What we don't do</h2>
<ul style="padding-left:20px;margin:12px 0;line-height:1.9">
<li>No registration, no profiles, no behavioral advertising cookies by default.</li>
<li>We never sell personal data — because we don't collect meaningful personal data.</li>
</ul>
<h2>Third parties</h2>
<p>Clicking through to a merchant takes you to their website, which has its own privacy policy that then applies.</p>
</div>
</div>
@endsection
