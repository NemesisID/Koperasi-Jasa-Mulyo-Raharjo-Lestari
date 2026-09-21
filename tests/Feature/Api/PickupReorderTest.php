<?php

namespace Tests\Feature\Api;

use App\Services\TrashWeighingService;
use PHPUnit\Framework\Attributes\Test;

class PickupReorderTest extends ApiTestCase
{
    /** Buat 3 tiket urut lewat service — sekalian memastikan sort_order di-append. */
    private function makeThreeTickets(): array
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [$anggota, $member] = $this->makeUserWithMember('anggota');
        $service = app(TrashWeighingService::class);

        $tickets = [];
        foreach ([1, 2, 3] as $i) {
            $tickets[] = $service->createTicket(
                ['member_id' => $member->id, 'location_type' => 'gudang', 'notes' => "tiket {$i}"],
                $pengurus,
            );
        }

        return [$pengurus, $anggota, $member, $tickets];
    }

    #[Test]
    public function sending_ids_reversed_returns_pickups_in_the_new_order(): void
    {
        [$pengurus, , , $tickets] = $this->makeThreeTickets();
        $reversed = collect($tickets)->pluck('id')->reverse()->values()->all();

        $this->actingAs($pengurus)
            ->patchJson('/api/v1/pickups/reorder', ['ids' => $reversed])
            ->assertStatus(200);

        // Tiket 3 kini di depan, tiket 1 di belakang.
        $this->assertSame(
            $reversed,
            collect($this->actingAs($pengurus)->getJson('/api/v1/pickups')->json('data'))
                ->pluck('id')->all(),
        );
    }

    #[Test]
    public function new_ticket_is_appended_after_the_reordered_queue(): void
    {
        [$pengurus, , $member, $tickets] = $this->makeThreeTickets();
        $reversed = collect($tickets)->pluck('id')->reverse()->values()->all();

        $this->actingAs($pengurus)->patchJson('/api/v1/pickups/reorder', ['ids' => $reversed]);

        $this->actingAs($pengurus)->postJson('/api/v1/pickups', [
            'member_id' => $member->id,
            'location_type' => 'gudang',
        ])->assertStatus(201);

        // Tiket baru masuk ekor antrean, bukan menimpa urutan hasil drag-drop.
        $ids = collect($this->actingAs($pengurus)->getJson('/api/v1/pickups')->json('data'))
            ->pluck('id')->all();
        $this->assertSame($reversed, array_slice($ids, 0, 3));
        $this->assertCount(4, $ids);
    }

    #[Test]
    public function ids_not_sent_keep_their_order(): void
    {
        [$pengurus, , , $tickets] = $this->makeThreeTickets();
        [$a, $b, $c] = $tickets;

        // Hanya dua tiket pertama ditukar; tiket ketiga tidak disentuh.
        $this->actingAs($pengurus)
            ->patchJson('/api/v1/pickups/reorder', ['ids' => [$b->id, $a->id]])
            ->assertStatus(200);

        $this->assertSame($b->fresh()->sort_order + 1, $a->fresh()->sort_order);
        $this->assertSame(3, $c->fresh()->sort_order);
    }

    #[Test]
    public function unknown_or_duplicate_ids_are_rejected(): void
    {
        [$pengurus, , , $tickets] = $this->makeThreeTickets();
        $ids = collect($tickets)->pluck('id')->all();

        $this->actingAs($pengurus)
            ->patchJson('/api/v1/pickups/reorder', ['ids' => [999999]])
            ->assertStatus(422);

        $this->actingAs($pengurus)
            ->patchJson('/api/v1/pickups/reorder', ['ids' => [$ids[0], $ids[0]]])
            ->assertStatus(422);
    }

    #[Test]
    public function anggota_cannot_reorder(): void
    {
        $this->seedCore();
        [$anggota] = $this->makeUserWithMember('anggota');

        $this->actingAs($anggota)
            ->patchJson('/api/v1/pickups/reorder', ['ids' => [1]])
            ->assertStatus(403);
    }
}
