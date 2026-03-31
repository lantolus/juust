<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metóda nie je povolená.']);
    exit;
}

$prijemca       = 'tvoj@juust.sk';
$odosielatel    = 'noreply@juust.sk';
$predmet_prefix = '[Juust Web] ';

if (!empty($_POST['website'])) {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// 2. RATE LIMITING (Max 3 správy za hodinu z jednej IP)
$ip      = $_SERVER['REMOTE_ADDR'];
$logDir  = __DIR__ . '/logs_contact';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
    file_put_contents($logDir . '/.htaccess', "Deny from all");
}

$lockFile = $logDir . '/rl_' . md5($ip) . '.json';
$limit    = 3;
$window   = 3600; // 1 hodina

$attempts = [];
if (file_exists($lockFile)) {
    $attempts = json_decode(file_get_contents($lockFile), true) ?: [];
}

$now      = time();
$attempts = array_filter($attempts, function($t) use ($now, $window) {
    return $now - $t < $window;
});

if (count($attempts) >= $limit) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Príliš veľa pokusov. Skúste to prosím o hodinu.']);
    exit;
}

$meno    = trim(strip_tags($_POST['meno']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$telefon = trim(strip_tags($_POST['telefon'] ?? ''));
$tema    = trim(strip_tags($_POST['tema']    ?? 'Všeobecný dotaz'));
$sprava  = trim(strip_tags($_POST['sprava']  ?? ''));

if (empty($meno) || empty($email) || empty($sprava)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vyplňte prosím všetky povinné polia.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Zadajte platnú emailovú adresu.']);
    exit;
}

if (mb_strlen($sprava) > 1500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Správa je príliš dlhá.']);
    exit;
}

foreach ([$meno, $email, $telefon, $tema] as $pole) {
    if (preg_match('/[\r\n]/', $pole)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Neplatné znaky v poliach.']);
        exit;
    }
}

$predmet = mb_encode_mimeheader($predmet_prefix . $tema . ' — ' . $meno, 'UTF-8');

$telo  = "Nová správa z kontaktného formulára na juust.sk\n";
$telo .= "------------------------------------------------\n\n";
$telo .= "Od:       $meno\n";
$telo .= "Email:    $email\n";
$telo .= "Telefón:  " . ($telefon ?: 'neuvedené') . "\n";
$telo .= "Téma:     $tema\n\n";
$telo .= "Správa:\n$sprava\n\n";
$telo .= "------------------------------------------------\n";
$telo .= "Odoslané z IP: $ip\n";

$hlavicky  = "From: Juust Web <{$odosielatel}>\r\n";
$hlavicky .= "Reply-To: {$meno} <{$email}>\r\n";
$hlavicky .= "MIME-Version: 1.0\r\n";
$hlavicky .= "Content-Type: text/plain; charset=UTF-8\r\n";
$hlavicky .= "Content-Transfer-Encoding: 8bit\r\n";
$hlavicky .= "X-Mailer: PHP/" . phpversion();

$odoslane = mail($prijemca, $predmet, $telo, $hlavicky);

if ($odoslane) {
    $attempts[] = $now;
    file_put_contents($lockFile, json_encode(array_values($attempts)));

    echo json_encode(['success' => true, 'message' => 'Správa bola úspešne odoslaná.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Chyba servera pri odosielaní. Skúste to neskôr.']);
}