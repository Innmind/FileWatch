<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Continuation,
    Ping,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Signal,
    Process\Output\Type,
};
use Innmind\Immutable\Maybe;

final class ProcessOutput implements Ping
{
    private Processes $processes;
    private Command $command;

    public function __construct(Processes $processes, Command $command)
    {
        $this->processes = $processes;
        $this->command = $command;
    }

    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Maybe<R|C>
     */
    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Maybe
    {
        $process = $this->processes->execute($this->command)->unwrap();
        $failed = new \stdClass;

        $carry = $process
            ->output()
            ->map(static fn($chunk) => $chunk->type())
            ->sink($carry)
            ->until(static function($carry, $type, $continuation) use ($ping, $failed) {
                if ($type === Type::error) {
                    /** @psalm-suppress InvalidArgument */
                    return $continuation->stop($failed);
                }

                /** @psalm-suppress MixedArgument */
                return $ping($carry, Continuation::of($carry))->match(
                    $continuation->continue(...),
                    $continuation->stop(...),
                );
            });

        $this->kill($process);

        return match ($carry) {
            $failed => Maybe::nothing(),
            default => Maybe::just($carry),
        };
    }

    private function kill(Process $process): void
    {
        $_ = $process->pid()->match(
            fn($pid) => $this->processes->kill($pid, Signal::terminate),
            static fn() => null,
        );
    }
}
