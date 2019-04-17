<?php

namespace Mannum\EventStore\Tests\Traits;

use Mannum\EventStore\Client;
use Mannum\EventStore\Tests\Constraint\EventNotOnEventStoreConstraint;
use Mannum\EventStore\Tests\Constraint\EventOnEventStoreConstraint;
use Mannum\EventStore\Tests\Constraint\EventsOnEventStoreConstraint;

trait InteractsWithEventStore
{
    // not happy with these asserts, though doing them via Rx library is a terrible headache (outside my scope atm)
    // and requires extending Rx\Functional\FunctionalTestCase
    // as defined here: https://books.google.co.uk/books?id=cLkrDwAAQBAJ&pg=PA152&lpg=PA152&dq=phpunit+rx+php&source=bl&ots=KhODHzhp-G&sig=6d6jWL-9PDx1jZux4l126j874zc&hl=en&sa=X&ved=2ahUKEwjDvYbP_vHeAhXCL8AKHaeOCzoQ6AEwBXoECAUQAQ#v=onepage&q&f=false
    // and somehow collapsing observables - PT

    // assert that event was raised
    public function assertEventStoreEventRaised($class, string $stream, ?array $data = null, ?array $metadata = null, int $limit = 1)
    {
        static::assertThat((object) [
            'class' => $class,
            'streamName' => $stream,
            'data' => $data,
            'metaData' => $metadata,
            'limit' => $limit,
        ], new EventOnEventStoreConstraint(
            $this->app->make(Client::class)
        ));
    }

    // assert that event was not raised
    public function assertEventStoreEventNotRaised($class, string $stream, ?array $data = null, ?array $metadata = null, int $limit = 1)
    {
        static::assertThat((object) [
            'class' => $class,
            'streamName' => $stream,
            'data' => $data,
            'metaData' => $metadata,
            'limit' => $limit,
        ], new EventNotOnEventStoreConstraint(
            $this->app->make(Client::class)
        ));
    }

    // assert that events were raised in specific order
    // e.g. for A, B, C events, check for:
    // - A, B will return true
    // - A, C will return true
    // - B, A will return false
    public function assertEventStoreEventsRaised(...$classes)
    {
        static::assertThat((object) [
            'classes' => $classes,
        ], new EventsOnEventStoreConstraint(
            $this->app->make(Client::class)
        ));
    }
}
