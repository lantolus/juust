<?php
// =====================
// send.php — kontaktný formulár
// =====================
// ANTI-SPAM NASTAVENIA:
// 1. Nastav SPF záznam na svojej doméne (v DNS):
//    TXT @ "v=spf1 include:váš-hosting.sk ~all"
// 2. "From" adresa MUSÍ byť z rovnakej domény ako web
//    (napr. noreply@juust.sk) — inak skončí v spame
// 3. Reply-To nastavíme na email návštevníka
// =====================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metóda nie je povolená.']);
    exit;
}

// ---- KONFIGURÁCIA — ZMEŇ TIETO HODNOTY ----
$prijemca      = 'tvoj@juust.sk';        // kam príde email
$odosielatel   = 'noreply@juust.sk';     // MUSÍ byť z tvojej domény
$predmet_prefix = '[Juust Web] ';
// -------------------------------------------

// ---- HONEYPOT anti-spam (skryté pole v HTML) ----
// Ak bot vyplní pole "website", zahod požiadavku
if (!empty($_POST['website'])) {
    http_response_code(200); // tvárime sa že ok, bot nesmie vedieť
    echo json_encode(['success' => true]);
    exit;
}

// ---- RATE LIMITING — max 3 správy za hodinu z jednej IP ----
$ip       = $_SERVER['REMOTE_ADDR'];
$lockFile = sys_get_temp_dir() . '/juust_rl_' . md5($ip) . '.json';
$limit    = 3;
$window   = 3600; // sekúnd

$attempts = [];
if (file_exists($lockFile)) {
    $attempts = json_decode(file_get_contents($lockFile), true) ?: [];
}
$now      = time();
$attempts = array_filter($attempts, fn($t) => $now - $t < $window);

if (count($attempts) >= $limit) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Príliš veľa pokusov. Skúste neskôr.']);
    exit;
}

// ---- VSTUPNÉ DÁTA ----
$meno    = trim(strip_tags($_POST['meno']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$telefon = trim(strip_tags($_POST['telefon'] ?? ''));
$tema    = trim(strip_tags($_POST['tema']    ?? 'Nezvolená'));
$sprava  = trim(strip_tags($_POST['sprava']  ?? ''));

// ---- VALIDÁCIA ----
if (empty($meno) || empty($email) || empty($sprava)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vyplňte všetky povinné polia.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatná emailová adresa.']);
    exit;
}

if (mb_strlen($sprava) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Správa je príliš dlhá.']);
    exit;
}

// Ochrana pred header injection
foreach ([$meno, $email, $telefon, $tema] as $pole) {
    if (preg_match('/[\r\n]/', $pole)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Neplatné údaje.']);
        exit;
    }
}

// ---- ZOSTAVENIE EMAILU ----
$predmet = mb_encode_mimeheader($predmet_prefix . $tema . ' — ' . $meno, 'UTF-8');

$telo  = "Nová správa z kontaktného formulára na juust.sk\n";
$telo .= str_repeat('-', 48) . "\n\n";
$telo .= "Meno:     $meno\n";
$telo .= "Email:    $email\n";
$telo .= "Telefón:  " . ($telefon ?: '—') . "\n";
$telo .= "Téma:     $tema\n\n";
$telo .= "Správa:\n$sprava\n\n";
$telo .= str_repeat('-', 48) . "\n";
$telo .= "Táto správa bola odoslaná z juust.sk\n";

$hlavicky  = "From: Juust Web <{$odosielatel}>\r\n";
$hlavicky .= "Reply-To: {$meno} <{$email}>\r\n";
$hlavicky .= "MIME-Version: 1.0\r\n";
$hlavicky .= "Content-Type: text/plain; charset=UTF-8\r\n";
$hlavicky .= "Content-Transfer-Encoding: 8bit\r\n";
$hlavicky .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// ---- ODOSLANIE ----
$odoslane = mail($prijemca, $predmet, $telo, $hlavicky);

if ($odoslane) {
    // Ulož pokus do rate limit logu
    $attempts[] = $now;
    file_put_contents($lockFile, json_encode(array_values($attempts)));

    echo json_encode(['success' => true, 'message' => 'Email bol úspešne odoslaný.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Nastala chyba pri odoslaní. Skúste neskôr.']);
}
