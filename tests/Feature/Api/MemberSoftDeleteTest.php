<?php

namespace Tests\Feature\Api;

use App\Models\MemberCategory;
use App\Services\MemberService;
use PHPUnit\Framework\Attributes\Test;

class MemberSoftDeleteTest extends ApiTestCase
{
    #[Test]
    public function deleting_a_member_archives_the_row_instead_of_removing_it(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $this->actingAs($pengurus)
            ->deleteJson("/api/v1/members/{$member->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('members', ['id' => $member->id]);
    }

    #[Test]
    public function archived_members_are_hidden_by_default_and_listed_with_the_trashed_filter(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $this->actingAs($pengurus)->deleteJson("/api/v1/members/{$member->id}")->assertStatus(200);

        $this->actingAs($pengurus)->getJson('/api/v1/members')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($pengurus)->getJson('/api/v1/members?trashed=1')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $member->id);
    }

    #[Test]
    public function archived_member_can_be_restored(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $this->actingAs($pengurus)->deleteJson("/api/v1/members/{$member->id}")->assertStatus(200);
        $this->actingAs($pengurus)->patchJson("/api/v1/members/{$member->id}/restore")->assertStatus(200);

        $this->assertNotSoftDeleted('members', ['id' => $member->id]);
        $this->assertSame('aktif', $member->fresh()->status);
    }

    #[Test]
    public function member_code_is_not_reused_after_an_archive(): void
    {
        $this->seedCore();

        $user = $this->makeUserWithMember('petugas')[0];
        $category = MemberCategory::create(['name' => 'rumah']);
        $service = app(MemberService::class);

        $first = $service->createMember([
            'user_id' => $user->id,
            'member_category_id' => $category->id,
            'name' => 'Anggota Satu',
        ]);

        $service->deleteMember($first->id);

        $second = $service->createMember([
            'user_id' => $user->id,
            'member_category_id' => $category->id,
            'name' => 'Anggota Dua',
        ]);

        // Kode lama tidak boleh dipakai ulang walau barisnya sudah diarsipkan —
        // kalau dipakai ulang, insert-nya menabrak unique constraint.
        $this->assertNotSame($first->member_code, $second->member_code);
        $this->assertStringEndsWith('0002', $second->member_code);
    }

    #[Test]
    public function petugas_cannot_archive_members(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $this->actingAs($petugas)
            ->deleteJson("/api/v1/members/{$member->id}")
            ->assertStatus(403);

        $this->assertNotSoftDeleted('members', ['id' => $member->id]);
    }
}
