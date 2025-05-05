<?php
include 'connexion BDD.php';
session_start();
// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'repairdesk@sendix.fr') {
    header("Location: index.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    $conn = connectDB();
    if ($conn->connect_error) {
        die(json_encode(array("success" => false, "message" => "Connection failed: " . $conn->connect_error)));
    }
    // Mise à jour du statut du ticket
    if (isset($_POST["ticketId"]) && isset($_POST["newStatus"])) {
        $ticketId = intval($_POST["ticketId"]);
        $newStatus = $_POST["newStatus"];

        try {
            $conn = connectDB();
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            $stmt = $conn->prepare("UPDATE ticket SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("si", $newStatus, $ticketId);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            if ($newStatus === "Terminé") {
                sendEmail($ticketId);
            }

            echo json_encode(array("success" => true));
        } catch (Exception $e) {
            echo json_encode(array("success" => false, "message" => $e->getMessage()));
        } finally {
            if (isset($stmt)) $stmt->close();
            if (isset($conn)) $conn->close();
        }
        exit();
    }
}
    // Mise à jour du statut de la tâche
    if (isset($_POST['action']) && $_POST['action'] == 'update_task' && isset($_POST['task_id']) && isset($_POST['checked'])) {
        $taskId = $_POST['task_id'];
        $checked = $_POST['checked'];
        $sql_update = "UPDATE tache SET checked = $checked WHERE id = $taskId";
        if ($conn->query($sql_update) === TRUE) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Erreur lors de la mise à jour : " . $conn->error));
        }
        $conn->close();
        exit();
    }
    // Ajouter Tache
    if (isset($_POST["task_content"]) && isset($_POST["ticket_id"])) {
        $nouvelle_tache = htmlspecialchars($_POST["task_content"]);
        $id_ticket = intval($_POST["ticket_id"]);
        $stmt = $conn->prepare("INSERT INTO tache (contenu, checked, id_ticket) VALUES (?, 0, ?)");
        $stmt->bind_param("si", $nouvelle_tache, $id_ticket);
        if ($stmt->execute()) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Erreur lors de l'ajout de la tâche: " . $conn->error));
        }
        $stmt->close();
        $conn->close();
        exit();
    }
     // Modification de tâche
    if (isset($_POST["editTaskId"]) && isset($_POST["editTaskContent"])) {
        $task_id = intval($_POST["editTaskId"]);
        $task_content = htmlspecialchars($_POST["editTaskContent"]);
        $stmt = $conn->prepare("UPDATE tache SET contenu = ? WHERE id = ?");
        $stmt->bind_param("si", $task_content, $task_id);
        if ($stmt->execute()) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Erreur lors de la modification de la tâche: " . $conn->error));
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    // Suppression de tâche
    if (isset($_POST["deleteTaskId"])) {
        $task_id = intval($_POST["deleteTaskId"]);
        $stmt = $conn->prepare("DELETE FROM tache WHERE id = ?");
        $stmt->bind_param("i", $task_id);
    
        if ($stmt->execute()) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Erreur lors de la suppression de la tâche: " . $conn->error));
        }
    
        $stmt->close();
        exit();
    }
    // Suppression de ticket et du client associé
if (isset($_POST["delete_ticket"]) && isset($_POST["ticket_id"])) {
    $ticket_id = intval($_POST["ticket_id"]);
    
    // Commencer une transaction
    $conn->begin_transaction();
    try {
        // Récupérer l'id du client associé au ticket
        $sql_get_client_id = $conn->prepare("SELECT id_client FROM ticket WHERE id = ?");
        $sql_get_client_id->bind_param("i", $ticket_id);
        $sql_get_client_id->execute();
        $result = $sql_get_client_id->get_result();
        $client_id = $result->fetch_assoc()['id_client'];
        $sql_get_client_id->close();
        // Supprimer le ticket
        $sql_delete_ticket = $conn->prepare("DELETE FROM ticket WHERE id = ?");
        $sql_delete_ticket->bind_param("i", $ticket_id);
        $sql_delete_ticket->execute();
        $sql_delete_ticket->close();
        // Supprimer le client
        $sql_delete_client = $conn->prepare("DELETE FROM client WHERE id_client = ?");
        $sql_delete_client->bind_param("i", $client_id);
        $sql_delete_client->execute();
        $sql_delete_client->close();
        // Valider la transaction
        $conn->commit();
        echo json_encode(array("success" => true));
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Erreur lors de la suppression: " . $e->getMessage()));
    }
    exit();
} 

