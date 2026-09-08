<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;
use function str_ends_with;

/**
 * Laravel's global helpers (`app()`, `config()`, `route()`, `now()`, ...) are
 * service locators in disguise. Inject the contract instead.
 *
 * Detected by origin rather than by a name list so new helpers are covered
 * automatically: every function declared in a `helpers.php` shipped by
 * laravel/framework is banned.
 *
 * @implements Rule<FuncCall>
 */
final readonly class NoGlobalHelperRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    #[Override]
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return [];
        }

        if (! $this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [];
        }

        $function = $this->reflectionProvider->getFunction($node->name, $scope);
        $file = $function->getFileName();

        if ($file === null || ! str_ends_with($file, '/helpers.php') || ! str_contains($file, '/laravel/framework/')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf('Global helper %s() is not allowed: inject the underlying contract instead.', $function->getName()),
            )
                ->identifier('laravelOrbStack.noGlobalHelper')
                ->build(),
        ];
    }
}
