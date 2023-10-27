<?php
declare(strict_types=1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Throwing checked exception BadMethodCallException in closure\\!$#',
    'count' => 1,
    'path' => __DIR__ . '/app/Providers/RouteServiceProvider.php',
];
$ignoreErrors[] = [
    'message' => '#^Throwing checked exception RuntimeException in closure\\!$#',
    'count' => 2,
    'path' => __DIR__ . '/app/Providers/RouteServiceProvider.php',
];
$ignoreErrors[] = [
    'message' => '#^Undefined variable\\: \\$this$#',
    'count' => 1,
    'path' => __DIR__ . '/routes/console.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
