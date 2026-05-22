<?php

use App\Models\Application;
use App\Models\User;
use App\Services\DocumentGenerator;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$application = Application::where('code', 'SURAT-2026-0002')->firstOrFail();
$kadis = User::where('email', 'kadis@dinsospringsewu.test')->firstOrFail();

$doc = app(DocumentGenerator::class)->issue($application, $kadis);

echo "OK: doc_number={$doc->document_number}\n";
echo "    file_path={$doc->file_path}\n";
echo "    verify_url=".route('document.verify', ['token' => $doc->verification_token])."\n";
echo "    full_path=".storage_path('app/public/'.$doc->file_path)."\n";
echo "    size=".filesize(storage_path('app/public/'.$doc->file_path))." bytes\n";
