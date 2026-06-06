<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Kirim Web Push notification real (Chrome/Firefox/Edge/Safari 16.4+)
 * menggunakan protokol Web Push + VAPID authentication.
 *
 * Subscription dibuat di browser via service worker, lalu disimpan ke
 * tabel `push_subscriptions`. Backend mengirim notification ke
 * push service provider (FCM / Mozilla / Apple) yang lalu di-deliver
 * ke device — bekerja bahkan saat browser tertutup.
 */
class WebPushService
{
    protected ?WebPush $client = null;

    protected function client(): ?WebPush
    {
        if ($this->client) {
            return $this->client;
        }

        $public = config('services.push.vapid_public_key');
        $private = config('services.push.vapid_private_key');
        if (! $public || ! $private) {
            Log::warning('[WebPush] VAPID key belum di-set, push diabaikan.');

            return null;
        }

        $this->client = new WebPush([
            'VAPID' => [
                'subject' => config('services.push.vapid_subject'),
                'publicKey' => $public,
                'privateKey' => $private,
            ],
        ]);
        $this->client->setDefaultOptions([
            'TTL' => 86400,
            'urgency' => 'normal',
        ]);

        return $this->client;
    }

    /**
     * Kirim notification ke seluruh subscription milik user.
     * Subscription expired (404/410) otomatis di-prune dari DB.
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null): int
    {
        $client = $this->client();
        if (! $client) {
            return 0;
        }

        $subs = PushSubscription::where('user_id', $user->id)->get();
        if ($subs->isEmpty()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? route('home'),
            'timestamp' => now()->toIso8601String(),
        ]);

        $sent = 0;
        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);
            $client->queueNotification($subscription, $payload);
        }

        foreach ($client->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $sent++;
            } else {
                Log::warning('[WebPush] gagal kirim: '.$report->getReason(), compact('endpoint'));
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $endpoint)->delete();
                }
            }
        }

        return $sent;
    }

    /** Broadcast ke semua subscription aktif (untuk pengumuman). */
    public function broadcast(string $title, string $body, ?string $url = null): int
    {
        $client = $this->client();
        if (! $client) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? route('home'),
            'timestamp' => now()->toIso8601String(),
        ]);

        $sent = 0;
        PushSubscription::chunk(100, function ($subs) use ($client, $payload, &$sent) {
            foreach ($subs as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh,
                    'authToken' => $sub->auth,
                ]);
                $client->queueNotification($subscription, $payload);
            }
            foreach ($client->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                }
            }
        });

        return $sent;
    }
}
