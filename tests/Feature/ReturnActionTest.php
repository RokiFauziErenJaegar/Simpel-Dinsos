<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Filament\Admin\Resources\Applications\Pages\ListApplications;
use App\Jobs\SendApplicationNotificationJob;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Aksi "Kembalikan" petugas. DatabaseTransactions + Bus::fake → aman, tanpa WA.
 */
class ReturnActionTest extends TestCase
{
    use DatabaseTransactions;

    protected function nonFinalAppWithDocs(): Application
    {
        $app = Application::whereHas('documents')
            ->whereNotIn('status', ['completed', 'rejected', 'returned'])
            ->with('documents')
            ->first();
        $this->assertNotNull($app, 'Butuh 1 pengajuan non-final dengan dokumen.');

        return $app;
    }

    /** Logika penandaan berkas — deterministik, tanpa form harness. */
    public function test_apply_document_review_menandai_berkas(): void
    {
        $app = $this->nonFinalAppWithDocs();
        $doc = $app->documents->first();

        $app->applyDocumentReview($app->documents->map(fn ($d) => [
            'document_id' => (string) $d->id,
            'invalid' => $d->id === $doc->id,
            'note' => $d->id === $doc->id ? 'Buram, ulangi' : null,
        ])->all());

        $fresh = $app->fresh('documents');
        $flagged = $fresh->documents->firstWhere('id', $doc->id);
        $this->assertFalse($flagged->is_validated);
        $this->assertSame('Buram, ulangi', $flagged->notes);

        foreach ($fresh->documents->where('id', '!=', $doc->id) as $ok) {
            $this->assertTrue($ok->is_validated, "Berkas {$ok->id} seharusnya valid.");
        }
    }

    /** Aksi Filament: modal (Repeater seeded) ter-mount & submit tanpa error. */
    public function test_aksi_kembalikan_submit_tanpa_error(): void
    {
        Bus::fake();
        $app = $this->nonFinalAppWithDocs();
        $petugas = User::whereIn('role', ['petugas', 'admin'])->where('is_active', true)->first();

        Livewire::actingAs($petugas)
            ->test(ListApplications::class)
            ->mountTableAction('return', $app)
            ->setTableActionData(['notes' => 'Mohon perbaiki berkas.'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame(ApplicationStatus::Returned->value, $app->fresh()->status->value);
        Bus::assertDispatched(SendApplicationNotificationJob::class);
    }
}
