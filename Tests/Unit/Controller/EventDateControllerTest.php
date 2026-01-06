<?php

declare(strict_types=1);

namespace Extcode\CartEvents\Tests\Unit\Controller;

use Extcode\CartEvents\Classes\Controller\EventDateController;
use Extcode\CartEvents\Domain\Repository\EventDateRepository;
use PHPUnit\Framework\TestCase;

class EventDateControllerTest extends TestCase
{
    public function testAddCacheTagsWithArrayEventUid(): void
    {
        $repo = $this->createMock(EventDateRepository::class);
        $controller = new EventDateController($repo);

        $GLOBALS['TSFE'] = new class {
            public $captured;
            public function addCacheTags(array $tags)
            {
                $this->captured = $tags;
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('addCacheTags');
        $method->setAccessible(true);

        $method->invoke($controller, [[['event_uid' => 42], ['event_uid' => 7]]]);

        $this->assertSame([
            'tx_cartevents_event_42',
            'tx_cartevents_event_7',
        ], $GLOBALS['TSFE']->captured);
    }

    public function testAddCacheTagsWithArrayEventKey(): void
    {
        $repo = $this->createMock(EventDateRepository::class);
        $controller = new EventDateController($repo);

        $GLOBALS['TSFE'] = new class {
            public $captured;
            public function addCacheTags(array $tags)
            {
                $this->captured = $tags;
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('addCacheTags');
        $method->setAccessible(true);

        $method->invoke($controller, [[['event' => 13]]]);

        $this->assertSame(['tx_cartevents_event_13'], $GLOBALS['TSFE']->captured);
    }

    public function testAddCacheTagsWithObjectEvent(): void
    {
        $repo = $this->createMock(EventDateRepository::class);
        $controller = new EventDateController($repo);

        $GLOBALS['TSFE'] = new class {
            public $captured;
            public function addCacheTags(array $tags)
            {
                $this->captured = $tags;
            }
        };

        $event = new class {
            public function getUid()
            {
                return 99;
            }
        };

        $eventDate = new class ($event) {
            private $event;
            public function __construct($event)
            {
                $this->event = $event;
            }
            public function getEvent()
            {
                return $this->event;
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('addCacheTags');
        $method->setAccessible(true);

        $method->invoke($controller, [[$eventDate]]);

        $this->assertSame(['tx_cartevents_event_99'], $GLOBALS['TSFE']->captured);
    }
}
