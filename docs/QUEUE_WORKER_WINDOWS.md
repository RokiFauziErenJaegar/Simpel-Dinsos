# Queue Worker di Windows (XAMPP)

Setelah migrasi dispatch ke `SendOtpJob` & `SendApplicationNotificationJob` (true queue, bukan `afterResponse` lagi), aplikasi membutuhkan **queue worker** yang jalan terus untuk proses notifikasi outbound (OTP WA, notif pengajuan, surat selesai).

Tanpa worker, job menumpuk di tabel `jobs` dan **tidak pernah dikirim**.

---

## Opsi 1 — Manual via batch file (Quick)

Cara paling cepat: **double-click** [`start-queue-worker.bat`](../start-queue-worker.bat).

Window terminal akan terbuka & jalan `php artisan queue:work`. Biarkan terbuka selama aplikasi dipakai.
Tutup window untuk stop. Crash otomatis restart 5 detik kemudian.

> Cocok untuk dev / demo. **Tidak tahan reboot Windows**.

---

## Opsi 2 — Task Scheduler (Persistent, recommended)

Worker auto-start saat Windows boot & auto-restart kalau crash.

### Setup via PowerShell (Run as Administrator)

```powershell
$action = New-ScheduledTaskAction `
    -Execute "C:\xampp\htdocs\simpel-dinsos\start-queue-worker.bat"

$trigger = New-ScheduledTaskTrigger -AtStartup

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit (New-TimeSpan -Days 999)

$principal = New-ScheduledTaskPrincipal `
    -UserId "$env:USERNAME" `
    -LogonType S4U `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName "SimpelDinsos-QueueWorker" `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "SIMPEL DINSOS - php artisan queue:work (Windows boot)"
```

Untuk start sekarang juga (tanpa restart Windows):
```powershell
Start-ScheduledTask -TaskName "SimpelDinsos-QueueWorker"
```

Untuk verifikasi:
```powershell
Get-ScheduledTask -TaskName "SimpelDinsos-QueueWorker" | Select TaskName, State
```

Untuk uninstall:
```powershell
Unregister-ScheduledTask -TaskName "SimpelDinsos-QueueWorker" -Confirm:$false
```

### Setup via GUI (lebih simple)

1. Buka **Task Scheduler** (Win+R → `taskschd.msc`)
2. **Create Basic Task**
   - Name: `SimpelDinsos-QueueWorker`
   - Description: `php artisan queue:work`
3. **Trigger**: When the computer starts
4. **Action**: Start a program
   - Program: `C:\xampp\htdocs\simpel-dinsos\start-queue-worker.bat`
5. **Finish**, lalu klik task → **Properties**:
   - Tab **General**: pilih "Run whether user is logged on or not" + "Run with highest privileges"
   - Tab **Settings**: centang "If the task fails, restart every: 1 minute, Attempts: 999"
   - Tab **Conditions**: uncheck "Start the task only if computer is on AC power" (kalau di laptop)
6. Klik OK, masukkan password Windows kalau diminta.

---

## Verifikasi worker jalan

```bash
# Cek di tabel jobs — harusnya 0 (worker langsung consume)
cd C:\xampp\htdocs\simpel-dinsos
php artisan tinker --execute="echo DB::table('jobs')->count();"
```

Atau test live:
1. Submit OTP di `/masuk` → tabel jobs harus ada record sebentar lalu dihapus
2. WA harus masuk dalam ~5 detik

---

## Monitoring & Restart

### Cek log worker

Worker output ke terminal window-nya. Untuk monitoring background, redirect ke file:

Edit `start-queue-worker.bat`, tambah pada baris `php artisan queue:work...`:
```batch
php artisan queue:work --tries=3 --max-time=3600 --sleep=3 --backoff=10 >> storage\logs\queue.log 2>&1
```

Lalu lihat log:
```bash
type C:\xampp\htdocs\simpel-dinsos\storage\logs\queue.log
```

### Restart worker (kalau code changed)

```bash
cd C:\xampp\htdocs\simpel-dinsos
php artisan queue:restart
```

Ini kirim signal ke semua worker untuk graceful restart setelah job saat ini selesai. Penting setelah deploy code baru.

### Failed jobs

Kalau Fonnte API sempat down, job retry 3× lalu masuk `failed_jobs`.

```bash
# Lihat failed jobs
php artisan queue:failed

# Retry semua failed
php artisan queue:retry all

# Flush failed jobs (hapus)
php artisan queue:flush
```

---

## Troubleshooting

### "Cannot find php" saat batch jalan
PHP belum di PATH. Edit batch: ganti `php` dengan `C:\xampp\php\php.exe`.

### Job stuck di "RUNNING" forever
Worker crash di tengah job. Reset:
```bash
php artisan queue:retry all
```
Atau hapus row dengan `reserved_at IS NOT NULL` dari tabel `jobs`.

### OTP tetap tidak terkirim walau worker jalan
Cek:
1. `.env` punya `NOTIFICATION_DRIVER=fonnte` + `FONNTE_TOKEN`?
2. Token Fonnte valid? Test direct:
   ```bash
   curl -X POST https://api.fonnte.com/send \
     -H "Authorization: YOUR_TOKEN" \
     -F "target=628xxxxx" -F "message=test" -F "countryCode=62"
   ```
3. Cek `storage/logs/laravel.log` untuk error `[FONNTE] Gagal`.

### Worker pakai terlalu banyak RAM
Tambah `--max-jobs=500` ke command untuk auto-restart setelah 500 job.

---

🤖 Setelah setup, queue worker akan otomatis proses semua notifikasi outbound. Tidak perlu intervensi manual lagi.
