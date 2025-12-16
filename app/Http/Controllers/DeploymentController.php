<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class DeploymentController extends Controller
{
    /**
     * Security token for deployment routes
     * IMPORTANT: Change this token and remove routes after deployment
     */
    private const DEPLOYMENT_TOKEN = 'DeployMovilTech2025!SecretKeyXYZ789';

    /**
     * Verify deployment token
     */
    private function verifyToken(Request $request): bool
    {
        $token = $request->get('token') ?? $request->header('X-Deployment-Token');
        return $token === self::DEPLOYMENT_TOKEN;
    }

    /**
     * Show deployment dashboard
     */
    public function index(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        $migrationStatus = $this->getMigrationStatus();
        $catalogCounts = $this->getCatalogCounts();

        return view('deployment.index', [
            'migrationStatus' => $migrationStatus,
            'catalogCounts' => $catalogCounts,
            'token' => $request->get('token'),
        ]);
    }

    /**
     * Run migrations
     */
    public function migrate(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migrations executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Run seeders (only non-destructive ones)
     */
    public function seed(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        $seeder = $request->get('seeder', 'DatabaseSeeder');

        // All seeders use safe methods (firstOrCreate, updateOrCreate, etc.)
        $allowedSeeders = [
            'RoleSeeder',
            'UserSeeder',
            'CategorySeeder',
            'SupplierSeeder',
            'ProductSeeder',
            'CustomerSeeder',
            'DianIdentificationDocumentSeeder',
            'DianLegalOrganizationSeeder',
            'DianCustomerTributeSeeder',
            'DianDocumentTypeSeeder',
            'DianOperationTypeSeeder',
            'DianPaymentMethodSeeder',
            'DianPaymentFormSeeder',
            'DianProductStandardSeeder',
            'ProductionSeeder',
            'DatabaseSeeder',
        ];

        if (!in_array($seeder, $allowedSeeders)) {
            return response()->json([
                'success' => false,
                'message' => 'Seeder no permitido. Solo se permiten seeders de la lista autorizada.',
            ], 403);
        }

        try {
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Seeder executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeder failed: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Sync municipalities from Factus
     */
    public function syncMunicipalities(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        try {
            Artisan::call('factus:sync-municipalities', [
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Municipios sincronizados exitosamente',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar municipios: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Sync numbering ranges from Factus
     */
    public function syncNumberingRanges(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        try {
            Artisan::call('factus:sync-numbering-ranges', [
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Rangos de numeración sincronizados exitosamente',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar rangos de numeración: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Sync measurement units from Factus
     */
    public function syncMeasurementUnits(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        try {
            Artisan::call('factus:sync-measurement-units', [
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Unidades de medida sincronizadas exitosamente',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar unidades de medida: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Check database status
     */
    public function status(Request $request)
    {
        if (!$this->verifyToken($request)) {
            abort(403, 'Invalid deployment token');
        }

        $migrations = $this->getPendingMigrations();
        $tables = $this->getTableStatus();
        $migrationStatus = $this->getMigrationStatus();

        return response()->json([
            'migrations' => $migrations,
            'tables' => $tables,
            'migration_status' => $migrationStatus,
        ]);
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        try {
            $migrationsPath = database_path('migrations');
            $files = File::files($migrationsPath);
            
            $migrations = [];
            foreach ($files as $file) {
                $migrations[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                ];
            }

            return $migrations;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get catalog counts
     */
    private function getCatalogCounts(): array
    {
        try {
            return [
                'identification_documents' => \App\Models\DianIdentificationDocument::count(),
                'legal_organizations' => \App\Models\DianLegalOrganization::count(),
                'customer_tributes' => \App\Models\DianCustomerTribute::count(),
                'document_types' => \App\Models\DianDocumentType::count(),
                'operation_types' => \App\Models\DianOperationType::count(),
                'payment_methods' => \App\Models\DianPaymentMethod::count(),
                'payment_forms' => \App\Models\DianPaymentForm::count(),
                'product_standards' => \App\Models\DianProductStandard::count(),
                'municipalities' => \App\Models\DianMunicipality::count(),
                'numbering_ranges' => \App\Models\FactusNumberingRange::count(),
                'active_ranges' => \App\Models\FactusNumberingRange::where('is_active', true)->count(),
                'measurement_units' => \App\Models\DianMeasurementUnit::count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table status
     */
    private function getTableStatus(): array
    {
        $requiredTables = [
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];

        $tables = [];
        foreach ($requiredTables as $table) {
            $tables[$table] = [
                'exists' => Schema::hasTable($table),
                'count' => Schema::hasTable($table) ? DB::table($table)->count() : 0,
            ];
        }

        return $tables;
    }

    /**
     * Get migration status
     */
    private function getMigrationStatus(): array
    {
        try {
            $migrationsPath = database_path('migrations');
            $files = glob($migrationsPath . '/*.php');
            $totalMigrations = count($files);

            if (!Schema::hasTable('migrations')) {
                return [
                    'total' => $totalMigrations,
                    'executed' => 0,
                    'pending' => $totalMigrations,
                    'pending_list' => array_map(function($file) {
                        return basename($file, '.php');
                    }, $files),
                ];
            }

            $executedMigrations = DB::table('migrations')->count();
            $executed = DB::table('migrations')->pluck('migration')->toArray();
            $pendingMigrations = $totalMigrations - $executedMigrations;

            $pendingList = [];
            if ($pendingMigrations > 0) {
                foreach ($files as $file) {
                    $migrationName = basename($file, '.php');
                    if (!in_array($migrationName, $executed)) {
                        $pendingList[] = $migrationName;
                    }
                }
            }

            return [
                'total' => $totalMigrations,
                'executed' => $executedMigrations,
                'pending' => $pendingMigrations,
                'pending_list' => $pendingList,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'executed' => 0,
                'pending' => 0,
                'pending_list' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}

