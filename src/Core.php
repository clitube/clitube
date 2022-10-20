<?php

declare(strict_types=1);

namespace CliTube;

use CliTube\Command\Core\CloseComponent;
use CliTube\Command\User\Quit;
use CliTube\Contract\Component;
use CliTube\Contract\Event\EventListener;
use CliTube\Contract\Event\EventSubscriber;
use CliTube\Contract\InteractiveComponent;
use CliTube\Internal\Container;
use CliTube\Internal\Events\EventDispatcher;
use CliTube\Internal\Events\ListenerProvider;
use CliTube\Internal\Events\Subscriber;
use CliTube\Internal\Screen\Paginator;
use CliTube\Internal\UserCommandFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Core implements EventListener
{
    /**
     * @var array<int, Component>
     */
    private array $components = [];

    private readonly Container $container;
    private readonly EventDispatcher $eventDispatcher;
    private Subscriber $subscriber;
    private bool $running = false;

    public function __construct(OutputInterface $output) {
        $this->container = new Container();
        $this->container->setObject($this);
        $this->container->set(OutputInterface::class, $output);
        $this->container->setObject(new Paginator($output));

        // Init event dispatcher
        $this->subscriber = new Subscriber();
        $this->container->set(EventSubscriber::class, $this->subscriber);
        $listenerProvider = new ListenerProvider($this->subscriber);
        $this->container->set(ListenerProviderInterface::class, $listenerProvider);
        $this->eventDispatcher = new EventDispatcher($listenerProvider);
        $this->container->set(EventDispatcherInterface::class, $this->eventDispatcher);
        // Subscribe itself
        foreach ([
            CloseComponent::class => fn (CloseComponent $e) => $this->terminateComponent($e->component),
        ] + $this->getListeners() as $commandClass => $listener) {
            $this->subscriber->subscribeCallable(
                $listener,
                EventSubscriber::PREPEND,
                ...(\is_int($commandClass) ? [] : (array)$commandClass)
            );
        }
    }

    public function run(): void
    {
        if ($this->running) {
            return;
        }
        $this->running = true;
        $inputStream = STDIN; # todo
        try {
            /**
             * @psalm-suppress RedundantCondition
             * Because the {@see Quit} event from a component can change the $this->running value
             */
            while ($this->running && $this->components !== []) {
                $component = \reset($this->components);

                if ($component instanceof InteractiveComponent) {
                    $commandFactory = $this->container->get(UserCommandFactory::class);
                    while (\reset($this->components) === $component) {
                        $input = \fgets($inputStream);
                        if ($input === false) {
                            break;
                        }

                        $command = $commandFactory->create($component->getUserCommands(), $input);
                        $component->interact($command);
                    }
                    continue;
                }
                $input = \fgets($inputStream);
                $this->terminateComponent($component);
            }
        } finally {
            $this->running = false;
        }
    }

    public function getListeners(): array
    {
        return [
            Quit::class => $this->exit(...),
        ];
    }

    /**
     * @template TComponent of Component
     *
     * @param class-string<TComponent> $component
     *
     * @return TComponent
     */
    public function createComponent(string $component, array $params = []): Component
    {
        $object = $this->container->make($component, $params);
        \array_unshift($this->components, $object);
        $this->subscriber->subscribeComponent($object);

        if ($this->running) {
            $object->run();
        }

        return $object;
    }

    private function terminateComponent(Component $component): void
    {
        if (!\in_array($component, $this->components, true)) {
            return;
        }
        foreach ($this->components as $k => $v) {
            unset($this->components[$k]);
            if ($v === $component) {
                break;
            }
            $this->subscriber->unsubscribeComponent($component);
        }
    }

    private function exit(): void
    {
        $this->running = false;
    }
}
