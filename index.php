<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/WebsiteCompanyName.php';

header('Content-type:application/json;charset=utf-8');

if (!empty($_GET['url'])) {
    // print_r( (new WebsiteCompanyName($_GET['url']))->guesses );
    echo( (new WebsiteCompanyName($_GET['url'])) );
} else {
    echo json_encode([
        'error' => "Please provide a URL"
    ]);
}
