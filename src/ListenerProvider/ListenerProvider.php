<?php

declare(strict_types=1);

namespace Helium\EventDispatcher\ListenerProvider;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * The strict listener provider implementation as explained in the PSR-14.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    use ListenersRuntimeStorageTrait;

    /** @var iterable */
    private $listeners;

    /** @var string[] */
    private $listenersParameterMap;

    /**
     * ListenerProvider constructor.
     *
     * @param iterable $listeners
     */
    public function __construct(iterable $listeners)
    {
        $this->listeners = $listeners;
    }

    /**
     * {@inheritDoc}
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (null === $this->listenersParameterMap) {
            $this->registerListeners($this->listeners);
        }

        $eventName = get_class($event);

        if (!$this->storeHas($eventName)) {
            $this->initInStore($eventName);
            foreach ($this->listeners as $key => $listener) {
                if (is_a($event, $this->listenersParameterMap[$key])) {
                    $this->store($eventName, $listener);
                }
            }
        }

        dump(sprintf("----- Listeners for event %s -----", $eventName), $this->get($eventName));

        return $this->get($eventName);
    }

    public function registerListeners(iterable $listeners): void
    {
        $this->listeners = $listeners;
        $this->resetStorage();
        $this->listenersParameterMap = [];

        foreach ($listeners as $key => $listener) {
            $closure = \Closure::fromCallable($listener);
            $reflectionFunction = new \ReflectionFunction($closure);

            if (!$reflectionParameter = $reflectionFunction->getParameters()[0] ?? null) {
                continue;
            }

            if (!$class = $reflectionParameter->getClass()) {
                continue;
            }

            $this->listenersParameterMap[$key] = $class->getName();
        }
    }
}
