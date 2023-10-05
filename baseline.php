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
    'message' => '#^Only booleans are allowed in a ternary operator condition, mixed given\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/config/broadcasting.php',
];
$ignoreErrors[] = [
    'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, mixed given\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/config/cache.php',
];
$ignoreErrors[] = [
    'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, mixed given\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/config/database.php',
];
$ignoreErrors[] = [
    'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, mixed given\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/config/session.php',
];
$ignoreErrors[] = [
    'message' => '#^Undefined variable\\: \\$this$#',
    'count' => 1,
    'path' => __DIR__ . '/routes/console.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