// Envoi de l'email Informer client
if (isset($_POST['action']) && $_POST['action'] === 'send_email') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Récupérer les informations du client
        $stmt = $conn->prepare("SELECT nom, prenom FROM client WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $nom = $row['nom'];
        $prenom = $row['prenom'];

        $subject = "SENDIX - Consultez dès maintenant le statut de réparation de votre matériel";
        $message = "
        <html>
        <body>
            <p>Bonjour $prenom $nom,</p><br>
            <p>Vous pouvez dès à présent consulter l'avancement de la prise en charge de votre matériel en vous connectant sur <a href='https://repairdesk.sendix.fr'>RepairDesk</a></p><br>
            <p>Vous recevrez un message lorsque celui-ci sera prêt.</p><br>
            <p><a href='https://sendix.fr'>SENDIX</a></p>
        </body>
        </html>
        ";

        $headers = "From: repairdesk@sendix.fr\r\n";
        $headers .= "Reply-To: repairdesk@sendix.fr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'email.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Email invalide.']);
    }
    exit;
}

// Fonction pour envoyer l'e-mail Status Terminer
function sendEmail($ticketId) {
    $conn = connectDB();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT client.email, client.nom, client.prenom, ticket.titre FROM ticket INNER JOIN client ON ticket.id_client = client.id_client WHERE ticket.id = ?");
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $email = $row['email'];
    $nom = $row['nom'];
    $prenom = $row['prenom'];
    $subject = "SENDIX - Votre matériel est prêt";
    $message = "
    <html>
    <body>
        <p>Bonjour $prenom $nom,</p><br>
        <p>Nous vous informons que la réparation de votre matériel est terminée.</p>
        <p>Vous pouvez dès à présent venir le récupérer dans notre boutique.</p><br>
        <p><a href='https://sendix.fr'>SENDIX</a></p>
    </body>
    </html>
    ";

    $headers = "From: repairdesk@sendix.fr\r\n";
    $headers .= "Reply-To: repairdesk@sendix.fr\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    if (!mail($email, $subject, $message, $headers)) {
        throw new Exception("Email sending failed");
    }

    $stmt->close();
    $conn->close();
}


// Connexion BDD
$conn = connectDB();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT ticket.*, client.nom AS client_nom, client.prenom AS client_prenom, client.email, client.telephone FROM ticket INNER JOIN client ON ticket.id_client = client.id_client";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><!--Inclusion JQuery-->
    <script src="scripts.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="icon" href="assets/img/favicon.png" sizes="256x256">
    <title>SENDIX RepairDesk - Dashboard</title>
<style>
</style>
</head>
<body>
<div class="container"><br><br>
<div class="themeSelect-container">
    <select id="themeSelect" class="themeSelect">
        <option value="light">Thème Clair</option>
        <option value="dark">Thème Sombre</option>
        </select>
</div>     
<div class="logo-container mb-3">
    <img src="assets/img/RepairDeskLogoFinalFondFonce.png" alt="Logo" height="200px">
