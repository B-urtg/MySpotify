<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

// Nawiązanie połączenia mysqli na podstawie stałych z config.php
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    header("Location: user_panel_spotify.php?error=db_error");
    exit();
}
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlistName = trim($_POST['playlist_name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $user_id = (int)($_SESSION['id'] ?? 0);

    if ($playlistName === '' || ($type !== 'public' && $type !== 'private') || $user_id === 0) {
        header("Location: user_panel_spotify.php?error=invalid_data");
        exit();
    }

    $isPublic = ($type === 'public') ? 1 : 0;

    // 1. Sprawdzenie, czy playlista o takiej nazwie już istnieje dla tego użytkownika
    $checkStmt = mysqli_prepare($conn, "SELECT idpl FROM playlistname WHERE name = ? AND id = ?");
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "si", $playlistName, $user_id);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            mysqli_stmt_close($checkStmt);
            mysqli_close($conn);
            header("Location: user_panel_spotify.php?error=playlist_exists");
            exit();
        }
        mysqli_stmt_close($checkStmt);
    }

    // 2. Zapis nowej playlisty
    $stmt = mysqli_prepare($conn, "INSERT INTO playlistname (id, name, public) VALUES (?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $playlistName, $isPublic);
        mysqli_stmt_execute($stmt);
        
        $new_playlist_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        header("Location: user_panel_spotify.php?playlist_id=" . $new_playlist_id);
        exit();
    } else {
        mysqli_close($conn);
        header("Location: user_panel_spotify.php?error=db_error");
        exit();
    }
}

mysqli_close($conn);
header("Location: user_panel_spotify.php");
exit();