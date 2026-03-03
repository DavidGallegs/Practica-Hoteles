#!/usr/bin/env php
<?php
/**
 * realiza la consulta de un lote pasado como parámetro en la llamada
 * y guarda resultado en fichero.
 */

declare(strict_types=1);

// ==========================
// Configuración (.env)
// ==========================

function load_env(string $env_path): void
{
    if (!file_exists($env_path)) {
        return;
    }

    $lines = file($env_path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        if (!getenv(trim($key))) {
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

function require_config(?string $value, string $name): string
{
    if (!$value) {
        fwrite(STDERR, "Falta configuración obligatoria: $name\n");
        exit(1);
    }
    return $value;
}

function localname(string $tag): string
{
    $pos = strrpos($tag, '}');
    return $pos !== false ? substr($tag, $pos + 1) : $tag;
}

function find_first(DOMNode $root, string $name): ?DOMElement
{
    $xpath = new DOMXPath($root->ownerDocument);
    $nodes = $xpath->query("//*[local-name()='$name']");
    return $nodes->length > 0 ? $nodes->item(0) : null;
}

// ==========================
// MAIN
// ==========================

function main(array $argv): int
{
    if (count($argv) < 2) {
        fwrite(STDERR, "Uso: php consulta_lote.php ID_LOTE\n");
        return 1;
    }

    $base_dir = realpath(__DIR__);
    load_env($base_dir . DIRECTORY_SEPARATOR . '.env');

    $lote_id = trim($argv[1]);

    $endpoint  = require_config(getenv('SES_ENDPOINT'), 'SES_ENDPOINT');
    $auth_basic = require_config(getenv('SES_AUTH_BASIC'), 'SES_AUTH_BASIC');

    $plantilla_solicitud = $base_dir . '/input/solicitud.xml';
    $plantilla_comunicacion = $base_dir . '/input/comunicacion.xml';

    // ==========================
    // 1) Rellenar solicitud.xml en memoria
    // ==========================

    $dom_sol = new DOMDocument();
    $dom_sol->load($plantilla_solicitud);

    $nodo_lote = find_first($dom_sol->documentElement, 'lote');
    if (!$nodo_lote) {
        fwrite(STDERR, "No se encontró etiqueta <lote> en solicitud.xml\n");
        return 1;
    }

    $nodo_lote->nodeValue = $lote_id;

    $solicitud_bytes = $dom_sol->saveXML();

    // ==========================
   // 2) ZIP (equivalente en PHP)
    // ==========================

    // Crear fichero temporal
    $tmpZip = tempnam(sys_get_temp_dir(), 'zip_');

    $zip = new ZipArchive();

    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        fwrite(STDERR, "No se pudo crear ZIP\n");
        return 1;
    }

    $zip->addFromString("solicitud.xml", $solicitud_bytes);
    $zip->close();

    // Leer contenido del ZIP
    $zip_data = file_get_contents($tmpZip);

    // Eliminar temporal
    unlink($tmpZip);

    // Codificar en Base64
    $base64_data = base64_encode($zip_data);
    // ==========================
    // 3) Insertar Base64 en comunicacion.xml
    // ==========================

    $dom_com = new DOMDocument();
    $dom_com->load($plantilla_comunicacion);

    $nodo_solicitud = find_first($dom_com->documentElement, 'solicitud');
    if (!$nodo_solicitud) {
        fwrite(STDERR, "No se encontró etiqueta <solicitud> en comunicacion.xml\n");
        return 1;
    }

    $nodo_solicitud->nodeValue = $base64_data;

    $xml_final_bytes = $dom_com->saveXML();

    // ==========================
    // 4) Envío con curl (stdin)
    // ==========================

    $comando = [
        "curl",
        "-X", "POST",
        $endpoint,
        "-H", "Content-Type: text/xml; charset=utf-8",
        "-H", "Authorization: Basic $auth_basic",
        "--data-binary", "@-",
        "--silent",
        "--show-error",
        "--fail"
    ];

    $output_dir = $base_dir . '/output';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0777, true);
    }

    $respuesta_path = $output_dir . "/resp_consulta_lote_{$lote_id}.xml";

    $descriptor_spec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"], // stderr
    ];

    $process = proc_open($comando, $descriptor_spec, $pipes);

    if (!is_resource($process)) {
        fwrite(STDERR, "No se pudo ejecutar curl\n");
        return 1;
    }

    fwrite($pipes[0], $xml_final_bytes);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exit_code = proc_close($process);

    if ($exit_code !== 0) {
        $error_text = $stdout ?: $stderr;
        file_put_contents($respuesta_path, $error_text);
        echo "Error en la consulta.\n";
        echo $error_text . "\n";
        return 1;
    }

    file_put_contents($respuesta_path, $stdout);
    echo "Consulta OK. Respuesta guardada en: $respuesta_path\n";

    return 0;
}

exit(main($argv));

?>