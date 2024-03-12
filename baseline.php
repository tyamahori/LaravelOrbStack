<?php
declare(strict_types=1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Undefined variable\\: \\$this$#',
    'count' => 1,
    'path' => __DIR__ . '/routes/console.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
