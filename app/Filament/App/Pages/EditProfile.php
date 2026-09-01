<?php

namespace App\Filament\App\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

/**
 * @property Schema $form
 */
class EditProfile extends Page
{
    #[Override]
    protected string $view = 'filament.pages.edit-profile';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Account';

    #[Override]
    protected static ?string $navigationLabel = 'Profile';

    #[Override]
    protected static ?int $navigationSort = 10;

    public User $user;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->form->fill(
            [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]
            );
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $this->user->forceFill(
            [
                'name' => $state['name'],
                'email' => $state['email'],
            ]
        )->save();

        Notification::make()
            ->title('Your profile has been updated.')
            ->success()
            ->send();
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Edit Profile',
        ];
    }
}
