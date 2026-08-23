<?php

declare(strict_types=1);

namespace App\Exceptions;

use Error;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Override;
use Throwable;

class Handler extends ExceptionHandler
{
    #[Override]
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    private bool $handlingError = false;

    #[Override]
    public function register(): void
    {
        $this->reportable(
            function (Throwable $e): void {
                // Prevent recursive error handling
                if ($this->handlingError) {
                    return;
                }

                $this->handlingError = true;

                try {
                    if ($e instanceof Error && (str_contains(
                        $e->getMessage(),
                        'Maximum call stack size'
                    ) || str_contains(
                        $e->getMessage(),
                        'Container.php line 1048'
                    ))) {
                        logger()->error(
                            'Recursion or stack overflow detected',
                            [
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'trace' => $e->getTraceAsString(),
                            ]
                        );
                    }
                } finally {
                    $this->handlingError = false;
                }
            }
        );
    }
}
