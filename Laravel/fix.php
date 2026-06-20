<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\Asistencia::with('persona')->get() as $a) {
    if ($a->persona && $a->persona->turno) {
        $a->turno = $a->persona->turno;
        $a->save();
    }
}
echo "Turnos backfilled!";
