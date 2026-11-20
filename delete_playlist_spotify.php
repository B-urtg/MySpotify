<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

$user_id = (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idpl'])) {
    $idpl = (int)$_POST['idpl'];

    if ($idpl > 0) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn) {
            mysqli_set_charset($conn, "utf8mb4");

            $check_stmt = mysqli_prepare($conn, "SELECT idpl FROM playlistname WHERE idpl = ? AND id = ? LIMIT 1");
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "ii", $idpl, $user_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);

                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    mysqli_stmt_close($check_stmt);

                    $del_items_stmt = mysqli_prepare($conn, "DELETE FROM playlistdatabase WHERE idpl = ?");
                    if ($del_items_stmt) {
                        mysqli_stmt_bind_param($del_items_stmt, "i", $idpl);
                        mysqli_stmt_execute($del_items_stmt);
                        mysqli_stmt_close($del_items_stmt);
                    }

                    $del_pl_stmt = mysqli_prepare($conn, "DELETE FROM playlistname WHERE idpl = ? AND id = ?");
                    if ($del_pl_stmt) {
                        mysqli_stmt_bind_param($del_pl_stmt, "ii", $idpl, $user_id);
                        mysqli_stmt_execute($del_pl_stmt);
                        mysqli_stmt_close($del_pl_stmt);
                    }
                } else {
                    mysqli_stmt_close($check_stmt);
                }
            }

            mysqli_close($conn);
        }
    }
}

header("Location: user_panel_spotify.php");
exit();
?>