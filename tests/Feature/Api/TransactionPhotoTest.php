<?php

namespace Tests\Feature\Api;

use App\Models\FinanceCategory;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class TransactionPhotoTest extends ApiTestCase
{
    #[Test]
    public function income_can_be_recorded_without_a_photo(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        $category = FinanceCategory::where('name', 'Penjualan Sampah')->first();

        $this->actingAs($pengurus)->postJson('/api/v1/transactions', [
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 150000,
            'payment_method' => 'tunai',
        ])->assertStatus(201)->assertJsonPath('data.photo_url', null);
    }

    #[Test]
    public function expense_photo_is_stored_and_returned_as_url(): void
    {
        $this->seedCore();
        Storage::fake('public');
        [$pengurus] = $this->makeUserWithMember('pengurus');
        $category = FinanceCategory::where('name', 'Biaya Operasional Lapangan')->first();

        $response = $this->actingAs($pengurus)->post('/api/v1/transactions', [
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'payment_method' => 'tunai',
            'photo' => UploadedFile::fake()->image('nota.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.photo_url'));

        // Path-nya nyata ada di disk public, bukan cuma terisi di kolom.
        $transaction = Transaction::latest('id')->first();
        $this->assertNotNull($transaction->photo_path);
        Storage::disk('public')->assertExists($transaction->photo_path);
    }

    #[Test]
    public function non_image_upload_is_rejected(): void
    {
        $this->seedCore();
        Storage::fake('public');
        [$pengurus] = $this->makeUserWithMember('pengurus');
        $category = FinanceCategory::where('name', 'Biaya Operasional Lapangan')->first();

        $this->actingAs($pengurus)->post('/api/v1/transactions', [
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'payment_method' => 'tunai',
            'photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('photo');
    }
}
