<?php
namespace services\campanha;

use models\campanha\enums\TipoAlvo;
use models\Comentario;
use models\Curtida;
use modules\db\Database;

class ComentarioService
{
    public static function criar_comentario(Comentario $comentario, int $idUsuario): string
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $result = Comentario::criar_comentario($comentario, $idUsuario);
            $pdo->commit();
            return $result;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function curtir_comentario(int $idComentario, string $idUsuario) {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $curtida = new Curtida();
            $curtida->idAlvo = $idComentario;
            $curtida->idUsuario = $idUsuario;
            $curtida->tipoAlvo = TipoAlvo::COMENTARIO->value;
            $removerCurtida = Curtida::salvar_curtida($curtida);
            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
