@echo off
REM SIMPEL DINSOS - Queue Worker untuk Windows
REM
REM Double-click file ini untuk start queue worker.
REM Worker akan jalan terus, proses job (OTP WA, notif submit, dll)
REM dari tabel jobs. Tutup window untuk stop.
REM
REM Untuk auto-start saat Windows boot, register sebagai Task Scheduler
REM dengan trigger "At startup". Lihat docs/QUEUE_WORKER_WINDOWS.md.

cd /d C:\xampp\htdocs\simpel-dinsos

echo ==========================================================
echo  SIMPEL DINSOS Queue Worker
echo ==========================================================
echo.
echo Memproses job dari tabel `jobs` setiap 3 detik.
echo Job yang ditangani:
echo   - SendOtpJob       (OTP login warga via WA)
echo   - SendApplicationNotificationJob (notif pengajuan, surat selesai)
echo.
echo Tutup window ini untuk STOP worker (atau Ctrl+C).
echo ==========================================================
echo.

:loop
php artisan queue:work --tries=3 --max-time=3600 --sleep=3 --backoff=10

echo.
echo [%date% %time%] Worker stopped/crashed. Restart in 5 detik...
timeout /t 5 /nobreak >nul
goto loop
