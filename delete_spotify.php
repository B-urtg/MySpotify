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
$base_spotify_dir = '/mnt/drive/spotify';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['current_dir'])) {
        $sub_dir = trim(basename((string)$_POST['current_dir']), '/\\');
    }

    if (isset($_POST['file_to_delete'])) {
        $target_name = trim((string)$_POST['file_to_delete']);

        // Wyznaczamy katalog bazowy dla usuwanego elementu
        $session_home = rtrim((string)$_SESSION['user_home'], '/');
        $candidate_dir = ($sub_dir !== '') ? ($base_spotify_dir . '/' . $sub_dir) : $session_home;

        $real_base = realpath($base_spotify_dir);
        $real_candidate_dir = realpath($candidate_dir);
        $target_path = ($real_candidate_dir !== false) ? ($real_candidate_dir . '/' . $target_name) : '';
        $real_target = ($target_path !== '') ? realpath($target_path) : false;

        // Zabezpieczenie przed Directory Traversal – cel musi leżeć wewnątrz /mnt/drive/spotify
        if (
            $real_base !== false &&
            $real_target !== false &&
            str_starts_with($real_target, $real_base . DIRECTORY_SEPARATOR) &&
            file_exists($real_target)
        ) {
            $owner_dir = basename(dirname($real_target));
            $current_user = (string)($_SESSION['user'] ?? '');
            $is_admin = (($_SESSION['role'] ?? '') === 'admin');

            if (!$is_admin && $owner_dir !== $current_user) {
                $_SESSION['error_message'] = "Brak uprawnień do usunięcia tego pliku.";
            } else {
                if (is_dir($real_target)) {
                    $items = array_diff(scandir($real_target), ['.', '..']);
                    if (empty($items)) {
                        if (!@rmdir($real_target)) {
                            $_SESSION['error_message'] = "Nie udało się usunąć katalogu.";
                        }
                    } else {
                        $_SESSION['error_message'] = "Nie można usunąć katalogu, ponieważ nie jest pusty!";
                    }
                } else {
                    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                    if ($conn) {
                        mysqli_set_charset($conn, "utf8mb4");

                        $filename_only = basename($real_target);
                        $user_id = (int)($_SESSION['id'] ?? 0);

                        // Usuwamy powiązania z playlistdatabase oraz sam utwór z tabeli song
                        if ($is_admin) {
                            $del_pl_stmt = mysqli_prepare(
                                $conn,
                                "DELETE pd FROM playlistdatabase pd JOIN song s ON pd.ids = s.ids WHERE s.filename = ?"
                            );
                            if ($del_pl_stmt) {
                                mysqli_stmt_bind_param($del_pl_stmt, "s", $filename_only);
                                mysqli_stmt_execute($del_pl_stmt);
                                mysqli_stmt_close($del_pl_stmt);
                            }

                            $del_song_stmt = mysqli_prepare($conn, "DELETE FROM song WHERE filename = ?");
                            if ($del_song_stmt) {
                                mysqli_stmt_bind_param($del_song_stmt, "s", $filename_only);
                                mysqli_stmt_execute($del_song_stmt);
                                mysqli_stmt_close($del_song_stmt);
                            }
                        } else {
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
                        }

                        mysqli_close($conn);
                    }

                    if (!@unlink($real_target)) {
                        $_SESSION['error_message'] = "Nie udało się usunąć pliku z dysku.";
                    }
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