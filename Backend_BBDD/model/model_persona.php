<?php



function conultarPersonas($conn, $idComunicacion){
    try{  
        $stmt = $conn->prepare("SELECT pc.rol, p.nombre, p.apellido1, p.apellido2, p.fechaNacimiento, p.nacionalidad, p.direccion, p.codigoMunicipio, p.nombreMunicipio, p.localidad, p.cp, p.pais, p.telefono, p.correo, p.sexo, p.tipoDocumento, p.documento, p.soporteDocumento , pc.parentesco
                                FROM persona p, persona_comunicacion pc
                                WHERE p.idPersona=pc.idPersona
                                AND pc.idComunicacion =:idComunicacion");
        $stmt->bindParam(':idComunicacion', $idComunicacion);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $datos=$stmt->fetchAll();
        return $datos;
    }catch(PDOException $e)
    {
        echo "Error: " . $e->getMessage();
    } 
}



?>