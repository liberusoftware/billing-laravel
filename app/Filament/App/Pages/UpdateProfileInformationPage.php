<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

/**
 * @property Schema $form
 */
class UpdateProfileInformationPage extends Page
{
    #[Override]
    protected string $view = 'filament.pages.profile.update-profile-information';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Account';

    #[Override]
    protected static ?int $navigationSort = 0;

    #[Override]
    protected static ?string $title = 'Profile';

    public $name;

    public $email;

    public function mount(): void
    {
        $this->form->fill(
            [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
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
                        ->required(),
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required(),
                ]
            );
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $user = Auth::user();

        $user->forceFill(
            [
                'name' => $state['name'],
                'email' => $state['email'],
            ]
        )->save();

        session()->flash(
            'status',
            'Your profile has been updated.'
        );
    }

    #[Override]
    public function getHeading(): string
    {
        return static::$title;
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
