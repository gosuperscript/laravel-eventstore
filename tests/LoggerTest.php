<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use DigitalRisks\LaravelEventStore\BasicLogger;
use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Contracts\Logger;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\Tests\Fixtures\TestEvent;
use DigitalRisks\LaravelEventStore\Tests\Traits\InteractsWithEventStore;
use DigitalRisks\LaravelEventStore\Tests\Traits\MakesEventRecords;
use DigitalRisks\LaravelEventStore\Traits\SendsToEventStore;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogFake;

class LoggerTest extends TestCase
{
    use MakesEventRecords;

    /** @test */
    public function it_logs_an_ignored_classed_event()
    {
        // Arrange.
        Log::swap(new LogFake);

        $event = new TestEvent();
        $event->setEventRecord($this->makeEventRecord('test_event', ['hello' => 'world']));

        // Act.
        $logger = resolve(Logger::class);
        $logger->eventStart($event, null);

        // Assert.
        Log::assertLogged('info', function ($msg, $context) {
            $this->assertStringContainsString("/streams/test-stream/0", $msg);
            $this->assertEquals(['type' => 'DigitalRisks\LaravelEventStore\Tests\Fixtures\TestEvent', 'hasListeners' => false], $context);

            return true;
        });
    }

    /** @test */
    public function it_logs_an_event()
    {
        // Arrange.
        Log::swap(new LogFake);

        $event = $this->makeEventRecord('test_event', ['hello' => 'world']);

        // Act.
        $logger = resolve(Logger::class);
        $logger->eventStart($event->getType(), $event);

        // Assert.
        Log::assertLogged('info', function ($msg, $context) {
            $this->assertStringContainsString("/streams/test-stream/0", $msg);
            $this->assertEquals(['type' => 'test_event', 'hasListeners' => false], $context);

            return true;
        });
    }

    /** @test */
    public function it_logs_an_event_with_a_listener()
    {
        // Arrange.
        Log::swap(new LogFake);

        $event = $this->makeEventRecord('test_event', ['hello' => 'world']);
        Event::listen('test_event', function () {});

        // Act.
        $logger = resolve(Logger::class);
        $logger->eventStart($event->getType(), $event);

        // Assert.
        Log::assertLogged('info', function ($msg, $context) {
            $this->assertStringContainsString("/streams/test-stream/0", $msg);
            $this->assertEquals(['type' => 'test_event', 'hasListeners' => true], $context);

            return true;
        });
    }
}
