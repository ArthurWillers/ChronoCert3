<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('acc:purge-rejected-submissions')]
#[Description('Informa que documentos rejeitados são preservados para reanálise.')]
class PurgeRejectedSubmissions extends Command
{
    public function handle(): int
    {
        $this->info('Documentos rejeitados são preservados para reanálise.');

        return self::SUCCESS;
    }
}
