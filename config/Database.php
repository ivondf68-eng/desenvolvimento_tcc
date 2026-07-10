<?php
// Classe de conexão PDO usada por toda a aplicação.
class Database
{
    private static ?PDO $instancia = null; // Propriedade nova

    public  static function conectar(): PDO
    {
        if (self::$instancia === null) {
            // Criar a conexão apenas umab vez
            $host    = 'db'; // nome do serviço no docker-compose.yml
            $porta   = '5432';
            $banco   = 'meu_banco';
            $usuario = 'meu_usuario';
            $senha   = 'minha_senha';

            $dsn = "pgsql:host={$host};port={$porta};dbname={$banco}";

            self::$instancia = new PDO($dsn, $usuario, $senha, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$instancia;
    }
}
