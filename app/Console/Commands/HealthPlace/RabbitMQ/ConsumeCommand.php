<?php

declare(strict_types=1);

namespace App\Console\Commands\HealthPlace\RabbitMQ;

use App\Exceptions\InvalidAMQPMessageException;
use App\Jobs\IngestDataJob;
use Bschmitt\Amqp\Amqp;
use Bschmitt\Amqp\Consumer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Log;

class ConsumeCommand extends Command
{
    use DispatchesJobs;

    protected $signature = 'healthplace:rabbitmq:consume';

    protected $description = 'Runs a AMQP consumer that defers work to the Laravel queue worker';

    public function handle(Amqp $consumer, LoggerInterface $logger): bool
    {
        $logger->info('Listening for messages...');

        $consumer->consume(
            '',
            function (AMQPMessage $message, Consumer $resolver) use ($logger): void {
                $logger->info('Consuming message...');

                try {
                    $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    $this->validateMessage($payload);
                    //Log::info('Message received');
                    $logger->info('Message received', $payload);
                    $this->dispatch(new IngestDataJob($payload['filepath']));
                    $logger->info('Message handled.');
                    $resolver->acknowledge($message);
                } catch (InvalidAMQPMessageException $exception) {
                    $logger->error('Message failed validation.');
                    $resolver->reject($message);
                } catch (Exception $exception) {
                    $logger->error('Message is not valid JSON.');
                    $resolver->reject($message);
                }
            },
            [
                'routing' => ['ingest.pending'],
            ]
        );

        $logger->info('Consumer exited.');

        return true;
    }

    private function validateMessage(array $payload): void
    {
        if (!is_string($payload['filepath'] ?? null)) {
            throw new InvalidAMQPMessageException('The [filepath] property must be a string.');
        }
    }
}