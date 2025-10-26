<?php

namespace repositories;

use modules\db\Database;
use PDO;

class CampanhaRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function obter_campanha($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM Campanha WHERE idCampanha = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
