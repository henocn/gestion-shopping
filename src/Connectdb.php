<?php

namespace Src;

use PDO;
use Exception;

class Connectdb
{

    private static function connect()
    {
        try {
            // Chercher le fichier .env dans le dossier src
            $file = __DIR__ . "/.env";
            $config = parse_ini_file($file, true);

            $host = $config['database']['host'];
            $dbname = $config['database']['dbname'];
            $username = $config['database']['username'];
            $password = $config['database']['password'];
            $charset = $config['database']['charset'];

            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            $cnx = new PDO($dsn, $username, $password);
            $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $cnx;
        } catch (Exception $error) {
            die('Error : ' . $error->getMessage());
        }
    }

    public static function getConnection()
    {
        return self::connect();
    }
}
