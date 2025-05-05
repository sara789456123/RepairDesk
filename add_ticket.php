<?php
include 'connexion BDD.php'; // Assurez-vous que ce fichier contient la fonction connectDB

function sendJsonResponse($success, $message = '') {
    echo json_encode(array("success" => $success, "message" => $message));
    exit();
}

// Vérifie si la méthode de la requête est POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = connectDB(); // Initialise la connexion à la base de données

    if ($conn->connect_error) {
        sendJsonResponse(false, "Connexion échouée : " . $conn->connect_error);
    }

    // Gestion de l'ajout d'un ticket
    if (isset($_POST["titre"], $_POST["priorite"], $_POST["model"], $_POST["marque"], $_POST["diagnostique"], $_POST["mdp_session"], $_POST["nom"], $_POST["prenom"], $_POST["email"], $_POST["telephone"], $_POST["status"])) {
        $titre = $_POST["titre"];
        $priorite = $_POST["priorite"];
        $model = $_POST["model"];
        $marque = $_POST["marque"];
        $diagnostique = $_POST["diagnostique"];
        $mdp_session = $_POST["mdp_session"];
        $nom = $_POST["nom"];
        $prenom = $_POST["prenom"];
        $email = $_POST["email"];
        $telephone = $_POST["telephone"];
        $status = $_POST["status"];

        // Définir le fuseau horaire à utiliser (Europe/Paris pour la France)
        date_default_timezone_set('Europe/Paris');
        $date_depot = date('Y-m-d H:i:s');

        // Insérer les données du client
        $stmt_client = $conn->prepare("INSERT INTO client (nom, prenom, email, telephone) VALUES (?, ?, ?, ?)");
        $stmt_client->bind_param("ssss", $nom, $prenom, $email, $telephone);

        if ($stmt_client->execute()) {
            $client_id = $stmt_client->insert_id;

            // Insérer les données du ticket
            $stmt_ticket = $conn->prepare("INSERT INTO ticket (titre, priorite, Model, Marque, Diagnostique, Mdp_Session, id_client, status, date_depot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ticket->bind_param("ssssssiss", $titre, $priorite, $model, $marque, $diagnostique, $mdp_session, $client_id, $status, $date_depot);

            if ($stmt_ticket->execute()) {
                sendJsonResponse(true, "Ticket ajouté avec succès.");
            } else {
                sendJsonResponse(false, "Erreur lors de l'ajout du ticket : " . $conn->error);
            }
            $stmt_ticket->close();
        } else {
            sendJsonResponse(false, "Erreur lors de l'ajout du client : " . $conn->error);
        }
        $stmt_client->close();
    } else {
        sendJsonResponse(false, "Paramètres manquants pour l'ajout du ticket.");
    }

    $conn->close();
} else {
    sendJsonResponse(false, "Méthode de requête non autorisée.");
}
?>