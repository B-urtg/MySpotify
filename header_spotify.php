<?php 
declare(strict_types=1); 
$sub_dir = $sub_dir ?? ($_GET['dir'] ?? '');
$sub_dir = trim($sub_dir, '/\\');
$segments = $sub_dir !== '' ? explode('/', $sub_dir) : [];
$accumulated_path = '';
?>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="header_style_spotify.css">
</head>

<nav class="navbar navbar-expand-lg">
    <div class="home">
        <a class="generic-text" href="user_panel_spotify.php" title="MySpotify">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="spotify-icon">
                <path d="M 4 8 A 16 16 0 0 1 20 9.2"></path>
                <path d="M 5.5 13 A 13 13 0 0 1 18.5 14"></path>
                <path d="M 7 18 A 10 10 0 0 1 17 18.7"></path>
            </svg>
        </a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="toggler-icon">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <div class="navbar-nav">
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
            <a class="generic-text" href="#">Item</a>
        </div>
    </div>
    <div class="user-panel">
        <div class="profile">
            <img src="<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default_avatar.png'); ?>" class="avatar" alt="Profil">
        </div>
        <a href="logout_spotify.php" class="generic-text" title="Wyloguj się">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</nav>