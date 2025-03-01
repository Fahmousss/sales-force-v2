<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Enums\TeamType;
use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        if (auth()->user()->team->type === TeamType::KANTOR_PUSAT && auth()->user()->team->parent_key !== null) {
            Notification::make()
                ->danger()
                ->title('Invalid Team Structure!')
                ->body('A Head Office should not have a parent team.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
