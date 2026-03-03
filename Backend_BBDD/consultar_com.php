<?php

declare(strict_types=1);

/* ========================= */
/* Utilidades */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        if (!getenv($key)) {
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

function requireEnv(?string $value, string $name): string
{
    if (!$value) {
        fwrite(STDERR, "Error: falta '{$name}'. Defínela en .env o como variable de entorno.\n");
        exit(1);
    }
    return $value;
}

/* ========================= */
/* Inicio */

$baseDir = __DIR__;
loadEnv($baseDir . '/.env');

/* ========================= */
/* Parseo de argumentos */

if ($argc < 2) {
    fwrite(STDERR, "Uso: php consultar_comunicacion.php <codigo> [--respuesta=nombre.xml]\n");
    exit(1);
}

$codigo = trim($argv[1]);
if ($codigo === '') {
    fwrite(STDERR, "Error: el código está vacío.\n");
    exit(1);
}

/* Flags opcionales */
$respuestaNombre = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--respuesta=')) {
        $respuestaNombre = trim(substr($arg, 12));
    }
}

/* ========================= */
/* Variables entorno */

$endpoint = requireEnv(getenv('SES_ENDPOINT'), 'SES_ENDPOINT');
$authBasic = requireEnv(getenv('SES_AUTH_BASIC'), 'SES_AUTH_BASIC');

$connectTimeout = (int)(getenv('SES_CONNECT_TIMEOUT') ?: 15);
$maxTime = (int)(getenv('SES_MAX_TIME') ?: 60);

/* ========================= */
/* SOAP */

$soapContent = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                 xmlns:com="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
   <soapenv:Header/>
   <soapenv:Body>
      <com:consultaComunicacionRequest>
         <codigos>
            <codigo>{$codigo}</codigo>
         </codigos>
      </com:consultaComunicacionRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;

/* ========================= */
/* Output */

$outputDir = $baseDir . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

if ($respuestaNombre !== '') {
    $respName = $respuestaNombre;
} else {
    $respName = 'resp_consulta_com' . date('Ymd_His') . '.xml';
}

$respuestaPath = $outputDir . '/' . $respName;

/* ========================= */
/* Curl por STDIN (Schannel) */

try {

    $comando = [
        "curl.exe",
        "-X", "POST",
        $endpoint,
        "-H", "Content-Type: text/xml; charset=utf-8",
        "-H", "Authorization: Basic {$authBasic}",
        "--data-binary", "@-",
        "--silent",
        "--show-error",
        "--fail",
        "--connect-timeout", (string)$connectTimeout,
        "--max-time", (string)$maxTime
    ];

    $descriptores = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    $proceso = proc_open($comando, $descriptores, $pipes);

    if (!is_resource($proceso)) {
        throw new RuntimeException("No se pudo iniciar curl.exe");
    }

    fwrite($pipes[0], $soapContent);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($proceso);

    if ($exitCode !== 0) {
        throw new RuntimeException($stderr ?: $stdout, $exitCode);
    }

    file_put_contents($respuestaPath, $stdout);

    echo "OK. Respuesta guardada en: {$respuestaPath}\n";
    exit(0);

} catch (Throwable $e) {

    $evidencia = $e->getMessage();
    file_put_contents($respuestaPath, $evidencia);

    fwrite(STDERR, "Error en la consulta (curl).\n");
    fwrite(STDERR, "Exit code: " . $e->getCode() . "\n");
    fwrite(STDERR, $evidencia . "\n");
    fwrite(STDERR, "Evidencia guardada en: {$respuestaPath}\n");

    exit(1);
}
?>