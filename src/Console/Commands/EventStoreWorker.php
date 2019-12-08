<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class EventStoreWorker extends Command
{
    protected $signature = 'eventstore:worker
        {--parallel=10 : How many events to run in parallel.}';

    protected $description = 'Worker handling running stream processes';

    public function handle(): void
    {
        $processes = [];

        foreach (config('eventstore.subscription_streams') as $stream) {
            if (empty($stream))
                continue;

            $command = "php artisan eventstore:worker-thread --stream={$stream} --type=persistent --parallel={$this->option('parallel')}";
            $process = Process::fromShellCommandline($command);
            $process->start();

            $processes[] = $process;
        }

        foreach (config('eventstore.volatile_streams') as $stream) {
            if (empty($stream))
                continue;

            $command = "php artisan eventstore:worker-thread --stream={$stream} --type=volatile --parallel={$this->option('parallel')}";
            $process = Process::fromShellCommandline($command);
            $process->start();

            $processes[] = $process;
        }

        while(true) {
            foreach($processes as $process)
                if (!$process->isRunning()) {
                    throw new \Exception("Process {$process->getCommandLine()} stopped running");
                }

            usleep(1000);
        }
    }
}