</div>
<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTicketModal" name="add_ticket">Ajouter un ticket</button>
</div>
<div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="Rechercher...">
</div>
<table id="ticketTable" class="table table-bordered table-striped mt-3">
<thead>
<tr>
    <th>Id_ticket</th>
    <th>Status</th>
    <th>Priorité</th>
    <th>Titre</th>
    <th>Modèle</th>
    <th>Marque</th>
    <th>Diagnostique</th>
    <th>Mdp Session</th>
    <th>Id_Client</th>
    <th>Date et Heure</th>
    <th>Détails</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php
    $conn = connectDB();
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Gestion du rafraîchissement des tâches
    if (isset($_GET['refresh_tasks']) && isset($_GET['ticket_id'])) {
        $ticketId = intval($_GET['ticket_id']);
        
        $sql_taches = 'SELECT * FROM tache WHERE id_ticket=' . $ticketId;
        $result_taches = $conn->query($sql_taches);
        if ($result_taches->num_rows > 0) {
            while ($row_tache = $result_taches->fetch_assoc()) {
                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                echo "<div class='form-check'>";
                echo "<input class='form-check-input taskCheckbox' type='checkbox' id='taskCheckbox" . $row_tache['id'] . "' value='" . $row_tache['id'] . "'";
                if ($row_tache['checked'] == 1) {
                    echo " checked";
                }
                echo ">";
                echo "<label class='form-check-label' for='taskCheckbox" . $row_tache['id'] . "'>" . htmlspecialchars($row_tache["contenu"]) . "</label>";
                echo "</div>";
                echo "<div>";
                echo "<button class='btn btn-warning' type='button' data-bs-toggle='modal' data-bs-target='#editTaskModal' onclick='prepareEditTask(" . $row_tache['id'] . ", \"" . htmlspecialchars($row_tache["contenu"]) . "\")'>Modifier</button> ";
                echo "<button class='btn btn-danger delete-task-btn' data-task-id='" . $row_tache["id"] . "'>Supprimer</button>";
                echo "</div>";
                echo "</li>";
            }
        } else {
            echo '<p>Aucune tâche pour le moment.</p>';
        }
        exit;
    }
    
    // Affichage normal des tickets
    $sql = "SELECT ticket.*, client.nom AS client_nom, client.prenom AS client_prenom, client.email, client.telephone FROM ticket INNER JOIN client ON ticket.id_client = client.id_client";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusClass = "";
            if ($row["status"] == "En attente") {
                $statusClass = "bg-en-attente";
            } elseif ($row["status"] == "En cours") {
                $statusClass = "bg-en-cours";
            } elseif ($row["status"] == "Terminé") {
                $statusClass = "bg-termine";
            }

            $priorityClass = "";
            if ($row["priorite"] == "Haute") {
                $priorityClass = "priority-haute";
            } elseif ($row["priorite"] == "Moyenne") {
                $priorityClass = "priority-moyenne";
            } elseif ($row["priorite"] == "Basse") {
                $priorityClass = "priority-basse";
            }
            echo "<tr class='{$statusClass}'>";
                echo "<td>". $row["id"]. "</td>";
                echo "<td>";
                echo "<select class=\"form-select status-select\" data-ticket-id=\"" . $row["id"] . "\" data-previous-value=\"" . $row["status"] . "\">";
                echo "<option value=\"En attente\" class=\"bg-en-attente\"" . ($row["status"] == "En attente" ? " selected" : "") . ">En attente</option>";
                echo "<option value=\"En cours\" class=\"bg-en-cours\"" . ($row["status"] == "En cours" ? " selected" : "") . ">En cours</option>";
                echo "<option value=\"Terminé\" class=\"bg-termine\"" . ($row["status"] == "Terminé" ? " selected" : "") . ">Terminé</option>";
                echo "</select>";
                echo "</td>";
                echo "<td>";
                echo "<div class='eco'>";
                echo "<div class='priority-card " . $priorityClass . "'>";
                echo $row["priorite"];
                echo "</div>";
                echo "</div>";
                echo "</td>" ;
                echo "<td>". $row["titre"]. "</td>";
                echo "<td>". $row["Model"]. "</td>";
                echo "<td>". $row["Marque"]. "</td>";
                echo "<td>". $row["Diagnostique"]. "</td>";
                echo "<td>". $row["Mdp_Session"]. "</td>";
                echo "<td>". $row["id_client"]. "</td>";
                echo "<td>". $row["date_depot"]. "</td>";
                echo "<td>";
                echo "<button class='btn btn-info' type='button' data-bs-toggle='collapse' data-bs-target='#collapse". $row["id"]. "' aria-expanded='false' aria-controls='collapse". $row["id"]. "'>Détails</button>";
                echo "</td>";
                echo "<td>";
                echo "<button type='button' class='btn btn-danger delete-ticket-btn' data-ticket-id='" . $row["id"] . "'>Supprimer</button>";
                echo "</td>";
                echo "</tr>";
                echo "</td>";
                echo "</tr>";
                echo "<tr class='collapse-row' id='collapse" . $row["id"] . "'>";
                echo "<td colspan='12' class='p-0'>";
                echo "<div class='collapse-content'>";
                echo "<div class='row'>";
                // Colonne pour les détails du client
                echo "<div class='col-md-6'>";
                echo "<div class='card'>";
                echo "<div class='card-body'>";
                echo "<h5>Détails du client</h5>";
                echo "<p>Nom: " . $row["client_nom"] . "</p>";
                echo "<p>Prénom: " . $row["client_prenom"] . "</p>";
                echo "<p>Email: " . $row["email"] . "</p>";
                echo "<p>Téléphone: " . $row["telephone"] . "</p>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                // Colonne pour les tâches
                echo "<div class='col-md-6'>";
                echo "<div class='card'>";
                echo "<div class='card-body'>";
                echo "<h5>Tâches</h5>";
                $sql_taches = 'SELECT * FROM tache WHERE id_ticket=' . $row['id'];
                $result_taches = $conn->query($sql_taches);
                if ($result_taches->num_rows > 0) {
                    echo "<form class='tasksForm'>";
                    echo "<ul class='list-group'>";
                    while ($row_tache = $result_taches->fetch_assoc()) {
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                        echo "<div class='form-check'>";
                        echo "<input class='form-check-input taskCheckbox' type='checkbox' id='taskCheckbox" . $row_tache['id'] . "' value='" . $row_tache['id'] . "'";
                        if ($row_tache['checked'] == 1) {
                            echo " checked";
                        }
                        echo ">";
                        echo "<label class='form-check-label' for='taskCheckbox" . $row_tache['id'] . "'>" . htmlspecialchars($row_tache["contenu"]) . "</label>";
                        echo "</div>";
                        echo "<div>";
                        echo "<button class='btn btn-warning' type='button' data-bs-toggle='modal' data-bs-target='#editTaskModal' onclick='prepareEditTask(" . $row_tache['id'] . ", \"" . htmlspecialchars($row_tache["contenu"]) . "\")'>Modifier</button> ";
                        echo "<button class='btn btn-danger delete-task-btn' data-task-id='" . $row_tache["id"] . "'>Supprimer</button>";                        echo "</div>";
                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</form>";
                } else {
                    echo '<p>Aucune tâche pour le moment.</p>';
                }
                // Formulaire d'ajout de tâche
                echo "<form class='add-task-form'><br>";
                echo "<input type='hidden' name='ticket_id' value='" . $row["id"] . "'>";
                echo "<div class='row'>";
                echo "<div class='col-md-9'>";
                echo "<div class='input-group'>";
                echo "<input type='text' name='task_content' class='form-control' placeholder='Nouvelle tâche' required>";
                echo "<button type='submit' class='btn btn-primary'>Ajouter tâche</button>";
                echo "</div>";
                echo "</div>";
                echo "<div class='col-md-3 text-end'>";
                echo "<button type='button' class='mail-button' data-ticket-id='" . $row['id'] . "' data-email='" . htmlspecialchars($row['email']) . "'>";
                echo "<img src='mail.png' alt='Envoyer un email' />";
                echo "</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>"; 
                echo "</td>";
                echo "</tr>";
            }
        }
        $conn->close();
        ?>
    </tbody>
  </table>
