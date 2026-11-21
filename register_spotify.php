<?php declare(strict_types=1);?>
<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Zawada MySpotify</title>
        <link rel="stylesheet" href="styles_spotify.css">
    </head>
    <body>
        <main class="login">
            <div class="logbox">
                <div class="login-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spotify-icon">
                        <path d="M 4 8 A 16 16 0 0 1 20 9.2"></path>
                        <path d="M 5.5 13 A 13 13 0 0 1 18.5 14"></path>
                        <path d="M 7 18 A 10 10 0 0 1 17 18.7"></path>
                    </svg>
                </div>
                <div class="login-header">
                    Zarejestruj się
                </div>
                <form method="post" action="add_spotify.php" enctype="multipart/form-data">
                    <div class="textbox">
                        <label class="login-label">Login</label>
                        <input type="text" name="user" class="inputbox" id="input" maxlength="20" size="20">
                    </div>
                    <div class="textbox">
                        <label class="login-label">Hasło</label>
                        <input type="pass" name="pass" class="inputbox" id="input" maxlength="20" size="20">
                    </div>
                    <div class="textbox">
                        <label class="login-label">Powtórz hasło</label>
                        <input type="pass" name="pass2" class="inputbox" id="input" maxlength="20" size="20">
                    </div>
                    <label class="login-label">Dodaj avatar</label>
                    <input class="inputbox" type="file" class="form-control" id="avatar" name="avatar" accept=".png, .jpg, .jpeg">
                    <button class="button" type="submit">Zarejestruj</button>
                </form>
            </div>
        </main>
    </body>
</html>