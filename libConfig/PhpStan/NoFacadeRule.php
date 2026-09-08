<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Illuminate\Support\Facades\Facade;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_starts_with;

/**
 * Laravel facades hide the dependency they resolve. Inject the underlying
 * contract (or take it from `$this->app` in a provider) instead.
 *
 * @implements Rule<StaticCall>
 */
final readonly class NoFacadeRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    #[Override]
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);

        // Real-time facades (`Facades\App\...`) are generated at runtime, so
        // there is no class to reflect on.
        if (! str_starts_with($className, 'Facades\\')) {
            if (! $this->reflectionProvider->hasClass($className)) {
                return [];
            }

            $class = $this->reflectionProvider->getClass($className);

            if ($class->getName() === Facade::class || ! $class->is(Facade::class)) {
                return [];
            }

            $className = $class->getName();
        }

        return [
            RuleErrorBuilder::message(
                sprintf('Facade %s is not allowed: inject the underlying contract instead.', $className),
            )
                ->identifier('laravelOrbStack.noFacade')
                ->build(),
        ];
    }
}
