<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     * @throws Exception
     */
    public function create(array $input): User
    {
        try {
            Validator::make(
                $input,
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                    'email' => [
                        'required',
                        'string',
                        'email',
                        'max:255',
                        Rule::unique(User::class),
                    ],
                    'password' => $this->passwordRules(),
                    // 'role' => ['required', 'string', Rule::in(['tenant', 'buyer', 'seller', 'landlord', 'contractor'])],
                ]
            )->validate();
            // $user = DB::transaction(function () use ($input) {
            //     return tap(,
            //     , function (User $user) use ($input) {
            //         $team = $this->assignOrCreateTeam($user);
            //         $user->switchTeam($team);
            //         setPermissionsTeamId($team->id);
            //         $user->assignRole($input['role']);
            //     });
            // });

            // Log::info('User created successfully', [
            //     'user_id' => $user->id,
            //     'email' => $user->email,
            //     'role' => $input['role'],
            // ]);

            return DB::transaction(
                function () use ($input) {
                    $team = $this->configureDefaultTeamContext();

                    return tap(
                        User::create(
                            [
                                'name' => $input['name'],
                                'email' => $input['email'],
                                'password' => Hash::make($input['password']),
                            ]
                        ),
                        function (User $user) use ($team): void {
                            $team->users()->attach($user);
                            $team = $this->createTeam($user);
                            $user->switchTeam($team);
                            setPermissionsTeamId($team->id);
                            $user = User::find($user->id);
                            try {
                                $user->assignRole('panel_user');
                            } catch (RoleDoesNotExist) {
                                Log::warning('Role panel_user does not exist, skipping role assignment for user '.$user->id);
                            }
                        }
                    );
                }
            );
        } catch (ValidationException $e) {
            Log::error(
                'User creation validation failed',
                [
                    'errors' => $e->errors(),
                    'input' => array_diff_key(
                        $input,
                        array_flip(['password'])
                    ),
                ]
            );
            throw $e;
        } catch (QueryException $e) {
            Log::error(
                'Database error during user creation',
                [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ]
            );
            throw new Exception(
                $this->getDatabaseErrorMessage($e),
                $e->getCode(),
                $e
            );
        } catch (RoleDoesNotExist $e) {
            Log::error(
                'Invalid role specified during user creation',
                [
                    'role' => $input['role'] ?? 'not provided',
                    'message' => $e->getMessage(),
                ]
            );
            throw new Exception(
                'Invalid role specified. Please choose a valid role.',
                $e->getCode(),
                $e
            );
        } catch (Exception $e) {
            Log::error(
                'Unexpected error during user creation',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'exception_class' => $e::class,
                ]
            );
            throw new Exception(
                'An unexpected error occurred. Please try again later.',
                $e->getCode(),
                $e
            );
        }
    }

    private function getDatabaseErrorMessage(QueryException $e): string
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        if (str_contains(
            $errorMessage,
            'Duplicate entry'
        )) {
            return 'A user with this email already exists. Please use a different email address.';
        } elseif ($errorCode == 1045) {
            return 'Database access denied. Please contact the administrator.';
        } elseif ($errorCode == 2002) {
            return 'Unable to connect to the database. Please try again later.';
        } else {
            return 'A database error occurred. Please try again later. Error code: '.$errorCode;
        }
    }

    /**
     * Set the default team context.
     *
     * @throws Exception
     */
    protected function configureDefaultTeamContext(): Team
    {
        // Attach new registrants to the admin-designated default team; fall back to
        // the first team, then create one on a fresh install.
        $team = Team::defaultForRegistration() ?? Team::first();
        if (! $team) {
            $team = Team::forceCreate(
                [
                    'user_id' => 0,
                    'name' => 'Default Team',
                    'personal_team' => false,
                    'is_default_for_registration' => true,
                ]
            );
        }
        setPermissionsTeamId($team->id);

        return $team;
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): Team
    {
        /** @var Team $team */
        $team = $user->ownedTeams()->create(
            [
                'name' => explode(
                    ' ',
                    $user->name,
                    2
                )[0]."'s Team",
                'personal_team' => true,
            ]
        );

        return $team;
    }
}
