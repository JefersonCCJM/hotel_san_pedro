<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            // SupplierSeeder::class,
            // ProductSeeder::class,
            // CustomerSeeder::class,
            DianIdentificationDocumentSeeder::class,
            DianLegalOrganizationSeeder::class,
            DianCustomerTributeSeeder::class,
            DianDocumentTypeSeeder::class,
            DianOperationTypeSeeder::class,
            DianPaymentMethodSeeder::class,
            DianPaymentFormSeeder::class,
            DianProductStandardSeeder::class,
            ReservationStatusSeeder::class,
            ReservationSourceSeeder::class,
            StayStatusSeeder::class,
            StaySourceSeeder::class,
            PaymentTypeSeeder::class,
            PaymentSourceSeeder::class,
            RoomCleaningTypeSeeder::class,
            RoomCleaningSourceSeeder::class,
            RoomStatusHistoryStatusSeeder::class,
            RoomStatusHistorySourceSeeder::class,
            RoomMaintenanceBlockStatusSeeder::class,
            RoomMaintenanceBlockSourceSeeder::class,
            VentilationTypeSeeder::class,
            RoomTypeSeeder::class
        ]);
    }
}
