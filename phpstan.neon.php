<?php

/**
 * PHPStan Configuration
 * Laravel E-Commerce Backend
 */

declare(strict_types=1);

return [
    'includes' => [
        './vendor/larastan/larastan/extension.neon',
    ],
    'parameters' => [
        'paths' => [
            'app',
        ],
        'level' => 6,
        'excludePaths' => [
            'app/Console/Kernel.php',
        ],
        'ignoreErrors' => [
            '#Unsafe usage of new static#',
        ],
        'checkMissingIterableValueType' => false,
        'checkGenericClassInNonGenericObjectType' => false,
    ],
];
