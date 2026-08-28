@extends('layouts.app')
@section('content')
<div class="container" style="max-width:820px;padding-top:24px;padding-bottom:44px">
<h1>About Tulona</h1>
<div class="article-body" style="margin-top:16px">
<p>Tulona is a <strong>smart shopping research and product comparison platform</strong> for Bangladesh (expanding to India and beyond). We help you discover products, compare prices across trusted stores, read genuinely useful guides, and then buy from the store you choose.</p>
<p><strong>We are not a shop.</strong> We don't sell products, process payments, or handle shipping. When you click "Buy from …" you go directly to the merchant's own website. The final price, availability, warranty and delivery are always determined by that store.</p>
<h2>How we make money</h2>
<p>We may earn an affiliate commission when you purchase through links on Tulona. This costs you nothing extra and never influences our rankings — see our <a href="{{ url('/affiliate-disclosure') }}">Affiliate Disclosure</a>.</p>
<h2>Our principles</h2>
<ul>
<li>No fabricated prices, reviews or discounts — ever.</li>
<li>Transparent rankings based on data, not commissions.</li>
<li>Affiliate relationships disclosed on every page.</li>
<li>Fresh data marked clearly; stale data flagged as "may be outdated".</li>
</ul>
</div>
</div>
@endsection
