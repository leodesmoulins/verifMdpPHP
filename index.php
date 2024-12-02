<?php
// Paramètres de connexion à la base de données
include 'connexion.php';

function estMotDePasseValide($motDePasse) {
    if (strlen($motDePasse) < 12) {
        return false;
    }
    if (!preg_match('/[a-z]/', $motDePasse)) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $motDePasse)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $motDePasse)) {
        return false;
    }
    if (!preg_match('/[\W]/', $motDePasse)) {
        return false;
    }
    return true;
}

function hasherMotDePasse($motDePasse) {
    return password_hash($motDePasse, PASSWORD_DEFAULT);
}

function verifierTentatives($pdo, $mail_user) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE mail_user = ? AND date_password > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $stmt->execute([$mail_user]);
    return $stmt->fetchColumn();
}

function ajouterTentative($pdo, $mail_user) {
    $stmt = $pdo->prepare("INSERT INTO user (mail_user, date_password) VALUES (?, NOW())");
    $stmt->execute([$mail_user]);
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail_user = $_POST["mail_user"] ?? '';
    $motDePasse = $_POST["motDePasse"] ?? '';

    $tentatives = verifierTentatives($pdo, $mail_user);
    if ($tentatives >= 3) {
        $message = "Trop de tentatives. Veuillez réessayer plus tard.";
    } elseif (estMotDePasseValide($motDePasse)) {
        $hashMotDePasse = hasherMotDePasse($motDePasse);
        $message = "Le mot de passe est valide et a été haché. Hash : " . $hashMotDePasse;
        
        // Ici, vous pouvez ajouter le code pour enregistrer le hash dans la base de données
        // Par exemple :
        // $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE mail_user = ?");
        // $stmt->execute([$hashMotDePasse, $mail_user]);
        
    } else {
        $message = "Le mot de passe n'est pas valide. Il doit contenir au moins 12 caractères, une minuscule, une majuscule, un chiffre et un caractère spécial.";
        ajouterTentative($pdo, $mail_user);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation et hachage de mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        form { display: flex; flex-direction: column; }
        input, button { margin: 10px 0; padding: 5px; }
        .message { margin-top: 20px; padding: 10px; background-color: #f0f0f0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Validation et hachage de mot de passe</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="mail_user">Adresse e-mail :</label>
        <input type="email" id="mail_user" name="mail_user" required>
        <label for="motDePasse">Entrez un mot de passe :</label>
        <input type="password" id="motDePasse" name="motDePasse" required>
        <button type="submit">Vérifier et Hacher</button>
    </form>
    <?php
    if (!empty($message)) {
        echo "<div class='message'>$message</div>";
    }
    ?>
</body>
</html>