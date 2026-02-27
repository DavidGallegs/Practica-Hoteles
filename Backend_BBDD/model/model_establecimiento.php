<?php



function conultarcodigoEstablecimiento($conn){
    try{  
        $stmt = $conn->prepare("SELECT codigo FROM establecimiento");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $codigo=$stmt->fetch();
        return $codigo;
    }catch(PDOException $e)
    {
        echo "Error: " . $e->getMessage();
    } 
}



?>