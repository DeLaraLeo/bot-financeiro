<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Amqp\ConsumerManager;
use Psr\Container\ContainerInterface;

#[Command]
class StartConsumerCommand extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container
    ) {
        parent::__construct('consumer:start');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Start RabbitMQ Consumer');
    }

    public function handle()
    {
        $this->line('Starting RabbitMQ Consumer...');
        
        $consumerManager = $this->container->get(ConsumerManager::class);
        $consumerManager->run();
    }
}
