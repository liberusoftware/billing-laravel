<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Licenses\Pages;

use App\Filament\Admin\Resources\Licenses\LicenseResource;
use App\Services\LicenseService;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateLicense extends CreateRecord
{
    #[Override]
    protected static string $resource = LicenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['license_key'])) {
            $data['license_key'] = app(LicenseService::class)->generate();
        }

        return $data;
    }
}
