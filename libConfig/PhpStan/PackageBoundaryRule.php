<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function preg_match;
use function sprintf;

/**
 * AGENTS.md: a package reaches another package only through that package's
 * Domain/ (its ports and value objects). Its own Provider/ may name the other
 * side's implementation to bind it. Common/ is shared by every package, so it
 * must not depend on any feature package at all, not even to bind a port.
 * Deptrac cannot express this: its layers span all packages, so "same
 * package" is not something a layer can see.
 *
 * The importing package and layer come from the file path, so non-class files
 * such as Http/Web/routes.php are covered too.
 *
 * @implements Rule<FileNode>
 */
final readonly class PackageBoundaryRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (preg_match('#/packages/([^/]+)/([^/]+)/#', $scope->getFile(), $from) !== 1) {
            return [];
        }

        $errors = [];
        foreach (new NodeFinder()->findInstanceOf($node->getNodes(), Name::class) as $name) {
            if (preg_match('#^LaravelOrbStack\\\\([^\\\\]+)\\\\([^\\\\]+)\\\\#', $name->toString(), $to) !== 1 || $to[1] === $from[1]) {
                continue;
            }

            $message = match (true) {
                $from[1] === 'Common' => 'Common/ is shared by every package and must not depend on feature package %s: %s.',
                $from[2] === 'Provider', $to[2] === 'Domain' => null,
                default => 'Package %s may be reached only through its Domain/ (Provider/ may bind implementations): %s.',
            };
            if ($message === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf($message, $to[1], $name->toString()))
                ->identifier('laravelOrbStack.packageBoundary')
                ->line($name->getStartLine())
                ->build();
        }

        return $errors;
    }
}
