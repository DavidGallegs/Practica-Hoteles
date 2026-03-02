<?php

declare(strict_types=1);

/**
 * Alta SES:
 * - ZIP en memoria
 * - Base64
 * - Insertar en plantilla
 * - Envío por cURL
 * - Guardar respuesta
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
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
        die("Falta variable: {$name}\n");
    }
    return $value;
}

function getFirstByLocalName(DOMDocument $dom, string $name): ?DOMElement
{
    foreach ($dom->getElementsByTagName('*') as $node) {
        if ($node->localName === $name) {
            return $node;
        }
    }
    return null;
}

function extractByLocalName(string $xml, string $name): ?string
{
    $dom = new DOMDocument();

    if (!@$dom->loadXML($xml)) {
        return null;
    }

    $node = getFirstByLocalName($dom, $name);

    return $node ? trim($node->textContent) : null;
}

/* ========================= */

$baseDir = __DIR__;

loadEnv($baseDir . '/.env');

$endpoint = requireEnv(getenv('SES_ENDPOINT'), 'SES_ENDPOINT');
$authBasic = requireEnv(getenv('SES_AUTH_BASIC'), 'SES_AUTH_BASIC');
$codigoArrendador = requireEnv(getenv('SES_CODIGO_ARRENDADOR'), 'SES_CODIGO_ARRENDADOR');

$inputDir = $baseDir . '/input';
$outputDir = $baseDir . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$datosXmlPath = $inputDir . '/datosReserva.xml';
$plantillaPath = $inputDir . '/plantillaReserva.xml';

if (!file_exists($datosXmlPath)) {
    die("No existe datosReserva.xml\n");
}

if (!file_exists($plantillaPath)) {
    die("No existe plantillaReserva.xml\n");
}

/* ========================= */
/* 1) ZIP en memoria seguro */
$zip = new ZipArchive();

// Crear un archivo ZIP en memoria usando 'php://memory' con un stream temporal
$tmpStream = tempnam(sys_get_temp_dir(), 'ses_');

if ($tmpStream === false) {
    die("No se pudo crear archivo temporal\n");
}

// Abrir el ZIP con OVERWRITE en un archivo que aún no existe
if ($zip->open($tmpStream, ZipArchive::OVERWRITE) !== true) {
    die("No se pudo abrir el ZIP\n");
}

// Agregar XML directamente desde contenido
$datosXmlPath = __DIR__ . '/input/datosReserva.xml';
$xmlContent = file_get_contents($datosXmlPath);
$zip->addFromString(basename($datosXmlPath), $xmlContent);

$zip->close();

// Leer contenido en memoria
$zipData = file_get_contents($tmpStream);
unlink($tmpStream);

// Codificar a Base64
$solicitudB64 = base64_encode($zipData);

echo "ZIP en memoria creado y codificado en Base64 correctamente\n";
/* ========================= */
/* 2) Base64 */

$solicitudB64 = base64_encode($zipData);

/* ========================= */
/* 3) Insertar en plantilla */

$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = false;
$dom->load($plantillaPath);

$codigoNode = getFirstByLocalName($dom, 'codigoArrendador');
$solicitudNode = getFirstByLocalName($dom, 'solicitud');

if (!$codigoNode || !$solicitudNode) {
    die("No se encontraron nodos en la plantilla\n");
}

$codigoNode->nodeValue = $codigoArrendador;
$solicitudNode->nodeValue = $solicitudB64;

$xmlFinal = $dom->saveXML();

/* ========================= */
/* 4) Preparar nombre respuesta */

$timestamp = date('Ymd_His');
$respuestaPath = $outputDir . "/respuesta_Reserva_{$timestamp}.xml";

/* ========================= */
/* 5) Envío con cURL */




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
        "--connect-timeout", "15",
        "--max-time", "60"
    ];

    $descriptores = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    try {

        $proceso = proc_open($comando, $descriptores, $pipes);

        if (!is_resource($proceso)) {
            throw new RuntimeException("No se pudo iniciar curl.exe");
        }

        // Enviar XML por STDIN
        fwrite($pipes[0], $xmlFinal);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proceso);

        if ($exitCode !== 0) {
            throw new RuntimeException($stderr ?: $stdout, $exitCode);
        }

        // Guardar respuesta OK
        file_put_contents($respuestaPath, $stdout);
        echo "OK. Respuesta guardada en: {$respuestaPath}\n";

        /* ========================= */
        /* 6) Guardar código de lote */

        $lote = extractByLocalName($stdout, 'lote');

        if ($lote) {
            $lotePath = $outputDir . '/codigo_lote.txt';
            file_put_contents($lotePath, $lote);
            echo "Código de lote guardado en: {$lotePath}\n";
        } else {
            echo "Aviso: no se encontró <lote> en la respuesta.\n";
        }

        exit(0);

    } catch (Throwable $e) {

        $evidencia = $e->getMessage();

        file_put_contents($respuestaPath, $evidencia);

        echo "Error en el alta (curl).\n";
        echo "Exit code: " . $e->getCode() . "\n";
        echo $evidencia . "\n";
        echo "Evidencia guardada en: {$respuestaPath}\n";

        /* Intento opcional: extraer lote incluso en error */
        $lote = extractByLocalName($evidencia, 'lote');

        if ($lote) {
            $lotePath = $outputDir . '/codigo_lote.txt';
            file_put_contents($lotePath, $lote);
            echo "Código de lote guardado en: {$lotePath}\n";
        }

        exit(1);
    }

?>