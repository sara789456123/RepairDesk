<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8, user-scalable=0, minimal-ui">
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="icon" href="assets/img/favicon.png" sizes="256x256">
    <title>RepairDesk - Connexion</title>
</head>
<body class="preload"><br><br>
        <img src="assets/img/RepairDeskLogoFinalFondFonce.png" alt="Logo" id="logo" >
       
        <form id="loginForm" action="index.php" method="POST"><br><br><br><br>
        <span>Veuillez saisir l'email fourni en boutique :</span>

        <div class="group-form"><br>
            <input type="email" class="fat" id="email" name="email" required>
            <label>e-mail</label>
        </div>
        <div class="group-form">
            <button type="submit" class="fat-send" id="bouton">Connexion</button>
        </div>
    </form>

    <?php
    session_start(); // Démarrer une session
    include 'connexion BDD.php';

    $admin = "repairdesk@sendix.fr";
    $UrlDa = "dashboard";
    $UrlCli = "client";

    function checkEmail($email) {
        $conn = connectDB();
        
        $email = $conn->real_escape_string($email);  // Protection contre les injections SQL
        $sql = "SELECT email FROM client WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;  // L'email n'existe pas
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];

        if ($email == $admin) {
            $_SESSION['email'] = $email; // Stocker l'email dans la session
            header("Location: $UrlDa.php");
            exit();
        } elseif (checkEmail($email)) {
            $_SESSION['email'] = $email;
            header("Location: $UrlCli.php");
            exit();
        } else {
            echo "L'email n'existe pas dans notre base de données.";
        }
    }
    ?>
</body>
</html>