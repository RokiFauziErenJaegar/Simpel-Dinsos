<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class PushGenerateVapidCommand extends Command
{
    protected $signature = 'push:vapid-generate';

    protected $description = 'Generate VAPID key pair untuk Web Push notification';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID key pair berhasil di-generate.');
        $this->newLine();
        $this->line('Tambahkan baris berikut ke .env:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY="'.$keys['publicKey'].'"');
        $this->line('VAPID_PRIVATE_KEY="'.$keys['privateKey'].'"');
        $this->line('VAPID_SUBJECT="mailto:pringsewudinsos@gmail.com"');
        $this->newLine();
        $this->warn('Simpan private key dengan aman — jangan commit ke Git.');

        return self::SUCCESS;
    }
}
