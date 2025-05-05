<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta name="viewport" content="width=device-width, initial-scale=0.8, user-scalable=0, minimal-ui">
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" href="assets/img/favicon.png" sizes="256x256">
    <title>Vos Informations</title>
    <style>
        :root {
            --app-bg-color: #000000;
            --card-bg-color: #ffffff;
            --header-bg-color: #1c1c1c;
            --label-color: #007BFF;
            --text-color: #000000;
            --secondary-text-color: #555555;
            --border-color: #e0e0e0;
            --header-text-color: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            min-height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            background-color: var(--app-bg-color);
            padding: 20px;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            height: 100vh;
        }

        .logo img {
            height: 230px;

        }

        .card {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg-color);
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 0; 
            text-align: left;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background-color: var(--header-bg-color); 
            color: var(--header-text-color); 
            text-align: center;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .information {
            padding: 30px;
            display: flex;
            flex-wrap: wrap;
            width: 100%;
            margin-top: 20px;
        }

        .left-column, .right-column {
            width: 100%;
            padding: 10px;
        }

        .left-column {
            flex: 1;
        }

        .right-column {
            flex: 1;
        }

        .or {
            width: 100%;
            text-align: center;
            margin-bottom: 24px;
            font-size: 2rem;
            font-weight: 600;
            color: var(--header-text-color); 
            position: relative;
            background-color: var(--header-bg-color);
        }

        .or::before,
        .or::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 20%;
            height: 1px;
            background: var(--header-text-color);
        }

        .or::before {
            left: 0;
        }

        .or::after {
            right: 0;
        }

        h3 {
            font-weight: 400;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        h3 .label {
            color: var(--label-color);
            font-weight: 600;
        }

        p {
            margin-bottom: 10px;
            font-size: 1rem;
            color: var(--secondary-text-color);
        }
    </style>
</head>
<body>
<?php
session_start();

if (!isset($_SESSION['email'])) {
    echo "Aucune adresse e-mail trouvée. Veuillez vous connecter à nouveau.";
    exit();
}

include 'connexion BDD.php';

$conn = connectDB();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email'];

$sql_get_client_id = "SELECT id_client FROM client WHERE email = '$email'";
$result_get_client_id = $conn->query($sql_get_client_id);

if ($result_get_client_id->num_rows > 0) {
    $client = $result_get_client_id->fetch_assoc();
    $id_client = $client['id_client'];
} else {
    echo "Client non trouvé !";
    exit();
}

$nom = $prenom = $status = $priorite = $titre = $model = $marque = $diagnostique = $date_depot = "Non disponible";
$taches = array();

$sql_client = "SELECT * FROM client WHERE id_client = $id_client";
$result_client = $conn->query($sql_client);

if ($result_client->num_rows > 0) {
    $client = $result_client->fetch_assoc();
    $nom = $client['nom'];
    $prenom = $client['prenom'];

    $sql_tickets = "SELECT * FROM ticket WHERE id_client = $id_client";
    $result_tickets = $conn->query($sql_tickets);

    if ($result_tickets->num_rows > 0) {
        while ($ticket = $result_tickets->fetch_assoc()) {
            $status = $ticket['status'];
            $priorite = $ticket['priorite'];
            $titre = $ticket['titre'];
            $model = $ticket['Model'];
            $marque = $ticket['Marque'];
            $diagnostique = $ticket['Diagnostique'];
            $date_depot = $ticket['date_depot'];

            $id_ticket = $ticket['id'];
            $sql_taches = "SELECT * FROM tache WHERE checked = 1 AND id_ticket = $id_ticket";
            $result_taches = $conn->query($sql_taches);

            if ($result_taches->num_rows > 0) {
                while ($row = $result_taches->fetch_assoc()) {
                    $taches[] = $row['contenu'];
                }
            }
        }
    }
} else {
    echo "Client non trouvé !";
}

$conn->close();
?>
<!-- Logo -->
<div class="logo">
    <img src="assets/img/RepairDeskLogoFinalFondFonce.png" alt="Logo"><br><br><br>
</div>
<!-- Information client -->
<div class="card">
    <div class="card-header">
        <h2 class="or">Informations</h2>
    </div>
    <div class="information">
        <div class="left-column">
            <h3><span class="label">Nom:</span> <?php echo htmlspecialchars($nom . " " . $prenom); ?></h3><br>
            <h3><span class="label">Date de dépôt:</span> <?php echo htmlspecialchars($date_depot); ?></h3><br>
            <h3><span class="label">Status:</span> <?php echo htmlspecialchars($status); ?></h3><br>
            <h3><span class="label">Priorité:</span> <?php echo htmlspecialchars($priorite); ?></h3><br>
        </div>
        <div class="right-column">
            <h3><span class="label">Titre:</span> <?php echo htmlspecialchars($titre); ?></h3><br>
            <h3><span class="label">Modèle:</span> <?php echo htmlspecialchars($model); ?></h3><br>
            <h3><span class="label">Marque:</span> <?php echo htmlspecialchars($marque); ?></h3><br>
            <h3><span class="label">Diagnostique:</span> <?php echo htmlspecialchars($diagnostique); ?></h3><br>

            <!-- Si le ticket n'a pas de tâche, alors on n'affiche pas -->
            <?php if (!empty($taches)): ?>
                <h3><span class="label">Tâches réalisées:</span></h3>
                <?php foreach ($taches as $tache): ?>
                    <p><?php echo htmlspecialchars($tache); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php include 'include/ads.php';?>
</div>

</body>
</html>