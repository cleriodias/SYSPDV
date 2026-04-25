<?php

declare(strict_types=1);

set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dumpPath = __DIR__ . '/bkp/paoecafe8302.sql';

if (! is_file($dumpPath)) {
    fwrite(STDERR, "Backup nao encontrado: {$dumpPath}\n");
    exit(1);
}

$content = file_get_contents($dumpPath);

if ($content === false) {
    fwrite(STDERR, "Nao foi possivel ler o backup.\n");
    exit(1);
}

$allowedInsertTables = [
    'billing_plan_settings',
    'matrizes',
    'tb1_produto',
    'tb2_unidades',
    'tb2_unidade_user',
    'tb3_vendas',
    'tb4_vendas_pg',
    'tb21_usuarios_online',
    'tb26_configuracoes_fiscais',
    'tb27_notas_fiscais',
    'tb_17_configuracao_descarte',
    'users',
];

$statements = splitSqlStatements($content);
$pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
$executed = 0;
$skipped = 0;

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($statements as $index => $statement) {
        $sql = trim($statement);

        if ($sql === '') {
            continue;
        }

        if (shouldExecuteStatement($sql, $allowedInsertTables)) {
            $pdo->exec($sql);
            $executed++;
            fwrite(STDOUT, sprintf("EXECUTADO %d\n", $index + 1));
            continue;
        }

        $skipped++;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $counts = [
        'matrizes' => (int) Illuminate\Support\Facades\DB::table('matrizes')->count(),
        'users' => (int) Illuminate\Support\Facades\DB::table('users')->count(),
        'tb2_unidades' => (int) Illuminate\Support\Facades\DB::table('tb2_unidades')->count(),
        'tb2_unidade_user' => (int) Illuminate\Support\Facades\DB::table('tb2_unidade_user')->count(),
    ];

    fwrite(STDOUT, "RESTORE_OK\n");
    fwrite(STDOUT, 'EXECUTED=' . $executed . "\n");
    fwrite(STDOUT, 'SKIPPED=' . $skipped . "\n");

    foreach ($counts as $table => $count) {
        fwrite(STDOUT, strtoupper($table) . '=' . $count . "\n");
    }
} catch (Throwable $exception) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Throwable $ignored) {
    }

    fwrite(STDERR, "RESTORE_ERROR\n");
    fwrite(STDERR, get_class($exception) . "\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

function shouldExecuteStatement(string $sql, array $allowedInsertTables): bool
{
    $normalized = ltrim($sql);

    if (str_starts_with($normalized, 'SET SQL_MODE')
        || str_starts_with($normalized, 'SET time_zone')
        || str_starts_with($normalized, '/*!40101 SET')
        || str_starts_with($normalized, 'COMMIT')
        || str_starts_with($normalized, 'START TRANSACTION')) {
        return true;
    }

    if (str_starts_with($normalized, 'CREATE TABLE IF NOT EXISTS')) {
        return true;
    }

    if (preg_match('/^INSERT INTO `([^`]+)`/i', $normalized, $matches) === 1) {
        return in_array($matches[1], $allowedInsertTables, true);
    }

    return false;
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && ! $inDoubleQuote && $prev !== '\\') {
            $inSingleQuote = ! $inSingleQuote;
        } elseif ($char === '"' && ! $inSingleQuote && $prev !== '\\') {
            $inDoubleQuote = ! $inDoubleQuote;
        }

        $buffer .= $char;

        if ($char === ';' && ! $inSingleQuote && ! $inDoubleQuote) {
            $statements[] = $buffer;
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
