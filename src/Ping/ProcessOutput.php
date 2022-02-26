<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Exception\WatchFailed,
    Stop,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Signal,
    Process\Output\Type,
};
use Innmind\Immutable\Either;

final class ProcessOutput implements Ping
{
    private Processes $processes;
    private Command $command;

    public function __construct(Processes $processes, Command $command)
    {
        $this->processes = $processes;
        $this->command = $command;
    }

    public function __invoke(mixed $carry, callable $ping): Either
    {
        $process = $this->processes->execute($this->command);

        try {
            return $process
                ->output()
                ->reduce(
                    Either::right($carry),
                    function(Either $carry, $_, $type) use ($ping, $process): Either {
                        // we may have a left as entry here because while we are
                        // killing the process there may still be output transiting
                        // up to here, but since we have a left value we no longer
                        // want the $ping to be called
                        $stopping = $carry->match(
                            static fn() => false,
                            static fn() => true,
                        );

                        if ($stopping) {
                            return $carry;
                        }

                        if ($type === Type::error) {
                            $carry = Either::left(new WatchFailed);
                        }

                        /** @psalm-suppress MixedArgument Doesn't understand the type of $carry when calling $ping */
                        return $carry
                            ->flatMap(static fn($carry) => $ping($carry))
                            ->leftMap(function($left) use ($process) {
                                $this->kill($process);

                                return $left;
                            });
                    },
                )
                ->otherwise($this->switchStopValue(...));
        } catch (\Throwable $e) {
            $this->kill($process);

            throw $e;
        }
    }

    private function kill(Process $process): void
    {
        $_ = $process->pid()->match(
            fn($pid) => $this->processes->kill($pid, Signal::terminate),
            static fn() => null,
        );
    }

    /**
     * @template C
     * @template L
     *
     * @param L|Stop<C>|WatchFailed $value
     *
     * @return Either<WatchFailed|L, C>
     */
    private function switchStopValue(mixed $value): Either
    {
        return match ($value instanceof Stop) {
            true => Either::right($value->value()),
            false => Either::left($value),
        };
    }
}