</div>
<!-- Modal de modification de tâche -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">Modifier la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm" method="POST" action="dashboard.php">
                    <input type="hidden" id="editTaskId" name="editTaskId" value="">
                    <div class="mb-3">
                        <label for="editTaskContent" class="form-label">Contenu de la tâche</label>
                        <input type="text" class="form-control" id="editTaskContent" name="editTaskContent" value="" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal pour ajouter un ticket -->
<div class="modal fade" id="addTicketModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Ajouter un ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="ticketForm" method="POST" action="add_ticket.php">
          <input type="hidden" name="action" value="add_ticket">
          <div class="mb-3">
            <label for="ticketTitle" class="form-label">Titre *</label>
            <input type="text" class="form-control" id="ticketTitle" name="titre" required>
          </div>
          <div class="mb-3">
            <label for="ticketPriority" class="form-label">Priorité *</label>
            <select class="form-select" id="ticketPriority" name="priorite" required>
              <option value="Basse">Basse</option>
              <option value="Moyenne">Moyenne</option>
              <option value="Haute">Haute</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="ticketModel" class="form-label">Modèle</label>
            <input type="text" class="form-control" id="ticketModel" name="model" required>
          </div>
          <div class="mb-3">
            <label for="ticketBrand" class="form-label">Marque</label>
            <input type="text" class="form-control" id="ticketBrand" name="marque" required>
          </div>
          <div class="mb-3">
            <label for="ticketDiagnosis" class="form-label">Diagnostique</label>
            <input type="text" class="form-control" id="ticketDiagnosis" name="diagnostique" required>
          </div>
          <div class="mb-3">
            <label for="sessionPassword" class="form-label">Mot de passe de session</label>
            <input type="password" class="form-control" id="sessionPassword" name="mdp_session" required>
          </div>
          <div class="mb-3">
            <label for="clientLastName" class="form-label">Nom *</label>
            <input type="text" class="form-control" id="clientLastName" name="nom" required>
          </div>
          <div class="mb-3">
            <label for="clientFirstName" class="form-label">Prénom *</label>
            <input type="text" class="form-control" id="clientFirstName" name="prenom" required>
          </div>
          <div class="mb-3">
            <label for="clientEmail" class="form-label">Email *</label>
            <input type="email" class="form-control" id="clientEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label for="clientPhone" class="form-label">Téléphone</label>
            <input type="tel" class="form-control" id="clientPhone" name="telephone" required>
          </div>
          <div class="mb-3">
            <label for="ticketStatus" class="form-label">Statut *</label>
            <select class="form-select" id="ticketStatus" name="status" required>
              <option value="En attente">En attente</option>
              <option value="En cours">En cours</option>
              <option value="Terminé">Terminé</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary" id="submitButton">Ajouter</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {

