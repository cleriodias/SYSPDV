<?php

$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'testing';
$dbConnection = $_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null;
$dbHost = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: null;
$dbDatabase = $_SERVER['DB_DATABASE'] ?? $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null;
$safeMysqlHosts = ['127.0.0.1', 'localhost'];
$isSqliteMemory = $dbConnection === 'sqlite' && $dbDatabase === ':memory:';
$isSafeMysqlTesting = $dbConnection === 'mysql'
    && in_array($dbHost, $safeMysqlHosts, true)
    && is_string($dbDatabase)
    && preg_match('/(?:^|_)(test|testing)$/i', $dbDatabase) === 1;

if (
    $appEnv === 'testing'
    && ! ($isSqliteMemory || $isSafeMysqlTesting)
) {
    fwrite(
        STDERR,
        "Ambiente de teste bloqueado: use sqlite em memoria ou MySQL local com banco terminado em test/testing.\n"
    );
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
