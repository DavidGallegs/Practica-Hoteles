<?php


// 1️.- Ficheros
$xmlClientesFile = "input/datosClientes.xml";      // tu XML con datos
$soapTemplateFile = "input/comunicacion.xml";     // plantilla SOAP
$zipFile = "datosClientes.zip";             // ZIP que se generará
$soapOutputFile = "reserva_soap.xml";       // SOAP final

// 2️.- Comprimir el XML en ZIP
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    $zip->addFile($xmlClientesFile, basename($xmlClientesFile));
    $zip->close();
    echo "ZIP creado correctamente.\n";
} else {
    die("Error al crear ZIP.\n");
}

// 3️.- Codificar ZIP en Base64
$zipData = file_get_contents($zipFile);
$base64Zip = base64_encode($zipData);

// 4️.- Leer plantilla SOAP
$soapTemplate = file_get_contents($soapTemplateFile);

// 5️.- Insertar Base64 en <solicitud>
$soapFinal = preg_replace(
    '/<solicitud>(.*?)<\/solicitud>/s',
    "<solicitud>$base64Zip</solicitud>",
    $soapTemplate
);

// 6️.- Guardar SOAP final
file_put_contents($soapOutputFile, $soapFinal);

echo "SOAP generado correctamente en '$soapOutputFile'.\n";

?>