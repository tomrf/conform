<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR12' => true,
    '@PSR12:risky' => true,
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    '@PHP80Migration' => true,
    '@PHP80Migration:risky' => true,
    'mb_str_functions' => true,
])
    ->setFinder($finder)
;
