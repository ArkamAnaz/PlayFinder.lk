<?php
/**
 * Database connection using PDO (prepared statements protect against SQL injection).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('<div style="font-family:sans-serif;padding:40px;max-width:640px;margin:auto">
                <h2>Database connection failed</h2>
                <p>Please make sure MySQL is running in XAMPP and that you imported
                <code>database/playfinder.sql</code> in phpMyAdmin.</p>
                <p style="color:#888">Check settings in <code>config/config.php</code>.</p></div>');
        }
    }
    return $pdo;
}

/** Run a query and return the statement */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch a single row (or null) */
function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Fetch all rows */
function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Fetch a single value */
function q_val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}
