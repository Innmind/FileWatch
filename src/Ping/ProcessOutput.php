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
use Innmind\Immutable\{
    Either,
    Maybe,
};

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
    public function __invoke(mixed $carry, callable $ping): Maybe
    {
        $process = $this->processes->execute($this->command);

        try {
            /**
             * @psalm-suppress InvalidArgument Due to the reduce where Either types are not enterily defined
             * @var Maybe<R|C>
             */
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
                            $carry = Either::left(new Failed);
                        }

                        /** @psalm-suppress MixedArgument Doesn't understand the type of $carry when calling $ping */
                        return $carry
                            ->flatMap(static fn($carry) => $ping($carry, Continuation::of($carry))->match(
                                Either::right(...),
                                static fn($value) => Either::left(Stop::of($value)),
                            ))
                            ->leftMap(function($left) use ($process) {
                                $this->kill($process);

                                return $left;
                            });
                    },
                )
                ->otherwise($this->switchStopValue(...))
                ->maybe();
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
     * @template R
     *
     * @param R|Stop<C>|Failed $value
     *
     * @return Either<Failed, R|C>
     */
    private function switchStopValue(mixed $value): Either
    {
        return match (true) {
            $value instanceof Stop => Either::right($value->value()),
            $value instanceof Failed => Either::left($value),
            default => Either::right($value),
        };
    }
}