if (!jQuery.expr[':'].contains) {
jQuery.expr[':'].contains = function(a, i, m) {
    return jQuery(a).text().toUpperCase()
        .indexOf(m[3].toUpperCase()) >= 0;
};
}  document.querySelectorAll('.mail-button').forEach(button => {
    button.addEventListener('click', function() {
        const ticketId = this.getAttribute('data-ticket-id');
        const email = this.getAttribute('data-email');
        confirmMail(ticketId, email);
    });
});
//Ajout de ticket
document.getElementById('submitButton').addEventListener('click', function() {
const form = document.getElementById('ticketForm');
const formData = new FormData(form);
fetch('add_ticket.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Ticket ajouté avec succès.');
        $('#addTicketModal').modal('hide');
        location.reload();
    } else {
        alert('Erreur : ' + data.message);
    }
})
.catch(error => {
    console.error('Erreur lors de l\'ajout du ticket :', error);
    alert('Erreur lors de l\'ajout du ticket.');
});
});

// Confirmation mail
window.confirmMail = function(ticketId, email) {
    console.log('ticketId:', ticketId);
    console.log('email:', email);

    if (!email) {
        console.error(`Email non trouvé pour le ticket ${ticketId}`);
        alert("Adresse e-mail non trouvée pour ce ticket.");
        return;
    }

    if (confirm('Voulez-vous envoyer un e-mail à ' + email + ' ?')) {
        fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_email&email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email envoyé avec succès.');
            } else {
                alert('Erreur lors de l\'envoi de l\'email : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi de l\'email :', error);
            alert('Erreur lors de l\'envoi de l\'email.');
        });
    }
}

