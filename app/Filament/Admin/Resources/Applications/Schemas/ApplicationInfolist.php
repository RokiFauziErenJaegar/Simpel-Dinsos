<?php

namespace App\Filament\Admin\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ringkasan Pengajuan')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('code')->label('Kode')->copyable()->weight('bold'),
                    TextEntry::make('serviceType.name')->label('Jenis Layanan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof ApplicationStatus ? $state->label() : ApplicationStatus::tryFrom($state)?->label() ?? $state)
                        ->color(fn ($state) => $state instanceof ApplicationStatus ? $state->color() : (ApplicationStatus::tryFrom($state)?->color() ?? 'gray')),
                    TextEntry::make('beneficiary_name')->label('Penerima Manfaat'),
                    TextEntry::make('beneficiary_nik')->label('NIK')->placeholder('—'),
                    TextEntry::make('beneficiary_relation')->label('Hubungan'),
                    TextEntry::make('purpose')->label('Keperluan')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Pemohon')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('applicant.name')->label('Nama'),
                    TextEntry::make('applicant.phone')->label('HP'),
                    TextEntry::make('applicant.email')->label('Email'),
                ]),

            Section::make('Waktu')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('submitted_at')->label('Diajukan')->dateTime('d M Y H:i'),
                    TextEntry::make('sla_due_at')->label('Batas SLA')->dateTime('d M Y H:i'),
                    TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('—'),
                ]),

            Section::make('Berkas')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('documents')
                        ->label('')
                        ->schema([
                            TextEntry::make('label')->label('Berkas'),
                            TextEntry::make('original_name')->label('Nama File'),
                            TextEntry::make('file_path')
                                ->label('Lihat')
                                ->formatStateUsing(fn ($state) => $state ? 'Buka file' : '—')
                                ->url(fn ($record) => $record->file_path ? route('secure.file', ['docId' => $record->id]) : null, shouldOpenInNewTab: true),
                            IconEntry::make('is_validated')
                                ->label('Tervalidasi')
                                ->boolean(),
                        ])
                        ->columns(4),
                ]),

            Section::make('Dokumen Terbitan')
                ->columnSpanFull()
                ->columns(3)
                ->visible(fn ($record) => $record->outputDocument !== null)
                ->schema([
                    TextEntry::make('outputDocument.document_number')
                        ->label('Nomor Surat')
                        ->copyable()
                        ->weight('bold'),
                    TextEntry::make('outputDocument.signed_at')
                        ->label('Ditandatangani')
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('outputDocument.signedBy.name')
                        ->label('Penanda Tangan')
                        ->placeholder('—'),
                    TextEntry::make('outputDocument.file_path')
                        ->label('Surat Terbit')
                        ->badge()
                        ->color('success')
                        ->formatStateUsing(fn ($state) => $state ? '📄 Buka / Unduh PDF' : '—')
                        ->url(fn ($record) => $record->outputDocument
                            ? route('output.file', ['docId' => $record->outputDocument->id])
                            : null, shouldOpenInNewTab: true)
                        ->columnSpanFull(),
                ]),

            Section::make('Timeline / Log')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('logs')
                        ->label('')
                        ->schema([
                            TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                            TextEntry::make('action')->label('Aksi')->badge(),
                            TextEntry::make('user.name')->label('Oleh')->placeholder('Sistem'),
                            TextEntry::make('notes')->label('Catatan')->placeholder('—'),
                        ])
                        ->columns(4),
                ]),

            Section::make('Catatan Penolakan')
                ->visible(fn ($record) => $record->rejection_reason)
                ->schema([
                    TextEntry::make('rejection_reason')->label(''),
                ]),
        ]);
    }
}
