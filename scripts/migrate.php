<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/db.php';

db()->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    version TEXT PRIMARY KEY,
    applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$applied = array_column(db_all('SELECT version FROM schema_migrations'), 'version');
$applied = array_flip($applied);

$files = glob(COS_MIGRATIONS . '/*.sql') ?: [];
sort($files, SORT_STRING);

$ran = 0;
foreach ($files as $path) {
    $version = basename($path, '.sql');
    if (isset($applied[$version])) continue;

    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Could not read $path\n");
        exit(1);
    }

    db()->beginTransaction();
    try {
        db()->exec($sql);
        db_q('INSERT INTO schema_migrations (version) VALUES (?)', [$version]);
        db()->commit();
        echo "Applied: $version\n";
        $ran++;
    } catch (Throwable $e) {
        db()->rollBack();
        fwrite(STDERR, "Failed: $version — " . $e->getMessage() . "\n");
        exit(1);
    }
}

if ($ran === 0) {
    echo "Nothing to apply. Schema is up to date.\n";
}
