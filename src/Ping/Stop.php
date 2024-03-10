<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

/**
 * @internal
 * @template T
 */
final class Stop
{
    /** @var T */
    private mixed $value;

    /**
     * @param T $value
     */
    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
