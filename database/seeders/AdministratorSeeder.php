<?php

namespace Database\Seeders;

use App\Actions\Administrators\CreateGlobalAdministrator;
use App\Enums\AffiliationType;
use App\Models\Affiliation;
use Illuminate\Database\Seeder;

class AdministratorSeeder extends Seeder
{
    /**
     * Seed the development global administrator and its audit trail.
     */
    public function run(CreateGlobalAdministrator $createGlobalAdministrator): void
    {
        if (Affiliation::query()->where('type', AffiliationType::Administrator)->exists()) {
            return;
        }

        $createGlobalAdministrator->execute(
            name: 'Administrador ChronoCert',
            cpf: '00000000000',
            email: 'admin@chronocert.test',
            operationalEmail: 'admin@chronocert.test',
            password: 'password',
            sourceDetail: 'database:seed',
        );
    }
}
