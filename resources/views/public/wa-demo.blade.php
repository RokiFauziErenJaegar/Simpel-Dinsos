@extends('layouts.public')
@section('title', 'WhatsApp Bot Demo')
@section('content')

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">
    <h1 class="text-2xl font-bold text-slate-900">🤖 Simulator WhatsApp Bot</h1>
    <p class="text-slate-600 text-sm mt-1">Coba percakapan dengan bot Dinsos Pringsewu seperti dari WhatsApp. State percakapan disimpan di cache server selama 10 menit.</p>

    <div class="mt-6 card-elev overflow-hidden" x-data="waBot()" x-init="init()">
        <div class="bg-emerald-600 text-white px-5 py-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">🤖</div>
            <div>
                <div class="font-semibold">Dinsos Pringsewu Bot</div>
                <div class="text-xs text-white/80">Aktif sekarang · WhatsApp Business</div>
            </div>
        </div>

        <div id="chat" class="bg-[#e5ddd5] bg-opacity-50 p-4 h-[500px] overflow-y-auto space-y-3" x-ref="chat">
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                        ? 'bg-emerald-100 text-slate-900 rounded-2xl rounded-tr-sm px-4 py-2 max-w-[80%] whitespace-pre-wrap text-sm shadow'
                        : 'bg-white text-slate-900 rounded-2xl rounded-tl-sm px-4 py-2 max-w-[80%] whitespace-pre-wrap text-sm shadow'">
                        <span x-html="formatText(msg.text)"></span>
                        <div class="text-[10px] text-slate-400 text-right mt-1" x-text="msg.time"></div>
                    </div>
                </div>
            </template>
            <div x-show="loading" class="flex justify-start">
                <div class="bg-white rounded-2xl px-4 py-2 text-sm shadow">
                    <span class="inline-block animate-pulse">●●●</span>
                </div>
            </div>
        </div>

        <form @submit.prevent="send" class="bg-white border-t border-slate-200 p-3 flex gap-2">
            <input x-model="phone" type="text" placeholder="08xxxxxxxxxx" class="w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <input x-model="input" type="text" placeholder="Ketik pesan…" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" autofocus>
            <button type="submit" class="btn-primary px-4 py-2 text-sm" :disabled="loading">Kirim</button>
        </form>
    </div>

    <div class="mt-6 grid md:grid-cols-2 gap-4">
        <div class="card-elev p-4 text-sm">
            <div class="font-semibold mb-2">💡 Coba perintah ini:</div>
            <ul class="space-y-1 text-slate-600">
                <li>• Ketik <code class="bg-slate-100 px-1 rounded">halo</code> → menu utama</li>
                <li>• <code class="bg-slate-100 px-1 rounded">1</code> → cek status, lalu kirim kode (mis. <code class="bg-slate-100 px-1 rounded">SURAT-2026-0003</code>)</li>
                <li>• <code class="bg-slate-100 px-1 rounded">2</code> → buat aduan</li>
                <li>• <code class="bg-slate-100 px-1 rounded">3</code> → daftar 16 layanan</li>
                <li>• <code class="bg-slate-100 px-1 rounded">0</code> atau <code class="bg-slate-100 px-1 rounded">menu</code> → kembali</li>
            </ul>
        </div>
        <div class="card-elev p-4 text-sm">
            <div class="font-semibold mb-2">🔌 Pasang gateway nyata:</div>
            <ol class="space-y-1 text-slate-600 list-decimal pl-4">
                <li>Beli token Fonnte / Wablas</li>
                <li>Set <code class="bg-slate-100 px-1 rounded">NOTIFICATION_DRIVER=fonnte</code></li>
                <li>Set webhook URL ke <code class="bg-slate-100 px-1 rounded">{{ route('webhook.wa') }}</code></li>
                <li>Set <code class="bg-slate-100 px-1 rounded">WEBHOOK_TOKEN</code> untuk verifikasi</li>
            </ol>
        </div>
    </div>
</section>

<script>
function waBot() {
    return {
        phone: '081234567890',
        input: '',
        messages: [],
        loading: false,

        init() {
            this.appendBot('👋 *Selamat datang di simulator!*\nKetik *halo* atau angka 1-4 untuk memulai.');
        },
        formatText(text) {
            // *bold*, _italic_, escape HTML
            const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return escaped
                .replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
                .replace(/_([^_]+)_/g, '<em>$1</em>')
                .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 underline">$1</a>');
        },
        appendUser(text) {
            this.messages.push({role: 'user', text, time: new Date().toLocaleTimeString('id-ID').slice(0, 5)});
            this.scrollDown();
        },
        appendBot(text) {
            this.messages.push({role: 'bot', text, time: new Date().toLocaleTimeString('id-ID').slice(0, 5)});
            this.scrollDown();
        },
        scrollDown() {
            this.$nextTick(() => {
                const chat = this.$refs.chat;
                chat.scrollTop = chat.scrollHeight;
            });
        },
        async send() {
            if (! this.input.trim()) return;
            const text = this.input;
            this.appendUser(text);
            this.input = '';
            this.loading = true;

            try {
                const res = await fetch('/webhook/wa/simulate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({sender: this.phone, message: text}),
                });
                const data = await res.json();
                setTimeout(() => {
                    this.appendBot(data.reply || '(tidak ada balasan)');
                    this.loading = false;
                }, 400);
            } catch (e) {
                this.appendBot('⚠ Koneksi gagal: ' + e.message);
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
