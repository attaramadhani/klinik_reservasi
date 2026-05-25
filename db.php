<?php

class DbResult
{
    public array $rows;
    private int $position = 0;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    public function fetchAssoc(): ?array
    {
        if ($this->position >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->position++];
    }

    public function numRows(): int
    {
        return count($this->rows);
    }
}

class DbConnection
{
    public string $driver;
    public $handle;
    public string $error = '';
    public ?string $lastInsertTable = null;

    public function __construct(string $driver, $handle)
    {
        $this->driver = $driver;
        $this->handle = $handle;
    }
}

function db_set_last_connect_error(string $message): void
{
    $GLOBALS['DB_LAST_CONNECT_ERROR'] = $message;
}

function db_last_connect_error(): string
{
    return $GLOBALS['DB_LAST_CONNECT_ERROR'] ?? '';
}

function db_connect_from_env(): ?DbConnection
{
    db_set_last_connect_error('');

    $databaseUrl = getenv('DATABASE_URL') ?: '';
    $defaultDriver = ($databaseUrl || getenv('PGHOST')) ? 'pgsql' : 'mysql';
    $driver = strtolower(getenv('DB_DRIVER') ?: getenv('DATABASE_DRIVER') ?: $defaultDriver);

    if ($driver === 'pgsql' || $driver === 'postgres' || $driver === 'postgresql' || $driver === 'supabase') {
        $urlParts = $databaseUrl ? parse_url($databaseUrl) : [];
        $host = getenv('DB_HOST') ?: getenv('PGHOST') ?: ($urlParts['host'] ?? '');
        $user = getenv('DB_USER') ?: getenv('PGUSER') ?: (isset($urlParts['user']) ? urldecode($urlParts['user']) : 'postgres');
        $pass = getenv('DB_PASS') ?: getenv('PGPASSWORD') ?: (isset($urlParts['pass']) ? urldecode($urlParts['pass']) : '');
        $db = getenv('DB_NAME') ?: getenv('PGDATABASE') ?: (isset($urlParts['path']) ? ltrim($urlParts['path'], '/') : 'postgres');
        $port = (int) (getenv('DB_PORT') ?: getenv('PGPORT') ?: ($urlParts['port'] ?? 5432));
        $sslmode = getenv('DB_SSLMODE') ?: 'require';
        $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";

        if ($host === '' || $user === '' || $pass === '' || $db === '') {
            db_set_last_connect_error('Konfigurasi PostgreSQL belum lengkap. Pastikan DB_HOST, DB_USER, DB_PASS, dan DB_NAME sudah di-set.');
            return null;
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            return new DbConnection('pgsql', $pdo);
        } catch (Throwable $e) {
            db_set_last_connect_error($e->getMessage());
            return null;
        }
    }

    if (!function_exists('mysqli_connect')) {
        db_set_last_connect_error('Ekstensi mysqli tidak tersedia dan DB_DRIVER tidak diset ke supabase/pgsql.');
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: "127.0.0.1";
    $user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: "root";
    $pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: "";
    $db = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: "klinik_reservasi";
    $port = (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3307);
    $handle = @mysqli_connect($host, $user, $pass, $db, $port);

    if (!$handle) {
        db_set_last_connect_error(mysqli_connect_error() ?: 'Koneksi MySQL gagal.');
        return null;
    }

    return new DbConnection('mysql', $handle);
}

function db_translate_pgsql(string $query): string
{
    $query = str_replace('`', '', $query);
    $query = preg_replace_callback(
        '/DATE_FORMAT\(([^,]+),\s*\'([^\']+)\'\)/i',
        function (array $matches): string {
            $format = strtr($matches[2], [
                '%Y' => 'YYYY',
                '%m' => 'MM',
                '%d' => 'DD',
                '%H' => 'HH24',
                '%i' => 'MI',
                '%s' => 'SS',
            ]);

            return "TO_CHAR({$matches[1]}, '{$format}')";
        },
        $query
    );
    $query = preg_replace('/\bCURDATE\(\)/i', 'CURRENT_DATE', $query);
    $query = preg_replace('/\bNOW\(\)/i', 'CURRENT_TIMESTAMP', $query);
    $query = preg_replace('/\bMONTH\(([^)]+)\)/i', 'EXTRACT(MONTH FROM $1)', $query);
    $query = preg_replace('/\bYEAR\(([^)]+)\)/i', 'EXTRACT(YEAR FROM $1)', $query);
    $query = preg_replace('/\bDATE\(([^)]+)\)/i', '($1)::date', $query);

    return $query;
}

function db_query(?DbConnection $conn, string $query)
{
    if (!$conn) {
        return false;
    }

    if (preg_match('/^\s*INSERT\s+INTO\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $query, $m)) {
        $conn->lastInsertTable = strtolower($m[1]);
    }

    if ($conn->driver === 'mysql') {
        $result = mysqli_query($conn->handle, $query);
        if ($result === false) {
            $conn->error = mysqli_error($conn->handle);
        }

        return $result;
    }

    try {
        $translated = db_translate_pgsql($query);
        $statement = $conn->handle->query($translated);

        if ($statement && $statement->columnCount() > 0) {
            return new DbResult($statement->fetchAll());
        }

        return true;
    } catch (Throwable $e) {
        $conn->error = $e->getMessage();
        return false;
    }
}

function db_fetch_assoc($result): ?array
{
    if ($result instanceof DbResult) {
        return $result->fetchAssoc();
    }

    if ($result instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($result);
        return $row === null ? null : $row;
    }

    return null;
}

function db_num_rows($result): int
{
    if ($result instanceof DbResult) {
        return $result->numRows();
    }

    if ($result instanceof mysqli_result) {
        return mysqli_num_rows($result);
    }

    return 0;
}

function db_real_escape_string(?DbConnection $conn, $value): string
{
    $value = (string) $value;

    if ($conn && $conn->driver === 'mysql') {
        return mysqli_real_escape_string($conn->handle, $value);
    }

    return str_replace("'", "''", $value);
}

function db_insert_id(?DbConnection $conn): int
{
    if (!$conn) {
        return 0;
    }

    if ($conn->driver === 'mysql') {
        return (int) mysqli_insert_id($conn->handle);
    }

    try {
        return (int) $conn->handle->query('SELECT LASTVAL()')->fetchColumn();
    } catch (Throwable $e) {
        $conn->error = $e->getMessage();
        return 0;
    }
}

function db_error(?DbConnection $conn): string
{
    if (!$conn) {
        return 'Database connection is not available';
    }

    if ($conn->driver === 'mysql') {
        return mysqli_error($conn->handle);
    }

    return $conn->error;
}
