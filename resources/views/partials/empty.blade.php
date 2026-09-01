@if(isset($icon) || isset($text))
<div class="empty" role="status" data-reveal style="animation:none">
  <div class="empty-icon">{{ $icon ?? '🔍' }}</div>
  <p>{{ $text }}</p>
</div>
@endif
