@extends('layouts.app')
@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>About Tulona</h1>
    <p data-reveal data-delay="80">Smart shopping research and product comparison for Bangladesh — built on honest data.</p>
    <div class="hero-meta" data-reveal data-delay="160"><span>Not a shop</span><span>Transparent rankings</span><span>No fake data</span></div>
  </div>
</div>
<div class="container" style="max-width:820px;padding-top:32px;padding-bottom:56px">
  <div class="guide-card" style="padding:36px" data-reveal>
    <p>Tulona is a <strong>smart shopping research and product comparison platform</strong> for Bangladesh (expanding to India and beyond). We help you discover products, compare prices across trusted stores, read genuinely useful guides, and then buy from the store you choose.</p>
    <p style="margin-top:16px"><strong>We are not a shop.</strong> We don't sell products, process payments, or handle shipping. When you click "Buy from …" you go directly to the merchant's own website. The final price, availability, warranty and delivery are always determined by that store.</p>

    <div style="height:2px;background:linear-gradient(90deg,var(--brand),var(--accent));border-radius:2px;margin:28px 0;opacity:.3"></div>

    <h2 style="font-size:1.3rem;margin-bottom:8px">How we make money</h2>
    <p>We may earn an affiliate commission when you purchase through links on Tulona. This costs you nothing extra and never influences our rankings — see our <a href="{{ url('/affiliate-disclosure') }}">Affiliate Disclosure</a>.</p>

    <div style="height:2px;background:linear-gradient(90deg,var(--brand),var(--accent));border-radius:2px;margin:28px 0;opacity:.3"></div>

    <h2 style="font-size:1.3rem;margin-bottom:14px">Our principles</h2>
    <div class="trust-grid" style="grid-template-columns:1fr 1fr">
      <div class="trust-item" style="padding:16px"><span class="ico">🚫</span><div><strong>No fabrication</strong><small>No fake prices, reviews or discounts — ever.</small></div></div>
      <div class="trust-item" style="padding:16px"><span class="ico">📊</span><div><strong>Transparent rankings</strong><small>Based on data, not commissions.</small></div></div>
      <div class="trust-item" style="padding:16px"><span class="ico">⚖️</span><div><strong>Disclosed relationships</strong><small>Affiliate links disclosed on every page.</small></div></div>
      <div class="trust-item" style="padding:16px"><span class="ico">↻</span><div><strong>Fresh data</strong><small>Stale data flagged as "may be outdated".</small></div></div>
    </div>

    <div style="margin-top:32px;text-align:center">
      <a class="btn btn-primary btn-lg" href="{{ route('products.index') }}">Browse the catalog →</a>
    </div>
  </div>
</div>
@endsection
