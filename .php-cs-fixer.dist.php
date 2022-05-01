<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in('src/');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR12' => true,
    '@PSR12:risky' => true,
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    '@PHP80Migration' => true,
    '@PHP80Migration:risky' => true,
    'mb_str_functions' => true,
    'phpdoc_no_package' => false,
    'php_unit_method_casing' => ['case' => 'snake_case'],
])->setFinder($finder);
