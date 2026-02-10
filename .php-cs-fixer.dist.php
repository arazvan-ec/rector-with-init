<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var','bin','vendor'])
;
return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_to_return_type' => ['scalar_types' => false],
        'native_function_invocation' => ['scope' => 'all'],
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_to_comment' =>false
    ])
    ->setFinder($finder);
