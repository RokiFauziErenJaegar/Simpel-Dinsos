<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Jobs\SendApplicationNotificationJob;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur "Dikembalikan → Perbaiki → Kirim Ulang".
 * DatabaseTransactions → semua perubahan di-rollback (aman di DB nyata).
 * Storage & Bus di-fake → tidak ada file/WA sungguhan.
 */
class ApplicationResubmitTest extends TestCase
{
    use DatabaseTransactions;

    protected function returnedApp(): Application
    {
        $app = Application::where('status', ApplicationStatus::Returned->value)
            ->whereHas('documents')
            ->with('documents')
            ->first();
        $this->assertNotNull($app, 'Butuh 1 pengajuan returned dengan dokumen di DB.');

        return $app;
    }

    public function test_pemilik_bisa_buka_halaman_perbaiki(): void
    {
        $app = $this->returnedApp();

        $this->actingAs(User::find($app->applicant_user_id))
            ->get(route('warga.application.fix', $app->code))
            ->assertOk()
            ->assertSee('Kirim Ulang')
            ->assertSee('Data Penerima Manfaat');
    }

    public function test_bukan_pemilik_kena_403(): void
    {
        $app = $this->returnedApp();
        $other = User::where('role', 'warga')->where('id', '!=', $app->applicant_user_id)->first();
        $this->assertNotNull($other);

        $this->actingAs($other)
            ->get(route('warga.application.fix', $app->code))
            ->assertForbidden();
    }

    public function test_berkas_bermasalah_wajib_diunggah_ulang(): void
    {
        Storage::fake('secure');
        Bus::fake();
        $app = $this->returnedApp();
        $doc = $app->documents->first();
        $doc->update(['is_validated' => false, 'notes' => 'Buram']);

        // Submit tanpa mengganti berkas bermasalah → tertolak.
        $this->actingAs(User::find($app->applicant_user_id))
            ->from(route('warga.application.fix', $app->code))
            ->post(route('warga.application.fix.submit', $app->code), [
                'beneficiary_name' => $app->beneficiary_name,
                'beneficiary_relation' => $app->beneficiary_relation,
            ])
            ->assertSessionHasErrors('replace_docs');

        $this->assertSame(ApplicationStatus::Returned->value, $app->fresh()->status->value);
        Bus::assertNotDispatched(SendApplicationNotificationJob::class);
    }

    public function test_kirim_ulang_dengan_berkas_pengganti_berhasil(): void
    {
        Storage::fake('secure');
        Bus::fake();
        $app = $this->returnedApp();
        $doc = $app->documents->first();
        $doc->update(['is_validated' => false, 'notes' => 'Buram']);

        $this->actingAs(User::find($app->applicant_user_id))
            ->post(route('warga.application.fix.submit', $app->code), [
                'beneficiary_name' => $app->beneficiary_name,
                'beneficiary_relation' => $app->beneficiary_relation,
                'replace_docs' => [
                    $doc->id => UploadedFile::fake()->create('ktp-baru.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('cek-status.index', ['code' => $app->code]));

        $fresh = $app->fresh(['documents', 'logs']);
        $this->assertSame(ApplicationStatus::Submitted->value, $fresh->status->value);
        $this->assertTrue($fresh->logs->contains('action', 'resubmitted'));
        $this->assertNull($fresh->documents->firstWhere('id', $doc->id)->is_validated);
        Bus::assertDispatched(SendApplicationNotificationJob::class);
    }
}
