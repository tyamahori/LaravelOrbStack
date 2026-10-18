<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Override;
use PhpParser\Node;
use PhpParser\Node\Attribute as AttributeNode;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function preg_match;
use function sprintf;
use function str_starts_with;

/**
 * AGENTS.md: an Eloquent model is a Persistence/ tool for implementing a port,
 * and it must not change behaviour where the caller cannot see it. So a model
 * lives in Persistence/ and declares no model events, observers, global scopes,
 * accessors or mutators; the UseCase does that work with explicit calls.
 *
 * @implements Rule<InClassNode>
 */
final readonly class ExplicitEloquentModelRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $reflection = $node->getClassReflection();
        $className = $reflection->getName();
        if (! str_starts_with($className, 'LaravelOrbStack\\') || ! $reflection->is(Model::class)) {
            return [];
        }

        $errors = [];
        if (preg_match('#^LaravelOrbStack\\\\[^\\\\]+\\\\Persistence\\\\#', $className) !== 1) {
            $errors[] = RuleErrorBuilder::message(sprintf('%s: an Eloquent model belongs in Persistence/, where it implements a port.', $className))
                ->identifier('laravelOrbStack.explicitEloquentModel')
                ->build();
        }

        foreach (new NodeFinder()->findInstanceOf([$node->getOriginalNode()], Node::class) as $found) {
            $reason = $this->hiddenBehaviour($found);
            if ($reason === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf('%s: %s; do it with an explicit call from the UseCase instead.', $className, $reason))
                ->identifier('laravelOrbStack.explicitEloquentModel')
                ->line($found->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function hiddenBehaviour(Node $node): string|null
    {
        return match (true) {
            $node instanceof AttributeNode && in_array($node->name->toString(), [ObservedBy::class, ScopedBy::class], true) => sprintf('#[%s] hides behaviour from the caller', $node->name->toString()),
            $node instanceof PropertyItem && in_array($node->name->toString(), ['dispatchesEvents', 'observables'], true) => sprintf('$%s registers model events', $node->name->toString()),
            $node instanceof ClassMethod && in_array($node->name->toLowerString(), ['boot', 'booted', 'booting'], true) => sprintf('%s() is where model events and global scopes are registered', $node->name->toString()),
            $node instanceof ClassMethod && (
                preg_match('/^(?:get|set)\w+Attribute$/', $node->name->toString()) === 1
                || ($node->returnType instanceof Name && $node->returnType->toString() === Attribute::class)
            ) => sprintf('%s() is an accessor or mutator', $node->name->toString()),
            default => null,
        };
    }
}
