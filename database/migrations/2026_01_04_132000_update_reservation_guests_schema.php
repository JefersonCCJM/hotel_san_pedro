<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            // Nueva FK hacia reservation_rooms
            if (!Schema::hasColumn('reservation_guests', 'reservation_room_id')) {
                $table->unsignedBigInteger('reservation_room_id')->nullable()->after('id');
            }

            // Nueva FK hacia clientes (tabla customers ya existente)
            if (!Schema::hasColumn('reservation_guests', 'guest_id')) {
                $table->unsignedBigInteger('guest_id')->nullable()->after('reservation_room_id');
            }

            if (!Schema::hasColumn('reservation_guests', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('guest_id');
            }
        });

        // Backfill: mover datos de las columnas antiguas
        if (Schema::hasColumn('reservation_guests', 'reservation_id')) {
            // Mapear al primer reservation_room que coincida con la reserva
            DB::statement(<<<SQL
UPDATE reservation_guests rg
LEFT JOIN reservation_rooms rr ON rr.reservation_id = rg.reservation_id
SET rg.reservation_room_id = rr.id
WHERE rg.reservation_room_id IS NULL
SQL);
        }

        if (Schema::hasColumn('reservation_guests', 'customer_id')) {
            DB::statement('UPDATE reservation_guests SET guest_id = customer_id WHERE guest_id IS NULL');
        }

        // Agregar llaves foraneas evitando duplicados
        $fkRoom = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_guests' AND CONSTRAINT_NAME = 'reservation_guests_reservation_room_id_foreign' LIMIT 1");
        if ($fkRoom) {
            DB::statement('ALTER TABLE reservation_guests DROP FOREIGN KEY reservation_guests_reservation_room_id_foreign');
        }

        $fkGuest = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_guests' AND CONSTRAINT_NAME = 'reservation_guests_guest_id_foreign' LIMIT 1");
        if ($fkGuest) {
            DB::statement('ALTER TABLE reservation_guests DROP FOREIGN KEY reservation_guests_guest_id_foreign');
        }

        Schema::table('reservation_guests', function (Blueprint $table) {
            if (Schema::hasColumn('reservation_guests', 'reservation_room_id')) {
                $table->foreign('reservation_room_id')->references('id')->on('reservation_rooms')->onDelete('cascade');
            }
            if (Schema::hasColumn('reservation_guests', 'guest_id')) {
                $table->foreign('guest_id')->references('id')->on('customers')->onDelete('cascade');
            }
        });

        // Eliminar columnas antiguas
        Schema::table('reservation_guests', function (Blueprint $table) {
            // Quitar FKs previas si existían - verificar nombres específicos
            $reservationIdFk = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_guests' AND CONSTRAINT_NAME LIKE '%reservation_id%' AND REFERENCED_TABLE_NAME = 'reservations' LIMIT 1");
            if ($reservationIdFk) {
                $table->dropForeign($reservationIdFk->CONSTRAINT_NAME);
            }
            
            $customerIdFk = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation_guests' AND CONSTRAINT_NAME LIKE '%customer_id%' AND REFERENCED_TABLE_NAME = 'customers' LIMIT 1");
            if ($customerIdFk) {
                $table->dropForeign($customerIdFk->CONSTRAINT_NAME);
            }

            if (Schema::hasColumn('reservation_guests', 'reservation_id')) {
                $table->dropColumn('reservation_id');
            }
            if (Schema::hasColumn('reservation_guests', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            // Restaurar columnas antiguas
            $table->unsignedBigInteger('reservation_id')->nullable()->after('id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('reservation_id');
        });

        // Backfill inverso
        DB::statement('UPDATE reservation_guests SET reservation_id = reservation_room_id WHERE reservation_id IS NULL');
        DB::statement('UPDATE reservation_guests SET customer_id = guest_id WHERE customer_id IS NULL');

        Schema::table('reservation_guests', function (Blueprint $table) {
            // Restaurar FKs antiguas
            if (Schema::hasColumn('reservation_guests', 'reservation_id')) {
                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            }
            if (Schema::hasColumn('reservation_guests', 'customer_id')) {
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            }

            // Quitar columnas nuevas
            if (Schema::hasColumn('reservation_guests', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
            if (Schema::hasColumn('reservation_guests', 'guest_id')) {
                $table->dropForeign(['guest_id']);
                $table->dropColumn('guest_id');
            }
            if (Schema::hasColumn('reservation_guests', 'reservation_room_id')) {
                $table->dropForeign(['reservation_room_id']);
                $table->dropColumn('reservation_room_id');
            }
        });
    }
};
