<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    'mb_str_functions' => true,
    'modernize_strpos' => true,
])
    ->setFinder($finder)
;
