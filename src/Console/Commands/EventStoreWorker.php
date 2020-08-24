<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use DigitalRisks\LaravelEventStore\EventStore as LaravelEventStore;

class EventStoreWorker extends Command
{
    protected $signature = 'eventstore:worker';

    protected $description = 'Worker handling running stream processes';

    protected function spawnProcess($stream, $type = 'persistent')
    {
        if (empty($stream)) {
            return null;
        }

        $command = "php artisan eventstore:worker-thread --stream={$stream} --type={$type}";
        $process = Process::fromShellCommandline($command);
        $process->start();

        return $process;
    }

    public function handle(): void
    {
        $entries = [];

        foreach (config('eventstore.subscription_streams') as $stream) {
            if (($process = $this->spawnProcess($stream, 'persistent')) !== null) {
                $entries[] = [
                    'process' => $process,
                    'stream' => $stream,
                    'type' => 'persistent'
                ];
            }
        }

        foreach (config('eventstore.volatile_streams') as $stream) {
            if (($process = $this->spawnProcess($stream, 'volatile')) !== null) {
                $entries[] = [
                    'process' => $process,
                    'stream' => $stream,
                    'type' => 'volatile'
                ];
            }
        }

        while (true) {
            foreach ($entries as $key => $entry) {
                if (!$entry['process']->isRunning()) {
                    $this->info("Process {$key} {$entry['process']->getCommandLine()} stopped running - restarting");
                    unset($entries[$key]);

                    if (($process = $this->spawnProcess($entry['stream'], $entry['type'])) !== null) {
                        $entries[] = [
                            'process' => $process,
                            'stream' => $entry['stream'],
                            'type' => $entry['type']
                        ];
                    }
                }

                $output = $entry['process']->getIncrementalOutput();
                if (!empty($output)) {
                    foreach (explode(PHP_EOL, $output) as $line) {
                        $line = trim($line);

                        if (!empty($line)) {
                            (LaravelEventStore::$workerLogger)($line);
                        }
                    }
                }

                $errorOutput = $entry['process']->getIncrementalErrorOutput();
                if (!empty($errorOutput)) {
                    foreach (explode(PHP_EOL, $errorOutput) as $line) {
                        $line = trim($line);

                        if (!empty($line)) {
                            (LaravelEventStore::$workerErrorLogger)($line);
                        }
                    }
                }
            }

            sleep(1);
        }
    }
}
