<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\View\View;

/** @var array $params */

return [
    View::class => [
        'class' => View::class,
        '__construct()' => [
            'basePath' => static fn (Aliases $aliases) => $aliases->get($params['yiisoft/view']['basePath']),
        ],
        'withDefaultParameters()' => [
            $params['yiisoft/view']['defaultParameters'],
        ],
    ],
];
