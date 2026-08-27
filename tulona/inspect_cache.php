<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = Illuminate\Support\Facades\Cache::get('home.sections');
if (!is_array($data)) {
    echo "Cache miss or invalid: " . gettype($data) . "\n";
    exit(1);
}
$cat = $data['categories'] ?? null;
echo 'categories class: ' . get_class($cat) . ' count=' . $cat->count() . "\n";
foreach ($cat->take(7) as $i => $c) {
    echo "  [$i] " . get_class($c) . ' slug=' . $c->slug . "\n";
}
echo 'drops first: ' . get_class($data['drops']->first()) . "\n";
echo 'guides first: ' . get_class($data['guides']->first()) . "\n";
echo "Inspection OK\n";
