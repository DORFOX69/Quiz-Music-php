<?php
// classes/Badge.php

class Badge {
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Vérifie si l'utilisateur a déjà le badge
    public function hasBadge(int $user_id, int $badge_id): bool {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM user_badges WHERE user_id = :user_id AND badge_id = :badge_id"
        );
        $stmt->execute([
            ':user_id' => $user_id,
            ':badge_id' => $badge_id
        ]);
        return $stmt->rowCount() > 0;
    }

    // Attribuer un badge à un utilisateur
    public function giveBadge(int $user_id, int $badge_id) {
        if (!$this->hasBadge($user_id, $badge_id)) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)"
            );
            $stmt->execute([
                ':user_id' => $user_id,
                ':badge_id' => $badge_id
            ]);
        }
    }

    // Récupérer tous les badges d'un utilisateur
    public function getUserBadges(int $user_id): array {
        $stmt = $this->pdo->prepare(
            "SELECT b.* 
             FROM badges b
             INNER JOIN user_badges ub ON b.id = ub.badge_id
             WHERE ub.user_id = :user_id"
        );
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// FIN DU FICHIER – PAS DE ?> À LA FIN
