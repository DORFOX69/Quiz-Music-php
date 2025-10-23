<?php
// classes/Quiz.php

require_once 'Question.php';
require_once 'QuestionTexte.php';
require_once 'QuestionImage.php';
require_once 'QuestionAudio.php';
require_once 'Database.php'; // si tu as une classe pour récupérer PDO

class Quiz {
    private array $questions = [];      // Tableau d'objets Question
    private string $themeCode;          // Code du thème
    private array $themeInfo;           // Infos du thème

    public function __construct(string $themeCode) {
        $this->themeCode = $themeCode;

        $this->chargerTheme();
        $this->chargerQuestionsAleatoires();
    }

    // Charge les infos du thème
    private function chargerTheme(): void {
        $pdo = Database::getConnexion(); // récupère PDO

        $stmt = $pdo->prepare("
            SELECT *
            FROM questionnaires
            WHERE code = ? AND actif = 1
        ");
        $stmt->execute([$this->themeCode]);
        $this->themeInfo = $stmt->fetch();

        if (!$this->themeInfo) {
            throw new Exception("❌ Questionnaire introuvable ou désactivé : " . htmlspecialchars($this->themeCode));
        }
    }

    // Charge 5 questions aléatoires
    private function chargerQuestionsAleatoires(): void {
        $pdo = Database::getConnexion();

        $stmt = $pdo->prepare("
            SELECT *
            FROM questions
            WHERE questionnaire_id = ?
            ORDER BY RAND()
            LIMIT 5
        ");
        $stmt->execute([$this->themeInfo['id']]);
        $lignes = $stmt->fetchAll();

        foreach ($lignes as $ligne) {
            $reponses = [
                $ligne['reponse_a'],
                $ligne['reponse_b'],
                $ligne['reponse_c'],
                $ligne['reponse_d']
            ];

            switch ($ligne['type_question']) {
                case 'image':
                    $question = new QuestionImage(
                        $ligne['id'],
                        $ligne['question'],
                        $reponses,
                        $ligne['bonne_reponse'],
                        $ligne['media_url'],
                        $ligne['explication']
                    );
                    break;

                case 'audio':
                    $question = new QuestionAudio(
                        $ligne['id'],
                        $ligne['question'],
                        $reponses,
                        $ligne['bonne_reponse'],
                        $ligne['media_url'],
                        $ligne['explication']
                    );
                    break;

                default: // texte
                    $question = new QuestionTexte(
                        $ligne['id'],
                        $ligne['question'],
                        $reponses,
                        $ligne['bonne_reponse'],
                        $ligne['explication']
                    );
            }

            $this->questions[] = $question;
        }
    }

    // Calcule le score
    public function calculerScore(array $reponsesUtilisateur): int {
        $score = 0;
        foreach ($this->questions as $index => $question) {
            $reponseUser = (int)($reponsesUtilisateur[$index] ?? -1);
            if ($question->estCorrect($reponseUser)) {
                $score++;
            }
        }
        return $score;
    }

    // Sauvegarde le score
    public function sauvegarderScore(int $userId, int $score, ?int $tempsSecondes = null): void {
        $pdo = Database::getConnexion();

        $sql = "
            INSERT INTO scores (user_id, questionnaire_id, score, total_questions, temps_seconde)
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $this->themeInfo['id'],
            $score,
            count($this->questions),
            $tempsSecondes
        ]);
    }

    // GETTERS
    public function getQuestions(): array {
        return $this->questions;
    }

    public function getThemeInfo(): array {
        return $this->themeInfo;
    }
}
