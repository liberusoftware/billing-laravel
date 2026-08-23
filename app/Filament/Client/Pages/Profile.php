<?php

namespace App\Filament\Client\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Override;

/**
 * @property Schema $form
 */
class Profile extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    #[Override]
    protected string $view = 'filament.client.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ]
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(
                            'users',
                            'email',
                            ignorable: auth()->user()
                        ),
                    TextInput::make('current_password')
                        ->password()
                        ->label('Current Password')
                        ->required()
                        ->visible(fn ($get): bool => (bool) $get('password')),
                    TextInput::make('password')
                        ->password()
                        ->label('New Password')
                        ->rule(Password::defaults()),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->label('Confirm Password')
                        ->visible(fn ($get): bool => (bool) $get('password'))
                        ->same('password'),
                ]
            );
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            if (! Hash::check(
                $data['current_password'] ?? '',
                $user->password
            )) {
                $this->addError(
                    'current_password',
                    'The provided password is incorrect.'
                );

                return;
            }
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
