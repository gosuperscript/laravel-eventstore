<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\TestUtils\Traits\InteractsWithEventStore;
use DigitalRisks\LaravelEventStore\Traits\SendsToEventStore;

class SendsEventTest extends TestCase
{
    use InteractsWithEventStore;

    /** @test */
    public function it_sends_the_event_to_eventstore()
    {
        // Arrange.
        $event = new class implements ShouldBeStored {
            use SendsToEventStore;

            public function getEventStream(): string { return 'tests'; }
            public function getEventType(): string { return 'test_event'; }
        };

        // Act.
        event($event);

        // Assert.
        $this->assertEventStoreEventRaised('test_event', 'tests');
    }
}
