@if(isset($icon) || isset($text))
<div class="empty" role="status">
  <div class="ico">{{ $icon ?? '🔍' }}</div>
  <p>{{ $text }}</p>
</div>
@endif
