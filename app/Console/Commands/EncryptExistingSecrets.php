<?php

namespace App\Console\Commands;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\WebhookEndpoint;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

#[Description('Encrypt pre-existing plaintext values in encrypted-cast secret columns (idempotent)')]
#[Signature('secrets:encrypt-existing {--dry-run : Report counts without writing}')]
class EncryptExistingSecrets extends Command
{
    /**
     * Models whose `encrypted`-cast columns may hold legacy plaintext.
     *
     * @var list<class-string<Model>>
     */
    private const MODELS = [
        PaymentGateway::class,
        PaymentMethod::class,
        WebhookEndpoint::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach (self::MODELS as $modelClass) {
            $model = new $modelClass;
            $table = $model->getTable();

            // Derive columns from the model's cast list — only those cast as `encrypted`.
            $columns = array_keys(array_filter(
                $model->getCasts(),
                fn (string $cast): bool => $cast === 'encrypted',
            ));

            foreach ($columns as $column) {
                [$encrypted, $skipped] = $this->processColumn($table, $column, $dryRun);
                $this->line(sprintf(
                    '%s.%s — encrypted %d, skipped %d already-encrypted%s',
                    $table,
                    $column,
                    $encrypted,
                    $skipped,
                    $dryRun ? ' (dry-run)' : '',
                ));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{0:int,1:int} [encrypted count, skipped count]
     */
    private function processColumn(string $table, string $column, bool $dryRun): array
    {
        $encrypted = 0;
        $skipped = 0;

        // Read raw values via the query builder to bypass the model's encrypted cast,
        // which would otherwise throw DecryptException on plaintext rows.
        DB::table($table)
            ->select('id', $column)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->each(function (object $row) use ($table, $column, $dryRun, &$encrypted, &$skipped): void {
                $value = $row->{$column};

                if ($this->isEncrypted($value)) {
                    $skipped++;

                    return;
                }

                $encrypted++;

                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$column => Crypt::encryptString($value)]);
                }
            });

        return [$encrypted, $skipped];
    }

    /**
     * A value is already encrypted if it decrypts cleanly as a Laravel
     * encrypted-string payload. Plaintext throws DecryptException.
     */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
