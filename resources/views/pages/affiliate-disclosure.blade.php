@extends('layouts.app')
@section('content')
<div class="container" style="max-width:820px;padding-top:24px;padding-bottom:44px">
<h1>Affiliate Disclosure</h1>
<div class="article-body" style="margin-top:16px">
<p>Tulona participates in affiliate programs operated by merchants and affiliate networks. This means:</p>
<ul>
<li>When you click links labeled "Buy", "View Deal" or similar on this site, we may earn a commission if you complete a purchase on the merchant's website.</li>
<li>The price you pay is exactly the same as buying there directly — affiliate commissions come out of the merchant's marketing budget.</li>
<li>We never accept payment for fake reviews, fake ratings, fake scarcity or hidden sponsorships. Paid placements, if ever introduced, will be clearly labeled.</li>
<li>Rankings and "best deal" labels are computed from verified price data, not commission size.</li>
</ul>
<p>Questions? See <a href="{{ url('/contact') }}">Contact</a>.</p>
</div>
</div>
@endsection
