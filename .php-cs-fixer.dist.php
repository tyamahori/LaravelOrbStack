<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->exclude([
        __DIR__ . '/bootstrap',
    ])
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/packages',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'strict_param' => true,
        'linebreak_after_opening_tag' => true,
        'no_leading_namespace_whitespace' => true,
        'no_unused_imports' => true,
        'align_multiline_comment' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'no_superfluous_elseif' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_whitespace_before_comma_in_array' => true,
        'elseif' => true,
        'compact_nullable_typehint' => true,
        'function_typehint_space' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_break_comment' => false,
        'method_argument_space' => [
            'ensure_fully_multiline' => true,
            'keep_multiple_spaces_after_comma' => false,
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'use'],
        ],
        'no_whitespace_in_blank_line' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
            ],
        ],
        'return_type_declaration' => true,
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        '@PhpCsFixer:risky' => true,
        'no_blank_lines_before_namespace' => false,
        'single_blank_line_before_namespace' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'single_space',
                '=' => 'single_space',
            ],
        ],
        'single_line_comment_spacing' => true,
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_no_package' => false,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author'],
        ],
        'fully_qualified_strict_types' => true,
        'void_return' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'is_null' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'curly_braces_position' => [
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'php_unit_test_annotation' => [
            'style' => 'annotation',
        ],
        'php_unit_strict' => [],
        'strict_comparison' => false,
        'no_trailing_comma_in_singleline' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ],
        'blank_line_between_import_groups' => false,
        'ordered_traits' => false,
        'list_syntax' => [
            'syntax' => 'long',
        ],
        'single_quote' => true,
        'static_lambda' => true,
        'simple_to_complex_string_variable' => true,
        'function_declaration' => [
            'closure_function_spacing' => 'one',
            'closure_fn_spacing' => 'one',
        ],
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'single_trait_insert_per_statement' => true,
        'php_unit_data_provider_name' => false,
    ])
    ->setFinder($finder);
