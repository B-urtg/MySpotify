<?php
declare(strict_types=1);
session_start();

// Włączenie pokazywania błędów na czas testów (możesz wyłączyć, gdy wszystko zadziała)
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

$user_id = (int)($_SESSION['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idpl = (int)($_POST['idpl'] ?? 0);
    $song_ids = $_POST['song_ids'] ?? [];

    if ($idpl <= 0 || empty($song_ids) || !is_array($song_ids) || $user_id === 0) {
        die("Błąd: Nie wybrano playlisty lub piosenek (lub brak ID użytkownika).");
    }

    // 1. Sprawdzenie, czy playlista należy do zalogowanego użytkownika
    $check_pl = mysqli_prepare($conn, "SELECT idpl FROM playlistname WHERE idpl = ? AND id = ?");
    if ($check_pl) {
        mysqli_stmt_bind_param($check_pl, "ii", $idpl, $user_id);
        mysqli_stmt_execute($check_pl);
        mysqli_stmt_store_result($check_pl);

        if (mysqli_stmt_num_rows($check_pl) === 0) {
            die("Błąd: Brak uprawnień do tej playlisty.");
        }
        mysqli_stmt_close($check_pl);
    }

    // 2. Przygotowanie zapytania wstawiającego powiązanie (używamy INSERT IGNORE, żeby ignorować duplikaty)
    $insert_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO playlistdatabase (idpl, ids) VALUES (?, ?)");
    
    if (!$insert_stmt) {
        die("Błąd przygotowania zapytania SQL: " . mysqli_error($conn));
    }

    foreach ($song_ids as $song_id) {
        $song_id_int = (int)$song_id;
        if ($song_id_int > 0) {
            mysqli_stmt_bind_param($insert_stmt, "ii", $idpl, $song_id_int);
            mysqli_stmt_execute($insert_stmt);
        }
    }
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);

    // Sukces – powrót do widoku playlisty
    header("Location: user_panel_spotify.php?playlist_id=" . $idpl);
    exit();
}

mysqli_close($conn);
header("Location: user_panel_spotify.php");
exit();