<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'gann.gann87@gmail.com')->first();
if ($user) {
    echo "Envoi du Job pour {$user->email}...\n";
    App\Jobs\RecalculateMatchesJob::dispatch($user);
    echo "Job envoyé !\n";
} else {
    echo "Utilisateur non trouvé.\n";
}
