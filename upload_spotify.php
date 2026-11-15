<?php
declare(strict_types=1);
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once "config.php";

// 1. Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Połączenie z bazą danych przez mysqli
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
}
mysqli_set_charset($link, "utf8mb4");

// 2. Obsługa wysłania formularza metodą POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $musician = trim($_POST['musician'] ?? '');
    $idmt = (int)($_POST['idmt'] ?? 0); // Wybrany gatunek (musictype)
    $user_id = $_SESSION['id'];   // ID zalogowanego użytkownika
    
    $target_dir = "/mnt/drive/spotify/" . basename($_SESSION['user']) . "/";
    
    // Sprawdzenie czy katalog docelowy istnieje, jeśli nie – próba utworzenia
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // 3. Sprawdzenie czy przesłano plik audio
    if (isset($_FILES['file_to_upload']) && $_FILES['file_to_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_to_upload']['tmp_name'];
        $original_filename = basename($_FILES['file_to_upload']['name']);
        
        // Zabezpieczenie unikalności nazwy pliku (dodanie timestampu)
        $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_filename);
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($file_tmp, $target_file)) {
            
            // Opcjonalna obsługa pliku tekstowego z tekstem (lyrics)
            $lyrics_filename = null;
            if (isset($_FILES['lyrics_file']) && $_FILES['lyrics_file']['error'] === UPLOAD_ERR_OK) {
                $lyrics_tmp = $_FILES['lyrics_file']['tmp_name'];
                $lyrics_original = basename($_FILES['lyrics_file']['name']);
                $lyrics_filename = time() . "_lyrics_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $lyrics_original);
                move_uploaded_file($lyrics_tmp, $target_dir . $lyrics_filename);
            }

            // 4. Zapis do bazy danych przy użyciu MySQLi (Zmień 'nazwa_twojej_tabeli' na właściwą nazwę)
            $sql = "INSERT INTO song (title, musician, datetime, id, filename, lyrics, idmt) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($link, $sql);
            if ($stmt) {
                // Powiązanie parametrów: s = string, i = integer
                // Kolejność: title(s), musician(s), user_id(i), filename(s), lyrics(s), idmt(i)
                mysqli_stmt_bind_param($stmt, "ssissi", $title, $musician, $user_id, $filename, $lyrics_filename, $idmt);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Sukces – przekierowanie z powrotem do panelu
                    $_SESSION['success_message'] = "Utwór został dodany pomyślnie!";
                    header("Location: user_panel_spotify.php");
                    exit();
                } else {
                    echo "Błąd zapisu w bazie danych: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Błąd przygotowania zapytania: " . mysqli_error($link);
            }
            
        } else {
            echo "Błąd podczas zapisywania pliku na serwerze w ścieżce /mnt/drive/spotify/. Sprawdź uprawnienia do katalogu.";
        }
    } else {
        echo "Nie wybrano pliku audio lub wystąpił błąd podczas uploadu (kod błędu: " . ($_FILES['file_to_upload']['error'] ?? 'brak') . ").";
    }
}

mysqli_close($link);
?>