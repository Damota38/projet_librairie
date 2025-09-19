<?php

require_once 'config.php';

//GET

$search = $_GET['search'] ?? '';

$page = max(1, $_GET['page'] ?? 1);

$limit = 10;

$offset = ($page - 1) * $limit;

//POST

if ($_POST['action'] ?? '' == 'add') {

    $stmt = $pdo->prepare('INSERT INTO auteurs (nom, prenom, date_naissance, nationalite) VALUES (?, ?, ?, ?)');

    $stmt->execute([
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['date_naissance'],
        $_POST['nationalite'],
    ]);

    header('localhost: auteurs.php');

    exit;
}

if ($_POST['action'] ?? '' == 'edit') {

    $stmt = $pdo->prepare("UPDATE auteurs SET nom=?, prenom=?, date_naissance=?, nationalite=? WHERE id_auteur=?");

    $stmt->execute([
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['date_naissance'],
        $_POST['nationalite'],
        $_POST['id'],
    ]);

    header('Location: auteurs.php');
    exit;
}

//DELETE

if ($_GET['delete'] ?? false) {

    $stmt = $pdo->prepare("DELETE FROM auteurs WHERE id_auteurs = ?");

    $stmt->execute([$_GET['delete']]);

    header('Location: auteurs.php');
    exit;
}

$whereClause = "";
$params = [];

if ($search) {

    $whereClause = "WHERE nom LIKE ? OR prenom LIKE ? OR nationalite LIKE ?";

    $params = [
        "%$search%",
        "%$search%",
        "%$search%"
    ];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM auteurs $whereClause");

$countStmt->execute($params);

$total = $countStmt->fetchColumn();

$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM auteurs $whereClause ORDER BY nom, prenom LIMIT $limit OFFSET $offset");

$stmt->execute($params);

$auteurs = $stmt->fetchAll();

$editAuteur = null;

if ($_GET['edit'] ?? false) {

    $stmt = $pdo->prepare("SELECT * FROM auteurs WHERE id_auteur = ?");
    $stmt->execute([$_GET['edit']]);

    $editAuteur = $stmt->fetch();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Auteurs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation principale -->
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs.php">Auteurs</a>
        <a href="livres.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <!-- Conteneur principal -->
    <div class="container">
        <h1>Gestion des Auteurs</h1>

        <!-- ========================================================================
             FORMULAIRE D'AJOUT/MODIFICATION D'AUTEUR
             ======================================================================== -->
        <div class="form-container">
            <!-- Affiche "Modifier" si on édite, sinon "Ajouter" -->
            <!-- L'opérateur ternaire : condition ? si_vrai : si_faux -->
            <h3><?= $editAuteur ? 'Modifier' : 'Ajouter' ?> un auteur</h3>

            <!-- method="POST" : envoie les données sans les afficher dans l'URL -->
            <form method="POST">
                <!-- Champ caché pour indiquer l'action (add ou edit) -->
                <!-- value utilise l'opérateur ternaire pour choisir la bonne action -->
                <input type="hidden" name="action" value="<?= $editAuteur ? 'edit' : 'add' ?>">

                <!-- Si on est en mode édition, ajoute un champ caché avec l'ID -->
                <?php if ($editAuteur): ?>
                    <input type="hidden" name="id" value="<?= $editAuteur['id_auteur'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom :</label>
                        <!-- value : pré-remplit le champ avec la valeur existante si édition -->
                        <!-- ?? '' : si pas de valeur (mode ajout), met une chaîne vide -->
                        <!-- required : validation HTML5, champ obligatoire -->
                        <input type="text" name="nom" value="<?= $editAuteur['nom'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom :</label>
                        <input type="text" name="prenom" value="<?= $editAuteur['prenom'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Date de naissance :</label>
                        <!-- type="date" : affiche un sélecteur de date HTML5 -->
                        <input type="date" name="date_naissance" value="<?= $editAuteur['date_naissance'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nationalité :</label>
                        <input type="text" name="nationalite" value="<?= $editAuteur['nationalite'] ?? '' ?>">
                    </div>
                </div>

                <!-- Bouton qui change de texte selon le mode (ajout/édition) -->
                <button type="submit"><?= $editAuteur ? 'Modifier' : 'Ajouter' ?></button>

                <!-- Lien d'annulation visible uniquement en mode édition -->
                <?php if ($editAuteur): ?>
                    <a href="auteurs.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========================================================================
             BARRE DE RECHERCHE
             ======================================================================== -->
        <div class="search">
            <!-- method="GET" : envoie les données dans l'URL pour pouvoir partager le lien -->
            <form method="GET">
                <div class="grid-form">
                    <div style="flex: 1;">
                        <label>Rechercher :</label>
                        <!-- htmlspecialchars() : convertit les caractères spéciaux en entités HTML -->
                        <!-- Évite les attaques XSS en empêchant l'injection de code HTML/JS -->
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom ou nationalité">
                    </div>
                    <button type="submit">Rechercher</button>
                    <!-- Lien pour réinitialiser la recherche (retour à la page normale) -->
                    <a href="auteurs.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- ========================================================================
             TABLEAU D'AFFICHAGE DES AUTEURS
             ======================================================================== -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date de naissance</th>
                    <th>Nationalité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- foreach : boucle sur chaque auteur du tableau $auteurs -->
                <!-- $auteur contient les données d'un auteur à chaque itération -->
                <?php foreach ($auteurs as $auteur): ?>
                <tr>
                    <!-- Affichage simple de l'ID (pas besoin d'échapper, c'est un nombre) -->
                    <td><?= $auteur['id_auteur'] ?></td>

                    <!-- htmlspecialchars() sur toutes les données texte -->
                    <!-- Protège contre XSS en convertissant < > & " ' en entités HTML -->
                    <td><?= htmlspecialchars($auteur['nom']) ?></td>
                    <td><?= htmlspecialchars($auteur['prenom']) ?></td>

                    <!-- Date : pas de htmlspecialchars car format date non dangereux -->
                    <td><?= $auteur['date_naissance'] ?></td>

                    <td><?= htmlspecialchars($auteur['nationalite']) ?></td>

                    <td class="actions">
                        <!-- Lien pour éditer : ajoute ?edit=ID à l'URL -->
                        <a href="?edit=<?= $auteur['id_auteur'] ?>" class="btn-edit">Modifier</a>

                        <!-- Lien pour supprimer : ajoute ?delete=ID à l'URL -->
                        <!-- onclick : demande confirmation JavaScript avant suppression -->
                        <!-- return false annule le clic si l'utilisateur clique sur Annuler -->
                        <a href="?delete=<?= $auteur['id_auteur'] ?>" class="btn-delete"
                           onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ========================================================================
             PAGINATION
             ======================================================================== -->
        <!-- N'affiche la pagination que s'il y a plus d'une page -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <!-- Boucle for pour créer les liens de pagination -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <!-- Construit l'URL avec le numéro de page -->
                <!-- urlencode() : encode les caractères spéciaux pour l'URL -->
                <!-- Si recherche active, ajoute &search=terme à l'URL -->
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================================================
             INFORMATIONS DE PAGINATION
             ======================================================================== -->
        <!-- Affiche le total et la position actuelle dans la pagination -->
        <p>Total : <?= $total ?> auteur(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>