<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SalesTestDataSeeder;

class CreateSalesTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:create-test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea datos de prueba para el módulo de ventas (habitaciones, clientes y reservas activas)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creando datos de prueba para ventas...');
        $this->newLine();

        $seeder = new SalesTestDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('✅ Datos de prueba creados exitosamente!');
        $this->info('Ahora puedes probar el módulo de ventas.');
        $this->newLine();
        $this->info('Habitaciones ocupadas disponibles para ventas:');
        $this->info('  - Habitación 101');
        $this->info('  - Habitación 102');
        $this->info('  - Habitación 103');

        return Command::SUCCESS;
    }
}
