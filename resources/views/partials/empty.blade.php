@if(isset($icon) || isset($text))
<div class="empty" role="status" data-reveal style="animation:none">
  @if(isset($icon))<span class="empty-ico" aria-hidden="true">{!! $icon !!}</span>@endif
  <p>{{ $text }}</p>
</div>
@endif
