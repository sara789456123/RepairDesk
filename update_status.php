<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifiez si les données POST nécessaires sont présentes
    if(isset($_POST["ticketId"]) && isset($_POST["newStatus"])){
        $ticketId = $_POST["ticketId"];
        $newStatus = $_POST["newStatus"];

        // Connexion à la base de données
        include 'connexion BDD.php'; // Inclure le fichier de connexion à la base de données

        $conn = connectDB();
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Préparation de la requête de mise à jour du statut du ticket
        $stmt = $conn->prepare("UPDATE ticket SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $ticketId);

        // Exécution de la requête
        if ($stmt->execute()) {
            // Réussite
            echo "success";
        } else {
            // Erreur
            echo "error: " . $conn->error;
        }

        // Fermeture de la requête préparée et de la connexion
        $stmt->close();
        $conn->close();
    } else {
        // Si certaines données POST sont manquantes
        echo "error: données manquantes";
    }
} else {
    // Si la méthode de requête n'est pas POST
    echo "error: méthode de requête incorrecte";
}
?>
