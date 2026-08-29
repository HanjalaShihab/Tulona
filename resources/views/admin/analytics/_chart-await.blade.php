<div class="ana-chart-await">
    <div class="ana-skeleton" aria-hidden="true">
        <i style="height:40%"></i><i style="height:70%"></i><i style="height:55%"></i><i style="height:85%"></i>
        <i style="height:60%"></i><i style="height:95%"></i><i style="height:75%"></i><i style="height:50%"></i>
        <i style="height:80%"></i><i style="height:65%"></i><i style="height:90%"></i>
    </div>
    @include('admin.analytics._empty', [
        'compact' => true,
        'title' => $title ?? 'Awaiting tracking',
        'body' => $body ?? 'This metric will render here automatically once the backend records it.',
    ])
</div>