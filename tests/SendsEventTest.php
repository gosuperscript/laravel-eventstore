<?php

namespace Mannum\LaravelEventStore\Tests;

use Mannum\LaravelEventStore\ShouldBeEventStored;
use Mannum\LaravelEventStore\Tests\Traits\InteractsWithEventStore;
use Mannum\LaravelEventStore\SendsToEventStore;

class SendsEventTest extends TestCase
{
    use InteractsWithEventStore;

    /** @test */
    public function it_sends_the_event_to_eventstore()
    {
        // Arrange.
        $event = new class implements ShouldBeEventStored {
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
