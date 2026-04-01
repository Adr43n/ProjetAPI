
<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["email"]) && isset($_POST["password"])) {
    $result = callAuthAPI('/login.php', [
        'email' => trim($_POST["email"]),
        'password' => trim($_POST["password"])
    ]);

    if ($result['success']) {
        $_SESSION['username'] = $result['user']['email'];
        $_SESSION['user'] = $result['user'];
        $_SESSION['token'] = $result['token'];
        header("Location: " . BASE_PATH . "/joueur");
        die();
    } else {
        $erreur = "L'email ou le mot de passe est incorrect";
    }
}
?>

<body>
    <div class="CentredContainer">
        <h1>Login</h1>
        <div class="container">
            <form action="<?= BASE_PATH ?>/login" method="post">
                <div class="row">
                    <div class="col-20">
                        <label for="email">Email : </label>
                    </div>
                    <div class="col-80">
                        <input type="email" id="email" name="email" required/><br> 
                    </div>
                </div> 
                <div class="row">
                    <div class="col-20">
                        <label for="password">Password : </label>
                    </div>
                    <div class="col-80">
                        <input type="password" id="pass" name="password" required/><br>
                    </div>
                </div>
                <div class="row">
                    <input type="submit" value="Login"/>
                </div>
            </form>
            <div class="row" style="margin-top: 12px; text-align: center;">
                <a href="<?= BASE_PATH ?>/rencontre">Continuer sans connexion (liste des matchs)</a>
            </div>
        </div>
        <p><?php if (isset($erreur)) { echo $erreur; } ?></p>
    </div>
</body>
</html>
