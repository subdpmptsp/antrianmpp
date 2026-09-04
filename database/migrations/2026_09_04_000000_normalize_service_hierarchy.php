<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instansis', function (Blueprint $table): void {
            $table->string('zone', 20)->nullable()->after('nama_instansi');
            $table->boolean('is_active')->default(true)->after('zone');
            $table->boolean('is_archived')->default(false)->after('is_active');
            $table->index(['zone', 'is_active', 'is_archived'], 'instansis_zone_operational_index');
        });

        $conflictingInstitutionIds = DB::table('counters')
            ->whereNotNull('instansi_id')
            ->where('is_archived', false)
            ->select('instansi_id')
            ->groupBy('instansi_id')
            ->havingRaw('COUNT(DISTINCT UPPER(TRIM(name))) > 1')
            ->pluck('instansi_id');

        if ($conflictingInstitutionIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Migrasi zona dihentikan. Instansi lintas zona ditemukan: '.$conflictingInstitutionIds->implode(', ')
            );
        }

        DB::statement(<<<'SQL'
            UPDATE instansis i
            INNER JOIN counters c ON c.id = i.counter_id
            SET i.zone = UPPER(TRIM(c.name))
            WHERE c.name IS NOT NULL AND TRIM(c.name) <> ''
        SQL);

        DB::statement(<<<'SQL'
            UPDATE instansis i
            INNER JOIN (
                SELECT instansi_id, MIN(UPPER(TRIM(name))) AS zone
                FROM counters
                WHERE instansi_id IS NOT NULL
                  AND name IS NOT NULL
                  AND TRIM(name) <> ''
                  AND is_archived = 0
                GROUP BY instansi_id
            ) c ON c.instansi_id = i.instansi_id
            SET i.zone = c.zone
            WHERE i.zone IS NULL
        SQL);

        // Instansi BPN II sudah digabung ke BPN utama. Simpan sebagai arsip agar
        // identitas historis tetap ada, tetapi tidak lagi masuk alur operasional.
        DB::table('instansis')
            ->where('instansi_id', 20)
            ->whereNull('zone')
            ->update([
                'zone' => 'ZONA 4',
                'is_active' => false,
                'is_archived' => true,
            ]);

        if (DB::table('instansis')->whereNull('zone')->exists()) {
            throw new \RuntimeException('Migrasi zona dihentikan karena masih ada instansi tanpa zona.');
        }

        // Rekonsiliasi data historis yang induknya pernah terhapus.
        foreach ([46 => 45, 50 => 39, 75 => 76] as $oldServiceId => $currentServiceId) {
            DB::table('queues')->where('service_id', $oldServiceId)->update(['service_id' => $currentServiceId]);
        }

        DB::table('attendances')->where('instansi_id', 30)->update(['instansi_id' => 27]);
        DB::table('counters')->whereIn('id', [108, 197])->update(['instansi_id' => 19]);
        DB::table('users')->where('id', 189)->update(['service_id' => 79]);

        // Record setup yang belum lengkap diamankan sebagai arsip/nonaktif.
        DB::table('counters')
            ->whereNull('service_id')
            ->where('is_active', true)
            ->update(['is_active' => false, 'is_archived' => true]);

        DB::table('users')
            ->where('role', 'operator')
            ->whereNull('counter_id')
            ->update(['is_active' => false]);

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE services DROP FOREIGN KEY services_instansi_id_foreign');
        DB::statement('ALTER TABLE services MODIFY instansi_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_instansi_id_foreign FOREIGN KEY (instansi_id) REFERENCES instansis(instansi_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE counters DROP FOREIGN KEY counters_instansi_id_foreign');
        DB::statement('ALTER TABLE counters MODIFY instansi_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE counters ADD CONSTRAINT counters_instansi_id_foreign FOREIGN KEY (instansi_id) REFERENCES instansis(instansi_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE services DROP FOREIGN KEY services_counter_id_foreign');
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('counter_id'));

        DB::statement('ALTER TABLE instansis DROP FOREIGN KEY instansis_counter_id_foreign');
        Schema::table('instansis', fn (Blueprint $table) => $table->dropColumn('counter_id'));
        DB::statement('ALTER TABLE instansis MODIFY zone VARCHAR(20) NOT NULL');

        // Lindungi data historis: parent tidak boleh dihapus selama masih direferensikan.
        DB::statement('ALTER TABLE queues ADD CONSTRAINT queues_service_id_foreign FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE queues ADD CONSTRAINT queues_counter_id_foreign FOREIGN KEY (counter_id) REFERENCES counters(id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE attendances DROP FOREIGN KEY attendances_instansi_id_foreign');
        DB::statement('ALTER TABLE attendances ADD CONSTRAINT attendances_instansi_id_foreign FOREIGN KEY (instansi_id) REFERENCES instansis(instansi_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_counter_id_foreign');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_counter_id_foreign FOREIGN KEY (counter_id) REFERENCES counters(id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_service_id_foreign');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_service_id_foreign FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE counters DROP FOREIGN KEY counters_service_id_foreign');
        DB::statement('ALTER TABLE counters ADD CONSTRAINT counters_service_id_foreign FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT');

        DB::statement("ALTER TABLE counters ADD CONSTRAINT counters_active_requires_service CHECK (is_active = 0 OR is_archived = 1 OR service_id IS NOT NULL)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_active_operator_requires_counter CHECK (role <> 'operator' OR is_active = 0 OR counter_id IS NOT NULL)");
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Normalisasi hierarki tidak mendukung rollback parsial. Pulihkan backup database sebelum migrasi untuk menjaga data historis.'
        );
    }
};
