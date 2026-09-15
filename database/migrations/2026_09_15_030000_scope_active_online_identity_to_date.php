<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $reservations = DB::table('online_queue_reservations')
                ->whereIn('status', ['booked', 'checked_in'])
                ->whereNotNull('active_identity_key')
                ->orderBy('id')
                ->get(['id', 'service_date', 'nik_hash']);

            // Bebaskan indeks unik dahulu agar perubahan kunci dapat dilakukan aman.
            foreach ($reservations as $reservation) {
                DB::table('online_queue_reservations')->where('id', $reservation->id)->update([
                    'active_identity_key' => hash('sha256', 'temporary|'.$reservation->id),
                ]);
            }

            $seen = [];
            foreach ($reservations as $reservation) {
                $globalKey = hash('sha256', $reservation->service_date.'|'.$reservation->nik_hash);
                $identityKey = isset($seen[$globalKey])
                    ? hash('sha256', 'preserved-duplicate|'.$reservation->id.'|'.$globalKey)
                    : $globalKey;

                DB::table('online_queue_reservations')->where('id', $reservation->id)->update([
                    'active_identity_key' => $identityKey,
                ]);
                $seen[$globalKey] = true;
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $reservations = DB::table('online_queue_reservations')
                ->whereIn('status', ['booked', 'checked_in'])
                ->whereNotNull('active_identity_key')
                ->orderBy('id')
                ->get(['id', 'service_id', 'service_date', 'nik_hash']);

            foreach ($reservations as $reservation) {
                DB::table('online_queue_reservations')->where('id', $reservation->id)->update([
                    'active_identity_key' => hash('sha256', 'rollback|'.$reservation->id),
                ]);
            }

            foreach ($reservations as $reservation) {
                DB::table('online_queue_reservations')->where('id', $reservation->id)->update([
                    'active_identity_key' => hash('sha256', $reservation->service_id.'|'.$reservation->service_date.'|'.$reservation->nik_hash),
                ]);
            }
        });
    }
};
