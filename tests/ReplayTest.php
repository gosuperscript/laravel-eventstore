<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

class ReplayTest extends TestCase
{
    public function test_it_can_replay_a_single_event()
    {
        // Arrange.
        Event::fake();

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => '41749',
        ]);

        // Assert.
        Event::assertDispatched('account.opened');
    }

    public function test_it_can_replay_a_range_of_events_event()
    {
        // Arrange.
        Event::fake();

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => '41748-41753',
        ]);

        // Assert.
        Event::assertDispatched('account.opened');
        Event::assertDispatched('account.owner_assigned');
        Event::assertDispatched('account.contact_details_added');
        Event::assertDispatched('account.ern_details_added');
        Event::assertDispatched('account.business_data_assigned');
    }

    public function test_it_throws_an_exeption_for_invalid_event()
    {
        // Arrange.
        Event::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Events only accepts integer values');

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => 'foo',
        ]);
    }

    public function test_it_throws_an_exeption_for_invalid_event_range()
    {
        // Arrange.
        Event::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Events only accepts integer values');

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => '123-bar',
        ]);
    }

    public function test_it_throws_an_exception_for_downward_event_range()
    {
        // Arrange.
        Event::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Events must be an upward range');

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => '123-120',
        ]);
    }

    public function test_it_throws_an_exception_if_event_not_found()
    {
        // Arrange.
        Event::fake();

        $this->expectException(ClientException::class);

        // Act.
        $this->artisan('eventstore:replay', [
            'stream' => 'accounts-v13',
            'events' => '99999999999',
        ]);
    }
}
