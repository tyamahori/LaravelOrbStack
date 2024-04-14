<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\Basic\NoTrailingCommaInSinglelineFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedTraitsFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentSpacingFixer;
use PhpCsFixer\Fixer\ControlStructure\ElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\IsNullFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLinesBeforeNamespaceFixer;
use PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderNameFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderReturnTypeFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\StringNotation\SimpleToComplexStringVariableFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer;
use PhpCsFixer\Fixer\Whitespace\CompactNullableTypeDeclarationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$dirname = dirname(__FILE__, 2);

return ECSConfig::configure()
    ->withPaths([
        $dirname . '/app',
        $dirname . '/bootstrap/app.php',
        $dirname . '/config',
        $dirname . '/packages',
        $dirname . '/public',
        $dirname . '/resources',
        $dirname . '/routes',
        $dirname . '/tests',
    ])
    ->withCache(
        $dirname . '/.tempCache/.ecs'
    )
    ->withPhpCsFixerSets(
        php80MigrationRisky: true,
        php81Migration: true,
        php82Migration: true,
        php83Migration: true,
        psr2: true,
        psr12Risky: true,
        phpCsFixerRisky: true,
    )
    ->withConfiguredRule(
        ArraySyntaxFixer::class,
        ['syntax' => 'short'],
    )
    ->withConfiguredRule(
        MethodArgumentSpaceFixer::class,
        ['keep_multiple_spaces_after_comma' => true],
    )
    ->withConfiguredRule(
        NoExtraBlankLinesFixer::class,
        ['tokens' => ['extra', 'use']]
    )
    ->withConfiguredRule(
        BlankLineBeforeStatementFixer::class,
        [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
            ],
        ]
    )
    ->withConfiguredRule(
        MultilineWhitespaceBeforeSemicolonsFixer::class,
        [
            'strategy' => 'no_multi_line',
        ]
    )
    ->withConfiguredRule(
        BinaryOperatorSpacesFixer::class,
        [
            'operators' => [
                '=>' => 'single_space',
                '=' => 'single_space',
            ],
        ]
    )
    ->withConfiguredRule(
        PhpdocAlignFixer::class,
        [
            'align' => 'left',
        ]
    )
    ->withConfiguredRule(
        GeneralPhpdocAnnotationRemoveFixer::class,
        [
            'annotations' => ['author'],
        ]
    )
    ->withConfiguredRule(
        GlobalNamespaceImportFixer::class,
        [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ]
    )
    ->withConfiguredRule(
        YodaStyleFixer::class,
        [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    )
    ->withConfiguredRule(
        PhpUnitTestAnnotationFixer::class,
        ['style' => 'annotation']
    )
    ->withConfiguredRule(
        BracesPositionFixer::class,
        [
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ]
    )
    ->withConfiguredRule(
        OrderedImportsFixer::class,
        [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ]
    )
    ->withConfiguredRule(
        ListSyntaxFixer::class,
        [
            'syntax' => 'long',
        ]
    )
    ->withConfiguredRule(
        FunctionDeclarationFixer::class,
        [
            'closure_function_spacing' => 'one',
            'closure_fn_spacing' => 'one',
        ]
    )
    ->withConfiguredRule(
        PhpUnitTestCaseStaticMethodCallsFixer::class,
        ['call_type' => 'self']
    )
    ->withConfiguredRule(
        PhpUnitStrictFixer::class,
        ['assertions' => []],
    )
    ->withRules([
        StrictParamFixer::class,
        LinebreakAfterOpeningTagFixer::class,
        NoUnusedImportsFixer::class,
        NoLeadingNamespaceWhitespaceFixer::class,
        AlignMultilineCommentFixer::class,
        NoEmptyCommentFixer::class,
        NoEmptyPhpdocFixer::class,
        NoEmptyStatementFixer::class,
        ArrayIndentationFixer::class,
        NoSuperfluousElseifFixer::class,
        NoMultilineWhitespaceAroundDoubleArrowFixer::class,
        NoTrailingCommaInSinglelineFixer::class,
        NoWhitespaceBeforeCommaInArrayFixer::class,
        ElseifFixer::class,
        CompactNullableTypeDeclarationFixer::class,
        FunctionDeclarationFixer::class,
        NoBlankLinesAfterClassOpeningFixer::class,
        NoBlankLinesAfterPhpdocFixer::class,
        TrailingCommaInMultilineFixer::class,
        NoExtraBlankLinesFixer::class,
        NoWhitespaceInBlankLineFixer::class,
        ReturnTypeDeclarationFixer::class,
        BlankLinesBeforeNamespaceFixer::class,
        SingleBlankLineAtEofFixer::class,
        SingleLineCommentSpacingFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
        PhpdocNoPackageFixer::class,
        FullyQualifiedStrictTypesFixer::class,
        VoidReturnFixer::class,
        NoBreakCommentFixer::class,
        IsNullFixer::class,
        StrictComparisonFixer::class,
        BlankLineBetweenImportGroupsFixer::class,
        OrderedTraitsFixer::class,
        SingleQuoteFixer::class,
        StaticLambdaFixer::class,
        SimpleToComplexStringVariableFixer::class,
        SingleTraitInsertPerStatementFixer::class,
        PhpUnitDataProviderNameFixer::class,
        PhpUnitDataProviderReturnTypeFixer::class,
        StatementIndentationFixer::class,
    ]);
