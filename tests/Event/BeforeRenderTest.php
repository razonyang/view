<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event;

use Yiisoft\View\Event\BeforeRender;
use PHPUnit\Framework\TestCase;

class BeforeRenderTest extends TestCase
{
    public function testNotStopped(): void
    {
        $event = new BeforeRender('file.html', []);
        self::assertFalse($event->isPropagationStopped());
    }

    public function testStopped(): void
    {
        $event = new BeforeRender('file.html', []);
        $event->stopPropagation();

        self::assertTrue($event->isPropagationStopped());
    }
}
