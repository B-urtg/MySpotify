<?php
declare(strict_types=1);
require_once 'config.php';

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

// Inicjalizacja połączenia mysqli (korzystając ze stałych z config.php)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

$username = $_SESSION['user'];
$avatar = $_SESSION['avatar'];
$user_id = (int)($_SESSION['id'] ?? 0);

// Pobieranie playlist użytkownika do wyświetlenia w panelu/headerze
$playlists = [];
$stmt = mysqli_prepare($conn, "SELECT idpl, name, public FROM playlistname WHERE id = ? ORDER BY idpl DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $playlists[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="pl">
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
            
            <div class="playlist-controls">
                <div class="global-player-info">
                    <span id="global-playing-label">Nic nie jest odtwarzane</span>
                    <div class="player-progress-container">
                        <span id="current-time">0:00</span>
                        <input type="range" id="global-progress" value="0" max="100" step="0.1">
                        <span id="total-time">0:00</span>
                    </div>
                </div>

                <div class="global-volume-container">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
                    <input type="range" id="global-volume" min="0" max="1" step="0.01" value="1">
                </div>

                <div class="global-controls">
                    <button id="global-prev" class="ctrl-btn" title="Poprzedni">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
                        </svg>
                    </button>
                    <button id="global-toggle" class="ctrl-btn" title="Start/Stop">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <button id="global-next" class="ctrl-btn" title="Następny">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="spotify-panel">
                <?php
                    $playlist_files = [];
                    $selected_playlist_id = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
                    $is_public_view = isset($_GET['public_playlists']) && $_GET['public_playlists'] == '1';
                    $public_playlists_list = [];

                    if ($is_public_view) {
                        // Pobieramy wszystkie publiczne playlisty oraz login ich autora
                        $pub_query = mysqli_prepare($conn, "
                            SELECT p.idpl, p.name, u.username AS author_name 
                            FROM playlistname p
                            JOIN users u ON p.id = u.id
                            WHERE p.public = 1
                            ORDER BY p.name ASC
                        ");
                        if ($pub_query) {
                            mysqli_stmt_execute($pub_query);
                            $pub_result = mysqli_stmt_get_result($pub_query);
                            while ($row = mysqli_fetch_assoc($pub_result)) {
                                $public_playlists_list[] = $row;
                            }
                            mysqli_stmt_close($pub_query);
                        }
                    } elseif ($selected_playlist_id > 0) {
                        $track_stmt = mysqli_prepare($conn, "
                            SELECT s.*, u.username AS owner_name 
                            FROM playlistdatabase pd
                            JOIN song s ON pd.ids = s.ids
                            JOIN users u ON s.id = u.id
                            WHERE pd.idpl = ?
                        ");
                        
                        if ($track_stmt) {
                            mysqli_stmt_bind_param($track_stmt, "i", $selected_playlist_id);
                            mysqli_stmt_execute($track_stmt);
                            $result = mysqli_stmt_get_result($track_stmt);
                            while ($track = mysqli_fetch_assoc($result)) {
                                $owner = $track['owner_name'];
                                $filename = $track['filename'];

                                $playlist_files[] = [
                                    'user' => $owner,
                                    'file' => $filename,
                                    'display_name' => ($track['musician'] ?? '') . ' - ' . ($track['title'] ?? 'Utwór')
                                ];
                            }
                            mysqli_stmt_close($track_stmt);
                        }
                    } else {
                        // Standardowy widok katalogu plików na dysku (NAS-style)
                        $base_dir = "/mnt/drive/spotify/";
                        $target_dir = (isset($_GET['dir']) && !empty($_GET['dir'])) 
                            ? $base_dir . basename($_GET['dir']) . "/" 
                            : $base_dir;

                        $audio_extensions = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'];

                        if (is_dir($target_dir)) {
                            $dir_iterator = new RecursiveDirectoryIterator($target_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                            $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

                            foreach ($iterator as $file) {
                                if ($file->isFile()) {
                                    $filename = $file->getFilename();
                                    
                                    if (str_starts_with($filename, '.')) {
                                        continue;
                                    }

                                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                                    if (in_array($ext, $audio_extensions, true)) {
                                        $full_path = $file->getPathname();
                                        
                                        $relative_to_base = substr($full_path, strlen($base_dir));
                                        $path_parts = explode('/', $relative_to_base, 2);
                                        $file_user = $path_parts[0] ?? '';
                                        $file_rel = $path_parts[1] ?? '';

                                        $display_name = (isset($_GET['dir']) && !empty($_GET['dir'])) 
                                            ? substr($full_path, strlen($target_dir)) 
                                            : $relative_to_base;

                                        $playlist_files[] = [
                                            'user' => $file_user,
                                            'file' => $file_rel,
                                            'display_name' => $display_name
                                        ];
                                    }
                                }
                            }
                        }
                    }
                ?>

                <!-- Widok publicznych playlist -->
                <?php if ($is_public_view): ?>
                    <div class="playlist-container" style="padding: 20px; color: #c4a9a9;">
                        <h2>Playlisty publiczne</h2>
                        <div class="cards-grid" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
                            <?php if (!empty($public_playlists_list)): ?>
                                <?php foreach ($public_playlists_list as $pub_pl): ?>
                                    <div class="playlist-card" style="background: #200707; border: 1px solid #591515; padding: 20px; border-radius: 8px; width: 220px;">
                                        <h3><?php echo htmlspecialchars($pub_pl['name']); ?></h3>
                                        <p style="font-size: 13px; opacity: 0.8; margin: 10px 0;">Autor: <?php echo htmlspecialchars($pub_pl['author_name']); ?></p>
                                        <a href="user_panel_spotify.php?playlist_id=<?php echo (int)$pub_pl['idpl']; ?>" class="button-popup" style="display: inline-block; text-align: center; text-decoration: none; padding: 6px 12px; margin-top: 10px;">
                                            Otwórz
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Brak publicznych playlist w bazie.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Pętla renderująca pliki w kafelkach audio -->
                    <?php foreach ($playlist_files as $song): 
                        $file_url = "stream_spotify.php?user=" . urlencode($song['user']) . "&file=" . urlencode($song['file']);
                        $is_own_song = ($song['user'] === $username);
                        $del_id = md5($song['user'] . '/' . $song['file']);
                    ?>
                        <div class="element-panel audio-element-panel">
                            <audio preload="none" src="<?php echo htmlspecialchars($file_url); ?>"></audio>
                            
                            <div class="element track-item">
                                <div class="content-box">
                                    <?php echo htmlspecialchars($song['display_name']); ?>
                                </div>

                                <?php if ($is_own_song): ?>
                                    <label for="popup-delete-<?php echo $del_id; ?>" class="del-btn" title="Usuń" onclick="event.stopPropagation();">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="trash-icon">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </label>
                                <?php endif; ?>
                            </div>

                            <?php if ($is_own_song): ?>
                                <input type="checkbox" id="popup-delete-<?php echo $del_id; ?>" class="popup-checkbox">
                                <div class="popup-overlay" id="overlay-delete-<?php echo $del_id; ?>" onclick="document.getElementById('popup-delete-<?php echo $del_id; ?>').checked = false;">
                                    <div class="popup-box" onclick="event.stopPropagation();">
                                        <h3>Usuwanie elementu</h3>
                                        <p>Czy na pewno chcesz usunąć: <strong><?php echo htmlspecialchars($song['display_name']); ?></strong>?</p>
                                        <form action="delete_song_spotify.php" method="POST">
                                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($song['user']); ?>">
                                            <input type="hidden" name="file_to_delete" value="<?php echo htmlspecialchars($song['file']); ?>">
                                            <div class="popup-buttons">
                                                <button type="submit" class="button-popup">Usuń</button>
                                                <label for="popup-delete-<?php echo $del_id; ?>" class="button-popup" style="display:inline-block; text-align:center; line-height:normal; cursor:pointer;">Anuluj</label>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>    

            <div class="new-elements">
                <!-- 1. Przycisk otwierający popup dodawania pliku -->
                <label for="popup-file" class="element-popup" title="Dodaj nowy utwór">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-plus-icon">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                        <line x1="12" y1="12" x2="12" y2="18"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </label>

                <!-- 2. Przycisk otwierający popup tworzenia playlisty -->
                <label for="popup-playlist" class="element-popup" title="Utwórz nową playlistę">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="youtube-plus-icon">
                        <polygon points="4 3 21 12 4 21 4 3"></polygon>
                        <line x1="10" y1="9" x2="10" y2="15"></line>
                        <line x1="7" y1="12" x2="13" y2="12"></line>
                    </svg>
                </label>

                <!-- 3. Przycisk: Dodaj piosenki do playlisty -->
                <label for="popup-add-to-playlist" class="element-popup" title="Dodaj piosenki do playlisty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </label>

                <!-- 4. Przycisk: Usuń swoją playlistę -->
                <label for="popup-delete-playlist" class="element-popup" title="Usuń swoją playlistę">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </label>

                <!-- POPUP 1: Tworzenie playlisty -->
                <input type="checkbox" id="popup-playlist" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-playlist">
                    <div class="popup-box">
                        <h3>Utwórz nową playlistę</h3>
                        <form action="create_playlist_spotify.php" method="POST">
                            <input type="text" name="playlist_name" placeholder="Nazwa playlisty..." required class="inputbox" autocomplete="off">
                            <select name="type" required class="inputbox">
                                <option value="" disabled selected>Wybierz typ playlisty</option>
                                <option value="public">Publiczna</option>
                                <option value="private">Prywatna</option>
                            </select>
                            <div class="popup-buttons">
                                <label for="popup-playlist" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Utwórz</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- POPUP 2: Dodawanie nowego utworu -->
                <input type="checkbox" id="popup-file" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-file">
                    <div class="popup-box">
                        <h3>Dodaj nowy utwór</h3>
                        <form action="upload_spotify.php" method="POST" enctype="multipart/form-data">
                            <input type="file" name="file_to_upload" accept="audio/*" required class="inputbox">
                            <input type="text" name="title" placeholder="Tytuł" required class="inputbox" autocomplete="off">
                            <input type="text" name="musician" placeholder="Autor" required class="inputbox" autocomplete="off">
                            <label>Tekst piosenki (opcjonalnie .txt):</label>
                            <input type="file" name="lyrics" accept=".txt" class="inputbox">
                            <select name="idmt" required class="inputbox">
                                <option value="" disabled selected>Wybierz gatunek</option>
                                <option value="1">Pop</option>
                                <option value="2">Rock</option>
                                <option value="3">Hip-Hop</option>
                                <option value="4">Elektroniczna</option>
                                <option value="5">R&B</option>
                                <option value="6">Latynoska</option>
                                <option value="7">Country</option>
                                <option value="8">Metal</option>
                                <option value="9">Jazz</option>
                                <option value="10">Klasyczna</option>
                                <option value="11">Inna</option>
                            </select>
                            <div class="popup-buttons">
                                <label for="popup-file" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Wyślij</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- POPUP 3: Dodawanie wielu piosenek (od każdego użytkownika) do playlisty -->
                <input type="checkbox" id="popup-add-to-playlist" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-add-to-playlist">
                    <div class="popup-box" style="max-height: 80vh; overflow-y: auto;">
                        <h3>Dodaj piosenki do playlisty</h3>
                        <form action="add_to_playlist_spotify.php" method="POST">
                            <!-- Wybór playlisty docelowej -->
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Wybierz playlistę:</label>
                            <select name="idpl" required class="inputbox">
                                <option value="" disabled selected>Wybierz playlistę</option>
                                <?php foreach ($playlists as $pl): ?>
                                    <option value="<?php echo (int)$pl['idpl']; ?>"><?php echo htmlspecialchars($pl['name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label style="display:block; margin: 10px 0 5px; font-size:14px;">Wybierz utwory (przytrzymaj Ctrl, aby zaznaczyć wiele):</label>
                           <select name="song_ids[]" multiple required class="inputbox" style="height: 180px;">
                                <?php
                                    $songs_query = mysqli_query($conn, "SELECT ids, title, musician FROM song ORDER BY musician, title");
                                    if ($songs_query) {
                                        while ($song = mysqli_fetch_assoc($songs_query)) {
                                            $label = htmlspecialchars($song['musician'] . ' - ' . $song['title']);
                                            echo '<option value="' . (int)$song['ids'] . '">' . $label . '</option>';
                                        }
                                    }
                                ?>
                            </select>

                            <div class="popup-buttons">
                                <label for="popup-add-to-playlist" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Dodaj do playlisty</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- POPUP 4: Usuwanie playlisty stworzonej przez zalogowanego użytkownika -->
                <input type="checkbox" id="popup-delete-playlist" class="popup-checkbox">
                <div class="popup-overlay" id="overlay-delete-playlist">
                    <div class="popup-box">
                        <h3>Usuń swoją playlistę</h3>
                        <form action="delete_playlist_spotify.php" method="POST">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Wybierz playlistę do usunięcia:</label>
                            <select name="idpl" required class="inputbox">
                                <option value="" disabled selected>Wybierz playlistę</option>
                                <?php foreach ($playlists as $pl): ?>
                                    <option value="<?php echo (int)$pl['idpl']; ?>"><?php echo htmlspecialchars($pl['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="popup-buttons">
                                <label for="popup-delete-playlist" class="button-popup">Anuluj</label>
                                <button type="submit" class="button-popup">Usuń</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <div id="mediaModal" class="media-modal" onclick="closeMediaModal()">
            <div class="media-modal-content" onclick="event.stopPropagation()">
                <span class="media-close" onclick="closeMediaModal()">&times;</span>
                <div id="modalBody"></div>
            </div>
        </div>

        <script>
            function openImageModal(url, title) {
                const modal = document.getElementById('mediaModal');
                const body = document.getElementById('modalBody');
                body.innerHTML = `<img src="${url}" alt="${title}" style="max-width:100%; max-height:80vh; border-radius:8px;"><p style="text-align:center; margin-top:10px; color:#c4a9a9;">${title}</p>`;
                modal.style.display = 'flex';
            }
            function closeMediaModal() {
                const modal = document.getElementById('mediaModal');
                const body = document.getElementById('modalBody');
                modal.style.display = 'none';
                body.innerHTML = ''; 
            }
        </script>

        <script>
            const iconPlay = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>`;
            const iconPause = `<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;
            
            document.addEventListener("DOMContentLoaded", function() {
                const audioPanels = Array.from(document.querySelectorAll(".audio-element-panel"));
                const audioPlayers = audioPanels.map(panel => panel.querySelector("audio")).filter(Boolean);
                const players = audioPlayers.length > 0 ? audioPlayers : Array.from(document.querySelectorAll("audio"));

                const prevBtn = document.getElementById("global-prev");
                const toggleBtn = document.getElementById("global-toggle");
                const nextBtn = document.getElementById("global-next");
                const infoLabel = document.getElementById("global-playing-label");
                
                const progressBar = document.getElementById("global-progress");
                const currentTimeEl = document.getElementById("current-time");
                const totalTimeEl = document.getElementById("total-time");
                const volumeSlider = document.getElementById("global-volume");

                let activeIndex = -1;
                let isSeeking = false;

                function formatTime(seconds) {
                    if (isNaN(seconds)) return "0:00";
                    const mins = Math.floor(seconds / 60);
                    const secs = Math.floor(seconds % 60);
                    return mins + ":" + (secs < 10 ? "0" : "") + secs;
                }

                function getTrackName(audioElement) {
                    const panel = audioElement.closest(".element-panel, .element");
                    if (panel) {
                        const textBox = panel.querySelector(".content-box");
                        if (textBox) return textBox.textContent.trim();
                    }
                    return "Utwór audio";
                }

                function updateActivePanelStyles() {
                    audioPanels.forEach(panel => {
                        panel.classList.remove("active-track");
                    });
                    if (activeIndex !== -1 && audioPanels[activeIndex]) {
                        audioPanels[activeIndex].classList.add("active-track");
                    }
                }

                function setActivePlayer(index, autoPlay = true) {
                    if (index < 0 || index >= players.length) return;

                    players.forEach((p, i) => {
                        if (i !== index) {
                            p.pause();
                        }
                    });

                    activeIndex = index;
                    const currentAudio = players[activeIndex];
                    
                    currentAudio.currentTime = 0;
                    currentAudio.volume = volumeSlider.value;
                    updateActivePanelStyles();

                    infoLabel.textContent = "Wybrano: " + getTrackName(currentAudio);

                    if (autoPlay) {
                        currentAudio.play().catch(e => console.log("Autoodtwarzanie zablokowane:", e));
                    }
                }

                audioPanels.forEach((panel, index) => {
                    panel.addEventListener("click", () => {
                        setActivePlayer(index, true);
                    });
                });

                players.forEach((audio, index) => {
                    audio.addEventListener("timeupdate", () => {
                        if (!isSeeking && activeIndex === index && audio.duration) {
                            progressBar.value = (audio.currentTime / audio.duration) * 100;
                            currentTimeEl.textContent = formatTime(audio.currentTime);
                            totalTimeEl.textContent = formatTime(audio.duration);
                        }
                    });

                    audio.addEventListener("loadedmetadata", () => {
                        if (activeIndex === index) {
                            totalTimeEl.textContent = formatTime(audio.duration);
                        }
                    });

                    audio.addEventListener("play", () => {
                        activeIndex = index;
                        players.forEach((p, i) => { if (i !== index) p.pause(); });
                        infoLabel.textContent = "Teraz gra: " + getTrackName(audio);
                        toggleBtn.innerHTML = iconPause;
                        updateActivePanelStyles();
                    });

                    audio.addEventListener("pause", () => {
                        if (activeIndex === index) {
                            toggleBtn.innerHTML = iconPlay;
                        }
                    });

                    audio.addEventListener("ended", () => {
                        if (index + 1 < players.length) {
                            setActivePlayer(index + 1, true);
                        } else {
                            infoLabel.textContent = "Koniec playlisty";
                            toggleBtn.innerHTML = iconPlay;
                            progressBar.value = 0;
                            currentTimeEl.textContent = "0:00";
                        }
                    });
                });

                progressBar.addEventListener("mousedown", () => { isSeeking = true; });
                progressBar.addEventListener("touchstart", () => { isSeeking = true; });

                progressBar.addEventListener("input", () => {
                    if (activeIndex !== -1 && players[activeIndex].duration) {
                        const audio = players[activeIndex];
                        const time = (progressBar.value / 100) * audio.duration;
                        currentTimeEl.textContent = formatTime(time);
                    }
                });

                progressBar.addEventListener("change", () => {
                    isSeeking = false;
                    if (activeIndex !== -1 && players[activeIndex].duration) {
                        const audio = players[activeIndex];
                        audio.currentTime = (progressBar.value / 100) * audio.duration;
                    }
                });

                volumeSlider.addEventListener("input", () => {
                    const vol = volumeSlider.value;
                    players.forEach(p => { p.volume = vol; });
                });

                toggleBtn.addEventListener("click", () => {
                    if (activeIndex === -1 && players.length > 0) {
                        setActivePlayer(0, true);
                        return;
                    }
                    if (activeIndex !== -1) {
                        const current = players[activeIndex];
                        if (current.paused) {
                            current.play();
                            toggleBtn.innerHTML = iconPause;
                        } else {
                            current.pause();
                            toggleBtn.innerHTML = iconPlay;
                        }
                    }
                });

                prevBtn.addEventListener("click", () => {
                    if (activeIndex > 0) {
                        setActivePlayer(activeIndex - 1, true);
                    } else if (players.length > 0) {
                        setActivePlayer(0, true);
                    }
                });

                nextBtn.addEventListener("click", () => {
                    if (activeIndex === -1 && players.length > 0) {
                        setActivePlayer(0, true);
                    } else if (activeIndex + 1 < players.length) {
                        setActivePlayer(activeIndex + 1, true);
                    }
                });
                
            });
        </script>
    </body>
</html>
<?php 
// Bezpieczne zamknięcie połączenia na końcu skryptu
if (isset($conn)) { 
    mysqli_close($conn); 
}
?>