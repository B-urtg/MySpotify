<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_home'])) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

$sub_dir = '';
$current_user = (string)($_SESSION['user'] ?? '');
$user_id = (int)($_SESSION['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['current_dir'])) {
        $sub_dir = trim($_POST['current_dir'], '/\\');
    }

    if (isset($_POST['file_to_delete'])) {
        $user_home = rtrim((string)$_SESSION['user_home'], '/');
        $target_name = trim((string)$_POST['file_to_delete']);

        // Usuwamy wyłącznie pliki z katalogu domowego zalogowanego użytkownika
        if ($sub_dir === $current_user && $target_name !== '') {
            $target_path = $user_home . '/' . $target_name;
            $real_target = realpath($target_path);
            $real_home = realpath($user_home);

            if ($real_target !== false && $real_home !== false && strpos($real_target, $real_home . DIRECTORY_SEPARATOR) === 0 && file_exists($real_target)) {
                if (is_dir($real_target)) {
                    $items = array_diff(scandir($real_target), ['.', '..']);
                    if (empty($items)) {
                        @rmdir($real_target);
                    } else {
                        $_SESSION['error_message'] = "Nie można usunąć katalogu, ponieważ nie jest pusty!";
                    }
                } else {
                    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                    if ($conn) {
                        mysqli_set_charset($conn, "utf8mb4");
                        $filename_only = basename($real_target);

                        $del_pl_stmt = mysqli_prepare(
                            $conn,
                            "DELETE pd FROM playlistdatabase pd JOIN song s ON pd.ids = s.ids WHERE s.filename = ? AND s.id = ?"
                        );
                        if ($del_pl_stmt) {
                            mysqli_stmt_bind_param($del_pl_stmt, "si", $filename_only, $user_id);
                            mysqli_stmt_execute($del_pl_stmt);
                            mysqli_stmt_close($del_pl_stmt);
                        }

                        $del_song_stmt = mysqli_prepare($conn, "DELETE FROM song WHERE filename = ? AND id = ?");
                        if ($del_song_stmt) {
                            mysqli_stmt_bind_param($del_song_stmt, "si", $filename_only, $user_id);
                            mysqli_stmt_execute($del_song_stmt);
                            mysqli_stmt_close($del_song_stmt);
                        }

                        mysqli_close($conn);
                    }

                    @unlink($real_target);
                }
            }
        }
    }
}

$redirect_url = "user_panel_spotify.php";
if (!empty($sub_dir)) {
    $redirect_url .= "?dir=" . urlencode($sub_dir);
}

header("Location: " . $redirect_url);
exit();
?>