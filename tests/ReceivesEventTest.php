<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\Tests\Traits\InteractsWithEventStore;
use DigitalRisks\LaravelEventStore\Traits\AddsLaravelMetadata;
use DigitalRisks\LaravelEventStore\Traits\ReceivedFromEventStore;
use DigitalRisks\LaravelEventStore\Traits\SendsToEventStore;

class ReceivesEventTest extends TestCase
{
    use InteractsWithEventStore;

    /** @test */
    public function it_sets_the_event_record_on_received_event_when_event_connection_is_sync()
    {
        // Arrange.
        config(['eventstore.connection' => 'sync']);

        $event = new class implements CouldBeReceived {
            use ReceivedFromEventStore;

            public $hello = 'world';
        };

        // Act.
        $record = $event->getEventRecord();

        // Assert.
        $this->assertEquals('world', $record->getData()['hello']);
    }

    /** @test */
    public function it_sets_the_event_record_on_stored_event_when_event_connection_is_sync()
    {
        // Arrange.
        config(['eventstore.connection' => 'sync']);
        config(['app.name' => 'laravel-eventstore']);

        $event = new class implements CouldBeReceived, ShouldBeStored {
            use ReceivedFromEventStore, SendsToEventStore, AddsLaravelMetadata;

            public $hello = 'world';

            public function getData(): array {
                return ['hello' => 'universe'];
            }

            public function getEventStream(): string {
                return 'test';
            }
        };

        // Act.
        $record = $event->getEventRecord();

        // Assert.
        $this->assertEquals('universe', $record->getData()['hello']);
        $this->assertEquals('laravel-eventstore', $record->getMetadata()['name']);
    }

    /** @test */
    public function event_record_is_null_when_event_connection_is_not_sync()
    {
        // Arrange.
        config(['eventstore.connection' => '']);

        $event = new class implements CouldBeReceived {
            use ReceivedFromEventStore;
        };

        // Act.
        $record = $event->getEventRecord();

        // Assert.
        $this->assertNull($record);
    }
}
