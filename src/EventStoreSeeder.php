<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EventStoreSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eventstore:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed ES with dummy events';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->faker = \Faker\Factory::create();
    }

    public function data()
    {
        $data = [];

        for ($i=0; $i<mt_rand(0, 100); $i++) {
            $data[$this->faker->word] = $this->faker->text;
        }

        return $data;
    }

    public function name()
    {
        return $this->faker->word;
    }

    public function guidv4()
    {
        return sprintf('%s%s-%s-%s-%s-%s%s%s', mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999), mt_rand(1000, 9999));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $streams = config('eventstore.streams');

        $client = new \GuzzleHttp\Client();
        while(true) {
            $stream = $streams[array_rand($streams)];

            $res = $client->request('POST', config('eventstore.http_url').'/streams/'.$stream, [
                'headers' => [
                    'Content-Type' => 'application/vnd.eventstore.events+json',
                ],
                'json' => [[
                    'data' => $this->data(),
                    'eventId' => $this->guidv4(),
                    'eventType' => $this->name(),
                    'metadata' => []
                ]]
            ]);
        }
    }
}
