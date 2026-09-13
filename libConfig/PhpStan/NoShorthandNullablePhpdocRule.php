<?php

declare(strict_types=1);

namespace LibConfig\PhpStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

use function preg_match;
use function sprintf;

/**
 * Nullable types are written `T|null`, never `?T`. ECS rewrites native
 * declarations (NullableTypeDeclarationFixer), but no php-cs-fixer rule
 * touches `?T` inside PHPDoc, so this rule closes that gap.
 *
 * @implements Rule<Stmt>
 */
final readonly class NoShorthandNullablePhpdocRule implements Rule
{
    // `?` that is not part of `??`/`?->`/prose ("why?") and starts a type name.
    private const string PATTERN = '/(?<![\w$?])\?(?=[\\\\A-Za-z])/';

    #[Override]
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        // PHPStan's virtual nodes (InClassMethodNode, InClassNode, ...) extend
        // Stmt and share the real node's doc comment; skip them or every
        // finding is reported twice.
        if ($node instanceof VirtualNode) {
            return [];
        }

        $doc = $node->getDocComment();
        if ($doc === null || preg_match(self::PATTERN, $doc->getText()) !== 1) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf('PHPDoc on line %d uses the shorthand nullable `?T`: write `T|null` instead.', $doc->getStartLine()),
            )
                ->identifier('laravelOrbStack.noShorthandNullablePhpdoc')
                ->line($doc->getStartLine())
                ->build(),
        ];
    }
}
