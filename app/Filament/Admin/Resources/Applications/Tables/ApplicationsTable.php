<?php

namespace App\Filament\Admin\Resources\Applications\Tables;

use App\Enums\ApplicationStatus;
use App\Jobs\SendApplicationNotificationJob;
use App\Models\Application;
use App\Models\ApplicationLog;
use App\Models\User;
use App\Services\DocumentGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('serviceType.code')
                    ->label('Layanan')
                    ->badge()
                    ->color('gray')
                    ->tooltip(fn ($record) => $record->serviceType?->name),

                TextColumn::make('beneficiary_name')
                    ->label('Penerima')
                    ->searchable()
                    ->description(fn ($record) => $record->beneficiary_nik ? 'NIK '.$record->beneficiary_nik : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ApplicationStatus ? $state->label() : (ApplicationStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof ApplicationStatus ? $state->color() : (ApplicationStatus::tryFrom($state)?->color() ?? 'gray')),

                TextColumn::make('currentHandler.name')
                    ->label('Ditangani')
                    ->placeholder('— belum —')
                    ->size('sm')
                    ->toggleable(),

                TextColumn::make('submitted_at')
                    ->label('Diajukan')
                    ->dateTime('d M H:i')
                    ->sortable(),

                TextColumn::make('sla_due_at')
                    ->label('Batas SLA')
                    ->formatStateUsing(function ($record) {
                        if (! $record->sla_due_at) {
                            return '—';
                        }
                        if ($record->status instanceof ApplicationStatus && $record->status->isFinal()) {
                            return $record->sla_due_at->translatedFormat('d M H:i');
                        }
                        $diff = now()->diffInMinutes($record->sla_due_at, false);
                        if ($diff < 0) {
                            return 'Lewat '.abs(round($diff)).' menit';
                        }
                        if ($diff < 60) {
                            return 'Sisa '.round($diff).' menit';
                        }

                        return 'Sisa '.round($diff / 60).' jam';
                    })
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                SelectFilter::make('service_type_id')
                    ->label('Jenis Layanan')
                    ->relationship('serviceType', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),

                Action::make('call')
                    ->label('Panggil')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->visible(function ($record) {
                        $ticket = $record->queueTicket;

                        return $ticket && $ticket->status === 'waiting';
                    })
                    ->schema([
                        Select::make('counter')
                            ->label('Loket')
                            ->options([
                                'LOKET 1' => 'Loket 1',
                                'LOKET 2' => 'Loket 2',
                                'LOKET 3' => 'Loket 3',
                                'LOKET INFO' => 'Loket Informasi',
                            ])
                            ->default('LOKET 1')
                            ->required(),
                    ])
                    ->action(function (Application $record, array $data) {
                        $ticket = $record->queueTicket;
                        if (! $ticket) {
                            return;
                        }
                        $ticket->callToCounter($data['counter'], auth()->id());
                        Notification::make()
                            ->success()
                            ->title('Antrian dipanggil')
                            ->body($ticket->ticket_number.' ke '.$data['counter'].'. Disuarakan di TV lobi.')
                            ->send();
                    }),

                Action::make('verify')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()?->role?->canVerifyApplication()
                        && in_array($record->status?->value ?? $record->status, ['submitted', 'in_verification']))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pengajuan & lanjutkan?')
                    ->action(function (Application $record) {
                        $from = $record->status?->value ?? $record->status;
                        DB::transaction(function () use ($record, $from) {
                            $record->update([
                                'status' => ApplicationStatus::InProcess->value,
                                'current_step' => 'pemrosesan',
                                'current_handler_id' => auth()->id(),
                            ]);
                            ApplicationLog::create([
                                'application_id' => $record->id,
                                'user_id' => auth()->id(),
                                'action' => 'verified',
                                'from_status' => $from,
                                'to_status' => ApplicationStatus::InProcess->value,
                                'notes' => 'Berkas diverifikasi & lanjut ke pemrosesan.',
                            ]);
                        });
                        // Notifikasi WA ke pemohon (setelah commit).
                        SendApplicationNotificationJob::dispatch($record->id, 'status');
                        Notification::make()->success()->title('Pengajuan disetujui')->send();
                    }),

                Action::make('return')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->visible(fn ($record) => auth()->user()?->role?->canVerifyApplication()
                        && ! in_array($record->status?->value ?? $record->status, ['completed', 'rejected', 'returned']))
                    ->modalHeading('Kembalikan pengajuan untuk diperbaiki')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Alasan umum dikembalikan')
                            ->required()
                            ->placeholder('Mis. Beberapa berkas tidak terbaca jelas, mohon unggah ulang.'),
                        Repeater::make('doc_review')
                            ->label('Tandai berkas yang tidak sesuai')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Berkas')
                            ->default(fn (Application $record) => $record->documents
                                ->map(fn ($d) => [
                                    'document_id' => (string) $d->id,
                                    'label' => $d->label,
                                    'invalid' => false,
                                    'note' => null,
                                ])->values()->all())
                            ->schema([
                                Hidden::make('document_id'),
                                Hidden::make('label'),
                                Toggle::make('invalid')->label('Tidak sesuai / perlu diperbaiki'),
                                TextInput::make('note')
                                    ->label('Catatan untuk pemohon')
                                    ->placeholder('Apa yang harus diperbaiki?'),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (Application $record, array $data) {
                        $from = $record->status?->value ?? $record->status;
                        DB::transaction(function () use ($record, $from, $data) {
                            $record->update(['status' => ApplicationStatus::Returned->value]);
                            // Tandai status validasi tiap berkas sesuai centang petugas.
                            $record->applyDocumentReview($data['doc_review'] ?? []);
                            ApplicationLog::create([
                                'application_id' => $record->id,
                                'user_id' => auth()->id(),
                                'action' => 'returned',
                                'from_status' => $from,
                                'to_status' => ApplicationStatus::Returned->value,
                                'notes' => $data['notes'],
                            ]);
                        });
                        // Notifikasi WA ke pemohon: status dikembalikan + alasan.
                        SendApplicationNotificationJob::dispatch($record->id, 'status', null, $data['notes']);
                        Notification::make()->success()->title('Pengajuan dikembalikan ke pemohon')->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()?->role?->canDecideApplication()
                        && ! in_array($record->status?->value ?? $record->status, ['completed', 'rejected']))
                    ->schema([
                        Textarea::make('reason')->label('Alasan penolakan')->required(),
                    ])
                    ->action(function (Application $record, array $data) {
                        $from = $record->status?->value ?? $record->status;
                        DB::transaction(function () use ($record, $from, $data) {
                            $record->update([
                                'status' => ApplicationStatus::Rejected->value,
                                'rejection_reason' => $data['reason'],
                                'completed_at' => now(),
                            ]);
                            ApplicationLog::create([
                                'application_id' => $record->id,
                                'user_id' => auth()->id(),
                                'action' => 'rejected',
                                'from_status' => $from,
                                'to_status' => ApplicationStatus::Rejected->value,
                                'notes' => $data['reason'],
                            ]);
                        });
                        // Notifikasi WA ke pemohon: status ditolak + alasan.
                        SendApplicationNotificationJob::dispatch($record->id, 'status', null, $data['reason']);
                        Notification::make()->danger()->title('Pengajuan ditolak')->send();
                    }),

                Action::make('issue')
                    ->label('Terbitkan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->visible(fn ($record) => auth()->user()?->role?->canIssueDocument()
                        && in_array($record->status?->value ?? $record->status, ['in_process', 'awaiting_signature']))
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan surat ber-QR & selesaikan pengajuan?')
                    ->modalDescription('Sistem akan membuat PDF resmi dengan kop, nomor surat, QR verifikasi, dan tanda tangan Kepala Dinas.')
                    ->action(function (Application $record) {
                        $from = $record->status?->value ?? $record->status;

                        // Pilih signer: Kadis kalau ada, fallback ke user saat ini
                        $signer = User::where('role', 'kadis')->first() ?? auth()->user();

                        $doc = app(DocumentGenerator::class)->issue($record, $signer);

                        DB::transaction(function () use ($record, $from, $doc) {
                            $record->update([
                                'status' => ApplicationStatus::Completed->value,
                                'current_step' => 'selesai',
                                'completed_at' => now(),
                            ]);
                            ApplicationLog::create([
                                'application_id' => $record->id,
                                'user_id' => auth()->id(),
                                'action' => 'completed',
                                'from_status' => $from,
                                'to_status' => ApplicationStatus::Completed->value,
                                'notes' => 'Surat diterbitkan: '.$doc->document_number,
                            ]);
                        });

                        // Push ke queue worker (lebih robust dari afterResponse)
                        SendApplicationNotificationJob::dispatch($record->id, 'completed', $doc->id);
                        SendApplicationNotificationJob::dispatch($record->id, 'survey');

                        Notification::make()
                            ->success()
                            ->title('Surat terbit: '.$doc->document_number)
                            ->body('PDF tersimpan & pemohon dinotifikasi.')
                            ->send();
                    }),

                Action::make('downloadSurat')
                    ->label('Unduh Surat')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ($record->status?->value ?? $record->status) === 'completed'
                        && $record->outputDocument !== null)
                    ->url(fn (Application $record) => $record->outputDocument
                        ? route('output.file', ['docId' => $record->outputDocument->id])
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
