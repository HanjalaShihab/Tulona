@extends('layouts.app')
@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Affiliate Disclosure</h1>
    <p data-reveal data-delay="80">How Tulona earns money — transparently, without ever affecting our rankings.</p>
  </div>
</div>
<div class="container" style="max-width:820px;padding-top:32px;padding-bottom:56px">
<div class="article-body" data-reveal>
<p>Tulona participates in affiliate programs operated by merchants and affiliate networks. This means:</p>
<ul style="padding-left:20px;margin:12px 0;line-height:1.9">
<li>When you click links labeled "Buy", "View Deal" or similar on this site, we may earn a commission if you complete a purchase on the merchant's website.</li>
<li>The price you pay is exactly the same as buying there directly — affiliate commissions come out of the merchant's marketing budget.</li>
<li>We never accept payment for fake reviews, fake ratings, fake scarcity or hidden sponsorships. Paid placements, if ever introduced, will be clearly labeled.</li>
<li>Rankings and "best deal" labels are computed from verified price data, not commission size.</li>
</ul>
<p>Questions? See <a href="{{ url('/contact') }}">Contact</a>.</p>
</div>
</div>
@endsection
