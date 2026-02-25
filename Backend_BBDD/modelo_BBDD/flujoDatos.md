
## 1.- Creación de administrador

Supongamos que el arrendatario tiene los siguientes datos:

- **Arrendador:** GUILLERMO ROBERTO NIEBLA PINCAY, 54882182L
    - **Codigo de arrendador:** 0000253722
- **Establecimiento:** HOTEL RURAL QUINO
    - **Codigo de establecimiento:** 0000400736

### Tabla involucrada

**ARRENDADOR** -> Se registra al arrendador:

| codigo     | tipo    | nombre            | apellido1     | apellido2 | tipoDocumento |documento |
|------------|---------|-------------------|---------------|-----------|---------------|----------|
| 0000253722 | EH      | Guillermo Roberto | Niebla        | Pincay    | DNI           |54882182L |


## 2.- Creación de Establecimiento

Supongamos que el establecimiento registrado en el SES tiene los siguientes datos:

- **Establecimiento:** HOTEL RURAL QUINO
    - **Código de establecimiento:** 0000400736    
    - **Tipo:** HR
    - **Dirección:** CALLE FICTICIA 123
    - **Código Municipio:** 28079
    - **Localidad:** MADRID
    - **CP:** 28054
    - **País:** ESP
  
**ESTABLECIMIENTO** -> Se registra al establecimiento:

| codigo     | tipo   | nombre            | direccion          | codigoMunicipio | localidad | cp    | pais |
| ---------- | ------ | ----------------- | ------------------ | --------------- | --------- | ----- | ---- |
| 0000400899 | HR     | HOTEL RURAL QUINO | CALLE FICTICIA, 46 | 28079           | MADRID    | 28054 | ESP  |




