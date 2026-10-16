<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$resp = $kernel->handle(Illuminate\Http\Request::create('http://192.168.1.122:8000/admin/login', 'GET'));
$content = $resp->getContent();

preg_match_all('/<button[^>]*>.*?<\/button>/s', $content, $matches);
echo "BUTTON 2:\n" . $matches[0][2] . "\n\n";

