<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Pickup;

return new class extends Migration
{
    /**
     * Tabel receipts untuk menyimpan data nota timbang secara terpisah
     * lengkap dengan snapshot objek (items_payload & nota_data) agar
     * data nota 100% frozen dan terisolasi dari perubahan harga sampah atau data master.
     */
    public function up(): void
    {
        if (!Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pickup_id')->unique()->constrained('pickups')->onDelete('cascade');
                $table->string('receipt_number', 50)->unique();
                $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('set null');
                $table->foreignId('officer_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('member_name');
                $table->string('member_code');
                $table->string('officer_name')->nullable();
                $table->string('location_type')->default('gudang');
                $table->string('location_label')->nullable();
                $table->json('items_payload');
                $table->json('nota_data')->nullable();
                $table->decimal('total_gross', 15, 2)->default(0);
                $table->decimal('total_fee', 15, 2)->default(0);
                $table->decimal('total_net', 15, 2)->default(0);
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();
            });
        }

        // Backfill data untuk semua pickup berstatus 'selesai' yang belum punya nota
        $existingPickupIds = DB::table('receipts')->pluck('pickup_id')->toArray();

        $completedPickups = Pickup::with(['member', 'officer', 'items.category', 'items.transaction'])
            ->where('status', 'selesai')
            ->whereNotIn('id', $existingPickupIds)
            ->get();

        foreach ($completedPickups as $p) {
            $code = $p->items->first()?->transaction?->transaction_code
                ?? ('NOTA-' . ($p->completed_at ? $p->completed_at->format('Ymd') : date('Ymd')) . '-' . str_pad($p->id, 5, '0', STR_PAD_LEFT));

            $locationLabel = match ($p->location_type) {
                'jemput_rumah' => 'Dijemput di Rumah',
                'jemput_pasar' => 'Dijemput di Pasar',
                default => 'Diantar ke Gudang',
            };

            $itemsPayload = $p->items->map(function ($i) {
                $qty = (float) ($i->weight_kg > 0 ? $i->weight_kg : $i->unit_count);
                $price = (float) ($i->price_per_unit > 0
                    ? $i->price_per_unit
                    : ($qty > 0 ? round(((float) $i->total_value) / $qty, 2) : 0));

                return [
                    'category_id' => $i->category_id,
                    'name' => $i->category?->name ?? 'Sampah',
                    'type' => $i->category?->type ?? 'umum',
                    'unit' => $i->category?->unit ?? 'kg',
                    'weight' => $qty,
                    'price' => $price,
                    'total' => (float) $i->total_value,
                ];
            })->values()->toArray();

            $notaData = [
                'receipt_number' => $code,
                'issued_at' => ($p->completed_at ?? $p->created_at)?->toIso8601String(),
                'member' => [
                    'id' => $p->member_id,
                    'name' => $p->member?->name ?? 'Anggota',
                    'code' => $p->member?->member_code ?? '-',
                ],
                'officer' => [
                    'id' => $p->officer_id,
                    'name' => $p->officer?->name ?? '-',
                ],
                'location' => $locationLabel,
                'items' => $itemsPayload,
                'total_gross' => (float) $p->total_gross,
                'total_fee' => (float) $p->total_fee,
                'total_net' => (float) $p->total_net,
            ];

            DB::table('receipts')->insert([
                'pickup_id' => $p->id,
                'receipt_number' => $code,
                'member_id' => $p->member_id,
                'officer_id' => $p->officer_id,
                'member_name' => $p->member?->name ?? 'Anggota',
                'member_code' => $p->member?->member_code ?? '-',
                'officer_name' => $p->officer?->name,
                'location_type' => $p->location_type ?? 'gudang',
                'location_label' => $locationLabel,
                'items_payload' => json_encode($itemsPayload),
                'nota_data' => json_encode($notaData),
                'total_gross' => $p->total_gross,
                'total_fee' => $p->total_fee,
                'total_net' => $p->total_net,
                'issued_at' => $p->completed_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
