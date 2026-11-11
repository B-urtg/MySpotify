<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_home'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['user'];
$avatar = $_SESSION['avatar'];
//require_once '../MyCloud/navigation_cloud.php';
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Zawada MySpotify <?php echo htmlspecialchars($_SESSION['user']);?></title>
        <link rel="stylesheet" href="styles_spotify.css">
        <script>
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>
    </head>
    <body>
        <div class="grid">
            <?php include 'header_spotify.php';?>    
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <div class="spotify-panel">
                <div class="element-panel">
                    <a class="element" href="user_panel.php?dir=<?php echo urlencode($next_dir); ?>">
                        <div class="content-box">
                            <?php echo htmlspecialchars($file); ?>
                        </div>
                    </a>
                    <label for="popup-delete-<?php echo md5($next_dir); ?>" class="del-btn" title="Usuń" onclick="event.stopPropagation();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="trash-icon">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </label>
                    <input type="checkbox" id="popup-delete-<?php echo md5($next_dir); ?>" class="popup-checkbox">
                    <div class="popup-overlay" id="overlay-delete-<?php echo md5($next_dir); ?>">
                        <div class="popup-box">
                            <h3>Usuwanie elementu</h3>
                            <p>Czy na pewno chcesz usunąć: <strong><?php echo htmlspecialchars($next_dir); ?></strong>?</p>
                            <form action="delete_cloud.php" method="POST">
                                <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($sub_dir); ?>">
                                <input type="hidden" name="file_to_delete" value="<?php echo htmlspecialchars($next_dir); ?>">
                                <div class="popup-buttons">
                                    <button type="submit" class="button-popup">Usuń</button>
                                    <label for="popup-delete-<?php echo md5($next_dir); ?>" class="button-popup" style="display:inline-block; text-align:center; line-height:normal; cursor:pointer;">Anuluj</label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="new-elements">
                <label for="popup-file" class="element">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-plus-icon">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                        <line x1="12" y1="12" x2="12" y2="18"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </label>
                <label for="popup-playlist" class="element">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="folder-icon">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        <line x1="12" y1="11" x2="12" y2="17"></line>
                        <line x1="9" y1="14" x2="15" y2="14"></line>
                    </svg>
                </label>
                <input type="checkbox" id="popup-playlist" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-playlist">
                    <div class="popup-box">
                        <h3>Nowa PLaylista</h3>
                        <form action="create_dir_cloud.php" method="POST">
                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($sub_dir); ?>">
                            <input type="text" name="dir_name" placeholder="Nazwa folderu..." required class="inputbox" autocomplete="off">
                            <div class="popup-buttons">
                                <label for="popup-playlist" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Utwórz</button>
                            </div>
                        </form>
                    </div>
                </div>
                <input type="checkbox" id="popup-file" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-file">
                    <div class="popup-box">
                        <h3>Dodaj utwór</h3>
                        <form action="upload_cloud.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($sub_dir); ?>">
                            <input type="file" name="file_to_upload" required class="inputbox">
                            <div class="popup-buttons">
                                <label for="popup-file" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Wyślij</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>