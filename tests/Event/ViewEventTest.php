<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Tests\Mocks\TestViewEvent;

class ViewEventTest extends TestCase
{
    public function testFile(): void
    {
        $file = 'template.php';
        $event = new TestViewEvent($file, []);

        self::assertSame($file, $event->file());
    }

    public function testParameters(): void
    {
        $parameters = ['id' => 42];
        $event = new TestViewEvent('template.php', $parameters);

        self::assertSame($parameters, $event->parameters());
    }
}
