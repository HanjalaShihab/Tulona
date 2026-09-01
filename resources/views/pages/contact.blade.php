@extends('layouts.app')
@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Contact Us</h1>
    <p data-reveal data-delay="80">Questions, corrections, or partnership inquiries — we'd love to hear from you.</p>
    <div class="hero-meta" data-reveal data-delay="160"><span>Editorial team</span><span>Merchant updates</span><span>Corrections</span></div>
  </div>
</div>
<div class="container" style="max-width:760px;padding-top:32px;padding-bottom:56px">
  <div class="guide-card" style="padding:36px;text-align:center" data-reveal>
    <span style="font-size:3rem;display:block;margin-bottom:12px">&#9993;</span>
    <h2 style="font-size:1.5rem;margin-bottom:12px">Get in touch</h2>
    <p style="color:var(--ink-2);font-size:1.05rem">Reach the editorial team at:<br><strong style="font-size:1.2rem;color:var(--brand)">hello@tulona.example</strong></p>
    <p style="color:var(--ink-3);margin-top:20px">(replace with your production email in production)</p>
    <div style="height:2px;background:linear-gradient(90deg,var(--brand),var(--accent));border-radius:2px;margin:28px 0;opacity:.3"></div>
    <p style="color:var(--ink-2)">If you represent a merchant listed on Tulona and want to update product or pricing information, include your store name and official contact so we can verify quickly.</p>
  </div>
</div>
@endsection
