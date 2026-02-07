<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\FileWatch\Continuation\State;

/**
 * @psalm-immutable
 * @template T
 */
final class Continuation
{
    /**
     * @param T $value
     */
    private function __construct(
        private State $state,
        private mixed $value,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function of(mixed $value): self
    {
        return new self(State::continue, $value);
    }

    /**
     * @template U
     *
     * @param U $value
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public function continue(mixed $value): self
    {
        return new self(State::continue, $value);
    }

    /**
     * @template U
     *
     * @param U $value
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public function stop(mixed $value): self
    {
        return new self(State::stop, $value);
    }

    /**
     * @internal
     * @template R
     *
     * @param callable(T): R $continue
     * @param callable(T): R $stop
     *
     * @return R
     */
    #[\NoDiscard]
    public function match(
        callable $continue,
        callable $stop,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->state) {
            State::continue => $continue($this->value),
            State::stop => $stop($this->value),
        };
    }
}
