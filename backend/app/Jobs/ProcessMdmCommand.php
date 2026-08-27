<?php

namespace App\Jobs;

use App\Models\MdmCommand;
use App\Services\Mdm\MdmCommandService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMdmCommand implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $commandId)
    {
        $this->onQueue('mdm');
    }

    public function handle(MdmCommandService $commands): void
    {
        $command = MdmCommand::query()->find($this->commandId);

        if (! $command) {
            return;
        }

        $commands->process($command);
    }
}
