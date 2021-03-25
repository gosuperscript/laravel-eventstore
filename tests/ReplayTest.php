<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use Illuminate\Support\Facades\Event;

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
}
