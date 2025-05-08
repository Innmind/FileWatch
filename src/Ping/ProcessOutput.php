<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Continuation;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Signal,
    Process\Output\Type,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class ProcessOutput implements Implementation
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
     * @return Attempt<R|C>
     */
    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Attempt
    {
        return $this
            ->processes
            ->execute($this->command)
            ->flatMap(function($process) use ($carry, $ping) {
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

                return $this
                    ->kill($process)
                    ->flatMap(static fn() => match ($carry) {
                        $failed => Attempt::error(new \Exception('An error occured')),
                        default => Attempt::result($carry),
                    });
            });
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function kill(Process $process): Attempt
    {
        return $process->pid()->match(
            fn($pid) => $this->processes->kill($pid, Signal::terminate),
            static fn() => Attempt::result(SideEffect::identity()),
        );
    }
}
