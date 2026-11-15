<?php
declare(strict_types=1);
session_start();

// 1. Zabezpieczenie: Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit("Brak dostępu.");
}

// 2. Sprawdzenie czy przekazano parametry user oraz file
if (!isset($_GET['user']) || !isset($_GET['file']) || empty(trim($_GET['user'])) || empty(trim($_GET['file']))) {
    http_response_code(400);
    exit("Nie podano użytkownika lub pliku.");
}

$target_dir = "/mnt/drive/spotify/";

// Bezpieczne złożenie ścieżki (użytkownik + plik) z zabezpieczeniem przed Directory Traversal
$safe_user = basename($_GET['user']);
$requested_file = $_GET['file'];

$filepath = realpath($target_dir . $safe_user . '/' . $requested_file);
$real_base = realpath($target_dir);

if ($filepath === false || $real_base === false || !str_starts_with($filepath, $real_base) || !is_file($filepath)) {
    http_response_code(404);
    exit("Plik nie istnieje.");
}

// 3. Walidacja rozszerzenia (tylko dozwolone pliki audio)
$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$allowed_extensions = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'flac' => 'audio/flac',
    'm4a' => 'audio/mp4',
    'aac' => 'audio/aac'
];

if (!array_key_exists($ext, $allowed_extensions)) {
    http_response_code(403);
    exit("Niedozwolony format pliku.");
}

$contentType = $allowed_extensions[$ext];
$fileSize = filesize($filepath);
$fp = fopen($filepath, 'rb');

if ($fp === false) {
    http_response_code(500);
    exit("Nie można otworzyć pliku.");
}

// 4. Obsługa nagłówków HTTP Range (umożliwia przewijanie paska odtwarzania w <audio>)
$range = 0;
$length = $fileSize;

if (isset($_SERVER['HTTP_RANGE'])) {
    $rangeHeader = $_SERVER['HTTP_RANGE'];
    if (preg_match('/bytes=\d+/', $rangeHeader)) {
        $rangeBytes = explode('=', $rangeHeader);
        $rangeRange = explode('-', $rangeBytes[1]);
        $range = (int)$rangeRange[0];
        
        if (isset($rangeRange[1]) && is_numeric($rangeRange[1])) {
            $end = (int)$rangeRange[1];
            $length = $end - $range + 1;
        } else {
            $length = $fileSize - $range;
        }
        
        http_response_code(206); // Partial Content
        header("Content-Range: bytes $range-" . ($range + $length - 1) . "/$fileSize");
    } else {
        http_response_code(200);
    }
} else {
    http_response_code(200);
}

// 5. Wysyłanie nagłówków strumieniowania do przeglądarki
header("Content-Type: $contentType");
header("Accept-Ranges: bytes");
header("Content-Length: $length");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Ustawienie wskaźnika pliku w przypadku żądania zakresu (Range)
if ($range > 0) {
    fseek($fp, $range);
}

// 6. Przesyłanie strumienia danych do klienta w paczkach po 8KB
$bufferSize = 8192;
while (!feof($fp) && ($length > 0)) {
    $bytesToRead = min($bufferSize, $length);
    $chunk = fread($fp, $bytesToRead);
    echo $chunk;
    flush();
    $length -= $bytesToRead;
}

fclose($fp);
exit();