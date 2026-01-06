<?php

declare(strict_types=1);

namespace Extcode\CartEvents\Tests\Unit\Hooks;

use Extcode\CartEvents\Hooks\DatamapDataHandlerHook;
use PHPUnit\Framework\TestCase;

class DatamapDataHandlerHookTest extends TestCase
{
    public function testIsAllowedTargetPageAllowsListEventsOnNon186(): void
    {
        $hook = new DatamapDataHandlerHook();

        $reflection = new \ReflectionClass($hook);
        $method = $reflection->getMethod('isAllowedTargetPage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($hook, ['cartevents_listevents', 185]);

        $this->assertTrue($result);
    }

    public function testIsAllowedTargetPageAllowsShowEventOnNon186(): void
    {
        $hook = new DatamapDataHandlerHook();

        $reflection = new \ReflectionClass($hook);
        $method = $reflection->getMethod('isAllowedTargetPage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($hook, ['cartevents_showevent', 1]);

        $this->assertTrue($result);
    }

    public function testIsAllowedTargetPageDisallowsSingleEventOnNon186(): void
    {
        $hook = new DatamapDataHandlerHook();

        $reflection = new \ReflectionClass($hook);
        $method = $reflection->getMethod('isAllowedTargetPage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($hook, ['cartevents_singleevent', 185]);

        $this->assertFalse($result);
    }

    public function testIsAllowedTargetPageAllowsSingleEventOn186(): void
    {
        $hook = new DatamapDataHandlerHook();

        $reflection = new \ReflectionClass($hook);
        $method = $reflection->getMethod('isAllowedTargetPage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($hook, ['cartevents_singleevent', 186]);

        $this->assertTrue($result);
    }
}
