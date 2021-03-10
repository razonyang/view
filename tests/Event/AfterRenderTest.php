<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event;

use Yiisoft\View\Event\AfterRender;
use PHPUnit\Framework\TestCase;

class AfterRenderTest extends TestCase
{
    public function testGetResult(): void
    {
        $result = 'test-result';

        $event = new AfterRender('file.html', [], $result);

        self::assertSame($result, $event->getResult());
    }
}
