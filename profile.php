<?php
session_start(); // Démarrage de session obligatoire

require_once 'classes/Database.php';
$pdo = Database::getConnexion(); // Connexion à la BDD

require_once 'classes/Badge.php';
$badge = new Badge($pdo); // Création de l'objet Badge

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ID de l'utilisateur connecté
$message = "";

// ==============================
// ✅ Modification du pseudo
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_pseudo'])) {
    $nouveau_pseudo = trim($_POST['nouveau_pseudo']);
    if (!empty($nouveau_pseudo)) {
        $stmt = $pdo->prepare("UPDATE users SET pseudo = ? WHERE id = ?");
        $stmt->execute([$nouveau_pseudo, $user_id]);
        $_SESSION['user_pseudo'] = $nouveau_pseudo;
        $message = "✅ Pseudo mis à jour avec succès.";
    } else {
        $message = "⚠️ Le pseudo ne peut pas être vide.";
    }
}

// ==============================
// ✅ Récupération des infos utilisateur
// ==============================
$stmt = $pdo->prepare("SELECT pseudo, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ==============================
// ✅ Nombre total de parties jouées
// ==============================
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_parties FROM historique WHERE utilisateur_id = ?");
$stmt->execute([$user_id]);
$total_parties = $stmt->fetch(PDO::FETCH_ASSOC)['total_parties'] ?? 0;

// ==============================
// ✅ Thème préféré (le plus joué)
// ==============================
$stmt = $pdo->prepare("
    SELECT q.titre, COUNT(*) AS nb
    FROM historique h
    JOIN questionnaires q ON q.id = h.questionnaire_id
    WHERE h.utilisateur_id = ?
    GROUP BY h.questionnaire_id
    ORDER BY nb DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$theme_pref = $stmt->fetch(PDO::FETCH_ASSOC);
$theme_prefere = $theme_pref ? $theme_pref['titre'] : "Aucun pour le moment";

// ==============================
// ✅ Récupération des badges
// ==============================
$userBadges = $badge->getUserBadges($user_id);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil utilisateur - QuizMusic 🎵</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-blue-900 min-h-screen text-white">
    <div class="container mx-auto px-6 py-10 max-w-3xl">

        <!-- Barre de navigation -->
        <nav class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold">👤 Mon profil</h1>
            <div class="flex gap-4">
                <a href="index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">🏠 Accueil</a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">🚪 Déconnexion</a>
            </div>
        </nav>

        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 px-4 py-3 rounded-lg 
                        <?php echo str_contains($message, '✅') ? 'bg-green-600' : 'bg-yellow-600'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Informations personnelles -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-2xl font-semibold mb-4">📋 Informations personnelles</h2>
            <p><strong>Pseudo :</strong> <?php echo htmlspecialchars($user['pseudo']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Date d'inscription :</strong> 
                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
            </p>
        </div>

        <!-- Statistiques -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-2xl font-semibold mb-4">📊 Statistiques</h2>
            <p><strong>Nombre total de parties jouées :</strong> <?php echo $total_parties; ?></p>
            <p><strong>Thème préféré :</strong> <?php echo htmlspecialchars($theme_prefere); ?></p>
        </div>

        <!-- Badges -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-2xl font-semibold mb-4">🏅 Mes badges</h2>
            <?php if (!empty($userBadges)): ?>
                <ul>
                    <?php foreach ($userBadges as $b): ?>
                        <li><strong><?php echo htmlspecialchars($b['nom']); ?></strong> : <?php echo htmlspecialchars($b['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucun badge obtenu pour l'instant.</p>
            <?php endif; ?>
        </div>

        <!-- Formulaire de modification du pseudo -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-lg">
            <h2 class="text-2xl font-semibold mb-4">✏️ Modifier mon pseudo</h2>
            <form method="POST" class="flex gap-3">
                <input type="text" name="nouveau_pseudo" 
                       placeholder="Nouveau pseudo" 
                       class="flex-grow px-4 py-2 rounded-lg text-black focus:outline-none" required>
                <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg text-white font-medium transition">
                    Mettre à jour
                </button>
            </form>
        </div>

    </div>
</body>
</html>
