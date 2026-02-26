<?php



function conultarPersonas($conn){
    try{  
        $stmt = $conn->prepare("SELECT referencia, fechaContrato, fechaEntrada, fechaSalida, numPersonas, tipoPago 
                                FROM contrato");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $datos=$stmt->fetch();
        return $datos;
    }catch(PDOException $e)
    {
        echo "Error: " . $e->getMessage();
    } 
}



?>