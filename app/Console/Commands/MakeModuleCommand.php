<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The PascalCase module name} {--force : Overwrite if exists} {--modular : Create in app-modules/ with src/ layout (modular pattern)}';

    protected $description = 'Scaffold a new application module';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! preg_match(
            '/^[A-Z][A-Za-z0-9]+$/',
            $name
        )) {
            $this->error('Module name must start with an uppercase letter and contain only alphanumeric characters.');

            return self::FAILURE;
        }

        $modular = $this->option('modular');

        if ($modular) {
            $modulePath = base_path("app-modules/{$name}");
            $relPath = "app-modules/{$name}";
            $namespace = config(
                'modules.alt_namespace',
                'Modules'
            );
            $srcPath = "{$modulePath}/src";
        } else {
            $modulePath = app_path("Modules/{$name}");
            $relPath = "app/Modules/{$name}";
            $namespace = config(
                'modules.namespace',
                'App\\Modules'
            );
            $srcPath = $modulePath;
        }

        if (File::exists($modulePath) && ! $this->option('force')) {
            $this->error("Module '{$name}' already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        if ($modular) {
            $this->createModularDirectories($modulePath);
            $this->createModularComposerJson(
                $name,
                $modulePath,
                $namespace
            );
        } else {
            $this->createDirectories($modulePath);
        }

        $this->createModuleJson(
            $name,
            $srcPath
        );
        $this->createModuleClass(
            $name,
            $srcPath,
            $namespace
        );
        $this->createServiceProvider(
            $name,
            $srcPath,
            $namespace
        );
        $this->createRoutes(
            $name,
            $modulePath
        );
        $this->createController(
            $name,
            $srcPath,
            $namespace
        );
        $this->createModel(
            $name,
            $srcPath,
            $namespace
        );
        $this->createMigration(
            $name,
            "{$modulePath}/database"
        );
        $this->createView(
            $name,
            "{$modulePath}/resources/views"
        );
        $this->createConfig(
            $name,
            "{$modulePath}/config"
        );
        $this->createFilamentResource(
            $name,
            $srcPath,
            $namespace
        );
        $this->createTest(
            $name,
            "{$modulePath}/tests",
            $namespace
        );

        $this->info("Module '{$name}' created at {$relPath}");
        $this->line('Run: <comment>composer dump-autoload</comment> to register the new module.');

        return self::SUCCESS;
    }

    private function createDirectories(string $modulePath): void
    {
        foreach ([
            'Filament/Resources',
            'Filament/Pages',
            'Filament/Widgets',
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Providers',
            'Services',
            'config',
            'database/migrations',
            'database/seeders',
            'resources/assets',
            'resources/lang',
            'resources/views',
            'routes',
            'tests',
        ] as $dir) {
            File::makeDirectory(
                "{$modulePath}/{$dir}",
                0755,
                true,
                true
            );
        }
    }

    private function createModularDirectories(string $modulePath): void
    {
        foreach ([
            'src/Filament/Resources',
            'src/Filament/Pages',
            'src/Filament/Widgets',
            'src/Http/Controllers',
            'src/Http/Middleware',
            'src/Models',
            'src/Providers',
            'src/Services',
            'config',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'resources/assets',
            'resources/lang',
            'resources/views',
            'routes',
            'tests/Unit',
            'tests/Feature',
        ] as $dir) {
            File::makeDirectory(
                "{$modulePath}/{$dir}",
                0755,
                true,
                true
            );
        }
    }

    private function createModularComposerJson(string $name, string $modulePath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        ).'\\';
        File::put(
            "{$modulePath}/composer.json",
            json_encode(
                [
                    'name' => 'liberu/'.strtolower($name),
                    'description' => "The {$name} module for Liberu Billing.",
                    'type' => 'library',
                    'autoload' => [
                        'psr-4' => [
                            "{$ns}{$name}\\" => 'src/',
                        ],
                    ],
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )."\n"
        );
    }

    private function createModuleJson(string $name, string $modulePath): void
    {
        File::put(
            "{$modulePath}/module.json",
            json_encode(
                [
                    'name' => $name,
                    'version' => '1.0.0',
                    'description' => "The {$name} module.",
                    'dependencies' => [],
                    'config' => [],
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )."\n"
        );
    }

    private function createModuleClass(string $name, string $srcPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::put(
            "{$srcPath}/{$name}Module.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name};

        use App\Modules\BaseModule;
        use Illuminate\Support\Facades\Log;

        class {$name}Module extends BaseModule
        {
            protected function onEnable(): void
            {
                Log::info('{$name} module enabled.');
            }

            protected function onDisable(): void
            {
                Log::info('{$name} module disabled.');
            }

            protected function onInstall(): void
            {
                Log::info('{$name} module installed.');
            }

            protected function onUninstall(): void
            {
                Log::info('{$name} module uninstalled.');
            }
        }
        PHP
        );
    }

    private function createServiceProvider(string $name, string $srcPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::put(
            "{$srcPath}/Providers/{$name}ServiceProvider.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Providers;

        use Illuminate\Support\ServiceProvider;

        class {$name}ServiceProvider extends ServiceProvider
        {
            public function register(): void {}

            public function boot(): void
            {
                // Filament resources, pages, and widgets in Filament/ are auto-discovered
                // by ModuleServiceProvider via PanelRegistry. No manual registration needed.
            }
        }
        PHP
        );
    }

    private function createFilamentResource(string $name, string $srcPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::makeDirectory(
            "{$srcPath}/Filament/Resources/{$name}Resource/Pages",
            0755,
            true,
            true
        );

        File::put(
            "{$srcPath}/Filament/Resources/{$name}Resource.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Filament\\Resources;

        use {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource\\Pages;
        use {$ns}\\{$name}\\Models\\{$name};
        use Filament\Forms;
        use Filament\Forms\Form;
        use Filament\Resources\Resource;
        use Filament\Tables;
        use Filament\Tables\Table;

        class {$name}Resource extends Resource
        {
            protected static ?string \$model = {$name}::class;

            protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';

            protected static ?string \$navigationGroup = '{$name}';

            public static function form(Form \$form): Form
            {
                return \$form->schema([
                    Forms\Components\TextInput::make('id')->disabled(),
                ]);
            }

            public static function table(Table \$table): Table
            {
                return \$table
                    ->columns([
                        Tables\Columns\TextColumn::make('id')->sortable(),
                        Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                    ])
                    ->filters([])
                    ->actions([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                    ])
                    ->bulkActions([
                        Tables\Actions\BulkActionGroup::make([
                            Tables\Actions\DeleteBulkAction::make(),
                        ]),
                    ]);
            }

            public static function getRelations(): array
            {
                return [];
            }

            public static function getPages(): array
            {
                return [
                    'index' => Pages\\List{$name}s::route('/'),
                    'create' => Pages\\Create{$name}::route('/create'),
                    'edit' => Pages\\Edit{$name}::route('/{record}/edit'),
                ];
            }
        }
        PHP
        );

        File::put(
            "{$srcPath}/Filament/Resources/{$name}Resource/Pages/List{$name}s.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource\\Pages;

        use {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource;
        use Filament\Actions;
        use Filament\Resources\Pages\ListRecords;

        class List{$name}s extends ListRecords
        {
            protected static string \$resource = {$name}Resource::class;

            protected function getHeaderActions(): array
            {
                return [
                    Actions\CreateAction::make(),
                ];
            }
        }
        PHP
        );

        File::put(
            "{$srcPath}/Filament/Resources/{$name}Resource/Pages/Create{$name}.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource\\Pages;

        use {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource;
        use Filament\Resources\Pages\CreateRecord;

        class Create{$name} extends CreateRecord
        {
            protected static string \$resource = {$name}Resource::class;
        }
        PHP
        );

        File::put(
            "{$srcPath}/Filament/Resources/{$name}Resource/Pages/Edit{$name}.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource\\Pages;

        use {$ns}\\{$name}\\Filament\\Resources\\{$name}Resource;
        use Filament\Actions;
        use Filament\Resources\Pages\EditRecord;

        class Edit{$name} extends EditRecord
        {
            protected static string \$resource = {$name}Resource::class;

            protected function getHeaderActions(): array
            {
                return [
                    Actions\DeleteAction::make(),
                ];
            }
        }
        PHP
        );
    }

    private function createRoutes(string $name, string $modulePath): void
    {
        $snake = strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/',
                '-$0',
                $name
            )
        );

        File::put(
            "{$modulePath}/routes/web.php",
            <<<PHP
        <?php

        use Illuminate\Support\Facades\Route;

        Route::middleware(['web', 'auth'])->prefix('{$snake}')->name('{$snake}.')->group(function () {
            // {$name} module web routes
        });
        PHP
        );

        File::put(
            "{$modulePath}/routes/api.php",
            <<<PHP
        <?php

        use Illuminate\Support\Facades\Route;

        Route::middleware(['api', 'auth:sanctum'])->prefix('api/{$snake}')->name('api.{$snake}.')->group(function () {
            // {$name} module API routes
        });
        PHP
        );
    }

    private function createController(string $name, string $srcPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::put(
            "{$srcPath}/Http/Controllers/{$name}Controller.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Http\\Controllers;

        use Illuminate\Http\Request;
        use Illuminate\Routing\Controller;

        class {$name}Controller extends Controller
        {
            public function index(Request \$request)
            {
                return view('{$name}::index');
            }
        }
        PHP
        );
    }

    private function createModel(string $name, string $srcPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::put(
            "{$srcPath}/Models/{$name}.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Models;

        use Illuminate\Database\Eloquent\Model;

        class {$name} extends Model
        {
            protected \$guarded = [];
        }
        PHP
        );
    }

    private function createMigration(string $name, string $dbPath): void
    {
        $table = strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/',
                '_$0',
                $name
            )
        ).'s';
        $timestamp = now()->format('Y_m_d_His');

        File::makeDirectory(
            "{$dbPath}/migrations",
            0755,
            true,
            true
        );
        File::put(
            "{$dbPath}/migrations/{$timestamp}_create_{$table}_table.php",
            <<<PHP
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        };
        PHP
        );
    }

    private function createView(string $name, string $viewsPath): void
    {
        File::makeDirectory(
            $viewsPath,
            0755,
            true,
            true
        );
        File::put(
            "{$viewsPath}/index.blade.php",
            <<<BLADE
        <x-app-layout>
            <x-slot name="header">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {$name}
                </h2>
            </x-slot>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <p>Welcome to the {$name} module.</p>
                    </div>
                </div>
            </div>
        </x-app-layout>
        BLADE
        );
    }

    private function createConfig(string $name, string $configPath): void
    {
        $key = strtolower($name);
        File::makeDirectory(
            $configPath,
            0755,
            true,
            true
        );
        File::put(
            "{$configPath}/{$key}.php",
            <<<PHP
        <?php

        return [
            // {$name} module configuration
        ];
        PHP
        );
    }

    private function createTest(string $name, string $testsPath, string $namespace): void
    {
        $ns = rtrim(
            $namespace,
            '\\'
        );
        File::makeDirectory(
            $testsPath,
            0755,
            true,
            true
        );
        File::put(
            "{$testsPath}/{$name}ModuleTest.php",
            <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns}\\{$name}\\Tests;

        use {$ns}\\{$name}\\{$name}Module;
        use Tests\TestCase;

        class {$name}ModuleTest extends TestCase
        {
            public function test_module_can_be_instantiated(): void
            {
                \$module = new {$name}Module;
                \$this->assertSame('{$name}', \$module->getName());
            }
        }
        PHP
        );
    }
}
