<?php
/**
 * db.php
 * -----------------------------------------------------------
 * Connexion centralisée à la base MySQL via PDO.
 * On l'inclut (require) dans tous les fichiers qui ont besoin
 * de la base, pour ne pas répéter les identifiants partout.
 * -----------------------------------------------------------
 */

// ----- Paramètres de connexion (à adapter à ton serveur) -----
$db_host = "localhost";
$db_name = "salle_serveur";   // nom de ta base
$db_user = "user_dashboard";  // utilisateur MySQL dédié (pas root !)
$db_pass = "motdepasse";      // mot de passe de cet utilisateur

try {
    // DSN = chaîne de connexion. charset=utf8mb4 pour gérer les accents.
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

    $pdo = new PDO($dsn, $db_user, $db_pass, [
        // Lève une exception en cas d'erreur SQL (plus facile à debugger)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Les résultats sont retournés sous forme de tableaux associatifs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sécurité : on désactive l'émulation des requêtes préparées
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // En production on n'affiche jamais le détail de l'erreur à l'utilisateur
    http_response_code(500);
    die("Erreur de connexion à la base de données.");
}
