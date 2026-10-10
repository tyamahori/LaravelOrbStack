<?php

declare(strict_types=1);

/*
 * The AGENTS.md placement rules that the file tree alone decides; PHPStan and
 * Deptrac check what the code references. Silent with exit 0 when the tree
 * conforms; otherwise one line per violation on stderr and exit 1.
 */

$root = dirname(__DIR__);
$errors = [];

foreach (['app', 'routes', 'resources', 'packages/Shared', 'bootstrap/providers.php'] as $path) {
    if (! file_exists("{$root}/{$path}")) {
        continue;
    }

    $errors[] = "{$path}: must not exist; code lives in packages/<Feature>/, project-wide setup in packages/Common/";
}

// The fixed per-package vocabulary. Http/ splits into Web/ and Api/ with shared
// FormRequests directly under it; Blade lives only in Http/Web/View/; Test/
// holds *Test.php and Fake*.php.
$allowed = [
    '#^[^/]+/(?:Domain|UseCase|Console|Persistence|Provider)/.+\.php$#',
    '#^[^/]+/Http/(?:(?:Web|Api)/)?[^/]+\.php$#',
    '#^[^/]+/Http/Web/View/.+\.blade\.php$#',
    '#^[^/]+/Test/(?:.+/)?(?:\w+Test|Fake\w+)\.php$#',
];
$bootstrap = (string) file_get_contents("{$root}/bootstrap/app.php");
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/packages", FilesystemIterator::SKIP_DOTS));

/** @var SplFileInfo $file */
foreach ($files as $file) {
    $path = substr($file->getPathname(), strlen("{$root}/packages/"));
    if (str_starts_with($file->getFilename(), '.')) {
        continue;
    }

    if (array_filter($allowed, static fn (string $pattern): bool => preg_match($pattern, $path) === 1) === []) {
        $errors[] = "packages/{$path}: outside the package vocabulary (Domain, UseCase, Http/{Web,Api}, Console, Persistence, Provider, Test)";

        continue;
    }

    // Wiring is listed by hand in bootstrap/app.php; a file missing from it is silently dead.
    if (preg_match('#^([^/]+)/Provider/(\w+)\.php$#', $path, $provider) === 1
        && ! str_contains($bootstrap, "LaravelOrbStack\\{$provider[1]}\\Provider\\{$provider[2]}")) {
        $errors[] = "packages/{$path}: not registered in bootstrap/app.php withProviders";
    }

    if (preg_match('#^[^/]+/Http/(Web|Api)/routes\.php$#', $path, $routes) === 1
        && preg_match('#' . strtolower($routes[1]) . ':\s*\[[^\]]*/packages/' . preg_quote($path, '#') . "'#", $bootstrap) !== 1) {
        $errors[] = "packages/{$path}: not listed in bootstrap/app.php withRouting(" . strtolower($routes[1]) . ': [...])';
    }
}

// .agents/skills/ holds the skills; .claude/skills/ only links to them, and AGENTS.md names each one.
$agents = (string) file_get_contents("{$root}/AGENTS.md");
$skills = scandir("{$root}/.agents/skills");
$links = scandir("{$root}/.claude/skills");
foreach (array_diff($skills === false ? [] : $skills, ['.', '..']) as $name) {
    $link = "{$root}/.claude/skills/{$name}";
    if (! is_link($link) || readlink($link) !== "../../.agents/skills/{$name}") {
        $errors[] = ".claude/skills/{$name}: must be a symlink to ../../.agents/skills/{$name}";
    }

    if (! str_contains($agents, "`{$name}`")) {
        $errors[] = ".agents/skills/{$name}: not listed in AGENTS.md";
    }
}

foreach (array_diff($links === false ? [] : $links, ['.', '..']) as $name) {
    if (is_dir("{$root}/.agents/skills/{$name}")) {
        continue;
    }

    $errors[] = ".claude/skills/{$name}: no matching .agents/skills/ directory";
}

foreach ($errors as $error) {
    fwrite(STDERR, $error . PHP_EOL);
}

exit($errors === [] ? 0 : 1);
