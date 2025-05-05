document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.getElementById('themeSelect');
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.body.classList.add(`${currentTheme}-theme`);
    
    if (themeSelect) {
        themeSelect.value = currentTheme;
        themeSelect.addEventListener('change', function() {
            document.body.classList.remove('light-theme', 'dark-theme');
            document.body.classList.add(`${this.value}-theme`);
            localStorage.setItem('theme', this.value);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
    const submitButton = document.getElementById('submitButton');
    const form = document.getElementById('ticketForm');

    if (submitButton && form) {
        submitButton.addEventListener('click', function () {
            console.log('Submit button clicked');
            const formData = new FormData(form);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    } else {
        console.error('Submit button or form not found');
    }
});
    //Ajouter tache
    document.addEventListener("DOMContentLoaded", function () {
        const addTaskForm = document.getElementById("addTaskForm");
    
        if (addTaskForm) {
            addTaskForm.addEventListener("submit", function (event) {
                event.preventDefault(); // Empêche la soumission du formulaire de provoquer une redirection
    
                const formData = new FormData(addTaskForm);
    
                fetch("dashboard.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Rafraîchir les tâches ici
                        loadTasks();
                    } else {
                        console.error("Erreur lors de l'ajout de la tâche");
                    }
                })
                .catch(error => console.error("Erreur:", error));
            });
        }
    
    });
    

    // Bouton envoyer mail
    window.confirmMail = function(ticketId) {
        const emailElement = document.getElementById("emailCollapse" + ticketId);
        const email = emailElement.querySelector("p").innerText.trim();
        if (confirm("Voulez-vous envoyer un mail à " + email + " ?")) {
            const formData = new FormData();
            formData.append("send_email", true);
            formData.append("email", email);
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .catch(error => console.error('Erreur:', error));
        }
    };

});
