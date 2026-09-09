<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\NoAliasLanguageConstructCallFixer;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\ArrayNotation\WhitespaceAfterCommaInArrayFixer;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\Basic\NoMultipleStatementsPerLineFixer;
use PhpCsFixer\Fixer\Basic\NoTrailingCommaInSinglelineFixer;
use PhpCsFixer\Fixer\Casing\NativeTypeDeclarationCasingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedTraitsFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfStaticAccessorFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentSpacingFixer;
use PhpCsFixer\Fixer\ControlStructure\ElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededBracesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\ControlStructure\SimplifiedIfReturnFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchContinueToBreakFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\LambdaNotUsedImportFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NullableTypeDeclarationForDefaultNullValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnneededImportAliasFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveIssetsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ExplicitIndirectVariableFixer;
use PhpCsFixer\Fixer\LanguageConstruct\IsNullFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLinesBeforeNamespaceFixer;
use PhpCsFixer\Fixer\NamespaceNotation\CleanNamespaceFixer;
use PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer;
use PhpCsFixer\Fixer\Operator\AssignNullCoalescingToCoalesceEqualFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\NoUselessConcatOperatorFixer;
use PhpCsFixer\Fixer\Operator\NoUselessNullsafeOperatorFixer;
use PhpCsFixer\Fixer\Operator\StandardizeIncrementFixer;
use PhpCsFixer\Fixer\Operator\TernaryToNullCoalescingFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocArrayTypeFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocListTypeFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoUselessInheritdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocParamOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocReturnSelfReferenceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSingleLineVarSpacingFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarWithoutNameFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitAttributesFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderNameFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderReturnTypeFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitFqcnAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitSetUpTearDownVisibilityFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\ReturnNotation\SimplifiedNullReturnFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\StringNotation\SimpleToComplexStringVariableFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer;
use PhpCsFixer\Fixer\Whitespace\CompactNullableTypeDeclarationFixer;
use PhpCsFixer\Fixer\Whitespace\HeredocIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\TypesSpacesFixer;
use Symplify\CodingStandard\Fixer\Spacing\SpaceAfterCommaHereNowDocFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$dirname = dirname(__FILE__, 2);

return ECSConfig::configure()
    ->withPaths([
        "{$dirname}/app",
        "{$dirname}/bootstrap/app.php",
        "{$dirname}/bootstrap/autoload.php",
        "{$dirname}/config",
        "{$dirname}/database",
        "{$dirname}/packages",
        "{$dirname}/public",
        "{$dirname}/resources",
        "{$dirname}/routes",
        "{$dirname}/tests",
    ])
    ->withCache(
        "{$dirname}/.tempCache/.ecs",
    )
    ->withPhpCsFixerSets(
        psr2: true,
        psr12Risky: true,
        phpCsFixerRisky: true,
        php85Migration: true,
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
        OrderedTypesFixer::class,
        ['null_adjustment' => 'always_last'],
    )
    ->withConfiguredRule(
        PhpdocTypesOrderFixer::class,
        ['null_adjustment' => 'always_last'],
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
        BlankLineAfterOpeningTagFixer::class,
        SpaceAfterCommaHereNowDocFixer::class,
        WhitespaceAfterCommaInArrayFixer::class,
        FinalClassFixer::class,
        ProtectedToPrivateFixer::class,
        SelfStaticAccessorFixer::class,
        OrderedClassElementsFixer::class,
        ClassAttributesSeparationFixer::class,
        NoNullPropertyInitializationFixer::class,
        SingleClassElementPerStatementFixer::class,
        ClassDefinitionFixer::class,
        NoUselessElseFixer::class,
        SimplifiedIfReturnFixer::class,
        NoUnneededControlParenthesesFixer::class,
        NoUnneededBracesFixer::class,
        SwitchContinueToBreakFixer::class,
        NoUselessReturnFixer::class,
        SimplifiedNullReturnFixer::class,
        NullableTypeDeclarationForDefaultNullValueFixer::class,
        UseArrowFunctionsFixer::class,
        LambdaNotUsedImportFixer::class,
        TypesSpacesFixer::class,
        HeredocIndentationFixer::class,
        MethodChainingIndentationFixer::class,
        PhpdocScalarFixer::class,
        PhpdocTypesFixer::class,
        PhpdocOrderFixer::class,
        PhpdocSeparationFixer::class,
        PhpdocTrimFixer::class,
        PhpdocIndentFixer::class,
        PhpdocArrayTypeFixer::class,
        PhpdocListTypeFixer::class,
        PhpdocVarWithoutNameFixer::class,
        PhpdocReturnSelfReferenceFixer::class,
        PhpdocNoUselessInheritdocFixer::class,
        PhpdocTrimConsecutiveBlankLineSeparationFixer::class,
        PhpdocSingleLineVarSpacingFixer::class,
        PhpdocParamOrderFixer::class,
        PhpdocLineSpanFixer::class,
        AssignNullCoalescingToCoalesceEqualFixer::class,
        TernaryToNullCoalescingFixer::class,
        NoUselessConcatOperatorFixer::class,
        NoUselessNullsafeOperatorFixer::class,
        StandardizeIncrementFixer::class,
        // TernaryToElvisOperatorFixer deliberately absent: it emits `?:`, which
        // relies on loose truthiness and Mago's no-shorthand-ternary flags.
        ExplicitStringVariableFixer::class,
        ExplicitIndirectVariableFixer::class,
        CombineConsecutiveIssetsFixer::class,
        CombineConsecutiveUnsetsFixer::class,
        NoAliasLanguageConstructCallFixer::class,
        PhpUnitAttributesFixer::class,
        PhpUnitMethodCasingFixer::class,
        PhpUnitSetUpTearDownVisibilityFixer::class,
        PhpUnitFqcnAnnotationFixer::class,
        NativeTypeDeclarationCasingFixer::class,
        CleanNamespaceFixer::class,
        NoUnneededImportAliasFixer::class,
        NoMultipleStatementsPerLineFixer::class,
        MultilineCommentOpeningClosingFixer::class,
    ])
    // modify parallel run
    ->withParallel(timeoutSeconds: 120, maxNumberOfProcess: 32, jobSize: 20);
