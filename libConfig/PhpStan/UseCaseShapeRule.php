<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function preg_match;
use function sprintf;

/**
 * AGENTS.md: a class in UseCase/ is one operation, and `__invoke` is its only
 * public method. A second public method is a second operation and belongs in
 * its own UseCase; a port interface belongs in Domain/.
 *
 * @implements Rule<InClassNode>
 */
final readonly class UseCaseShapeRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $className = $node->getClassReflection()
            ->getName();
        if (preg_match('#^LaravelOrbStack\\\\[^\\\\]+\\\\UseCase\\\\#', $className) !== 1) {
            return [];
        }

        $class = $node->getOriginalNode();
        if (! $class instanceof Class_) {
            return [
                RuleErrorBuilder::message(sprintf('%s: UseCase/ holds only use-case classes; put interfaces in Domain/.', $className))
                    ->identifier('laravelOrbStack.useCaseShape')
                    ->build(),
            ];
        }

        $errors = [];
        $hasInvoke = false;
        foreach ($class->getMethods() as $method) {
            $methodName = $method->name->toLowerString();
            $hasInvoke = $hasInvoke || $methodName === '__invoke';
            if ($method->isPublic() && ! in_array($methodName, ['__construct', '__invoke'], true)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf('%s::%s(): a UseCase exposes only __invoke; split this operation into its own UseCase.', $className, $method->name->toString()),
                )
                    ->identifier('laravelOrbStack.useCaseShape')
                    ->line($method->getStartLine())
                    ->build();
            }
        }

        if (! $hasInvoke) {
            $errors[] = RuleErrorBuilder::message(sprintf('%s: a UseCase must define __invoke.', $className))
                ->identifier('laravelOrbStack.useCaseShape')
                ->build();
        }

        return $errors;
    }
}