//Changement de status
function applyStatusBackground(selectElement) {
const status = selectElement.value;
selectElement.classList.remove('bg-en-attente', 'bg-en-cours', 'bg-termine');
if (status === 'En attente') {
    selectElement.classList.add('bg-en-attente');
} else if (status === 'En cours') {
    selectElement.classList.add('bg-en-cours');
} else if (status === 'Terminé') {
    selectElement.classList.add('bg-termine');
}
}

document.querySelectorAll('.status-select').forEach(select => {
applyStatusBackground(select);
select.addEventListener('change', function() {
    applyStatusBackground(select);
});
});

//Ajout Tache
document.querySelectorAll('.add-task-form').forEach(form => {
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    const ticketId = formData.get('ticket_id');

    fetch('dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php?openCollapse=' + ticketId;
        } else {
            alert('Erreur : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'ajout de la tâche :', error);
        alert('Erreur lors de l\'ajout de la tâche.');
    });
});
});
//Open Collapse General
function openCollapseAfterLoad() {
const urlParams = new URLSearchParams(window.location.search);
const collapseToOpen = urlParams.get('openCollapse');
if (collapseToOpen) {
    const collapseElement = document.querySelector('#collapse' + collapseToOpen);
    if (collapseElement) {
        new bootstrap.Collapse(collapseElement).show();

        urlParams.delete('openCollapse');
        window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
    }
}
}

openCollapseAfterLoad();

function refreshTasks(ticketId) {
fetch(`dashboard.php?refresh_tasks=1&ticket_id=${ticketId}`)
    .then(response => response.text())
    .then(html => {
        const taskList = document.querySelector(`#collapse${ticketId} .tasksForm ul`);
        if (taskList) {
            taskList.innerHTML = html;
        }
        const collapseElement = document.querySelector(`#collapse${ticketId}`);
        if (collapseElement) {
            new bootstrap.Collapse(collapseElement, { toggle: false }).show();
        }
    })
    .catch(error => {
        console.error('Erreur lors du rafraîchissement des tâches:', error);
    });
}

document.getElementById('editTaskForm').addEventListener('submit', function(e) {
e.preventDefault();

const form = document.getElementById('editTaskForm');
const formData = new FormData(form);
const taskId = formData.get('editTaskId');

//Modifier Tache
fetch('dashboard.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Tâche modifiée avec succès.');
        $('#editTaskModal').modal('hide');
        const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
        if (taskElement) {
            const ticketId = taskElement.closest('.collapse').id.replace('collapse', '');
            window.location.href = `dashboard.php?openCollapse=${ticketId}`;
        } else {
            location.reload();
        }
    } else {
        alert('Erreur : ' + data.message);
    }
})
.catch(error => {
    console.error('Erreur lors de la modification de la tâche :', error);
    alert('Erreur lors de la modification de la tâche.');
});
});

document.body.addEventListener('click', function(event) {
if (event.target.classList.contains('form-check-input')) {
    var taskId = event.target.value;
    handleTaskCheck(taskId);
}
});
//Check Task
function handleTaskCheck(taskId) {
var checkbox = document.getElementById('taskCheckbox' + taskId);
var checked = checkbox.checked ? 1 : 0;

var xhr = new XMLHttpRequest();
xhr.open('POST', 'dashboard.php', true);
xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
xhr.onload = function() {
    if (xhr.status === 200) {
        alert('Statut de la tâche mis à jour avec succès');
    } else {
        console.error('Erreur lors de la mise à jour du statut de la tâche');
    }
};
xhr.send('action=update_task&task_id=' + taskId + '&checked=' + checked);
}

