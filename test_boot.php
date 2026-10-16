<?php
require_once "bootstrap/app.php";
echo "Bootstrap successful\n";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
echo "Kernel loaded\n";
