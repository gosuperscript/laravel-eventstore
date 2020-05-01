<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\Client;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\ClientException;

class EventStoreReset extends Command
{
    protected $signature = 'eventstore:reset';

    protected $description = 'Wipe the database, seed and recreate the persistent subscriptions.';
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        parent::__construct();
    }

    public function handle()
    {
        if (! $this->confirm('Please stop all workers first. Continue?')) return;

        $this->call('migrate:fresh', ['--seed' => true]);

        $streams = collect(config('eventstore.subscription_streams'));

        $streams->map([$this, 'deleteSubscription']);
        $streams->map([$this, 'createSubscription']);
    }

    public function deleteSubscription($stream)
    {
        $name = config('eventstore.group');

        try {
            $this->client->delete("/subscriptions/{$stream}/{$name}");
        }
        catch (ClientException $e) {
            throw_if($e->getCode() !== 404, $e);
        }
    }

    public function createSubscription($stream)
    {
        $name = config('eventstore.group');

        $this->client->put("/subscriptions/{$stream}/{$name}");
    }
}