window.prepareEditTask = function(taskId, taskContent) {
document.getElementById('editTaskId').value = taskId;
document.getElementById('editTaskContent').value = taskContent;
}
// Gestion de la suppression de ticket et du client associé via AJAX
document.querySelectorAll('.delete-ticket-btn').forEach(button => {
button.addEventListener('click', function() {
    const ticketId = this.getAttribute('data-ticket-id');
    if (confirm('Êtes-vous sûr de vouloir supprimer ce ticket et le client associé ?')) {
        fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'delete_ticket=1&ticket_id=' + ticketId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Ticket et client associé supprimés avec succès.');
                this.closest('tr').remove();
                const detailsRow = document.getElementById('collapse' + ticketId);
                if (detailsRow) {
                    detailsRow.remove();
                }
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression :', error);
            alert('Erreur lors de la suppression.');
        });
    }
});
});

// Gestion de la suppression de tâche via AJAX
document.body.addEventListener('click', function(event) {
if (event.target.classList.contains('delete-task-btn')) {
    const taskId = event.target.getAttribute('data-task-id');
    const ticketId = event.target.closest('.collapse').id.replace('collapse', '');
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'deleteTaskId=' + taskId
        })
        .then(response => response.text())
        .then(data => {
            console.log('Réponse brute du serveur:', data); 
            try {
                const jsonData = JSON.parse(data);
                if (jsonData.success) {
                    alert('Tâche supprimée avec succès.');
                    // Reload the page with the collapse open
                    window.location.href = `dashboard.php?openCollapse=${ticketId}`;
                } else {
                    alert('Erreur lors de la suppression de la tâche : ' + jsonData.message);
                }
            } catch (error) {
                console.error('Erreur lors du parsing JSON:', error);
                alert('Erreur lors du traitement de la réponse du serveur.');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression de la tâche :', error);
            alert('Erreur lors de la suppression de la tâche.');
        })
        .finally(() => {
            // Reload the page after processing the task deletion
            window.location.reload();
        });
    }
}
});

//Filtre de recherhce
const searchInput = document.getElementById('searchInput');
const table = document.getElementById('ticketTable');
const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

searchInput.addEventListener('keyup', function() {
const filter = searchInput.value.toLowerCase();
for (let i = 0; i < rows.length; i++) {
    let row = rows[i];
    let cells = row.getElementsByTagName('td');
    let rowText = '';
    for (let j = 0; j < cells.length; j++) {
        rowText += cells[j].textContent.toLowerCase() + ' ';
    }
    if (rowText.includes(filter)) {
        row.style.display = '';
    } else {
        row.style.display = 'none';
    }
}
});

document.querySelectorAll('.status-select').forEach(select => {
select.addEventListener('change', function() {
    const ticketId = this.getAttribute('data-ticket-id');
    const newStatus = this.value;

    if (confirm('Êtes-vous sûr de vouloir changer le statut de ce ticket ?')) {
        $.ajax({
url: 'dashboard.php',
type: 'POST',
dataType: 'json', // Spécifiez que vous attendez une réponse JSON
data: { ticketId: ticketId, newStatus: newStatus },
success: function(response) {
    if (response.success) {
        // Mise à jour réussie
        applyStatusBackground(select);
    } else {
        alert('Erreur : ' + response.message);
        // Rétablir l'ancien statut
        select.value = select.getAttribute('data-previous-value');
        applyStatusBackground(select);
    }
},
error: function(xhr, status, error) {
    console.error('Erreur lors du changement de statut du ticket :', error);
    alert('Erreur lors du changement de statut du ticket.');
    // Rétablir l'ancien statut
    select.value = select.getAttribute('data-previous-value');
    applyStatusBackground(select);
    }}
    )}
});
});
});
</script>
</body>
</html>
