# Flujo de Reserva y Comunicación con el Ministerio

Este documento describe el proceso completo desde la creación de una solicitud de reserva hasta la comunicación con el Ministerio, utilizando las tablas del sistema.

---

## 1.- Creación de la solicitud

Supongamos que un cliente quiere reservar una habitación:

- **Cliente:** Juan Pérez  
- **Acompañante:** Ana López  
- **Establecimiento:** Hotel Rural El Quintanal  

### Tablas involucradas

**PERSONA** -> Se registra al cliente y a su acompañante:

| idPersona | rol        | nombre | apellido1 | ... | codigoParentesco |
|-----------|------------|--------|-----------|-----|------------------|
| 101       | TI         | Juan   | Pérez     | ... | NULL             |
| 102       | VI         | Ana    | López     | ... | CY(Conyuge)      |

> Parentesco: TI = titular, VI = acompañante

**SOLICITUD** -> Creas la solicitud de reserva:

| idSolicitud | idPersona | estado     | createdAt          |
|------------|------------|------------|--------------------|
| 201        | 101        | PENDIENTE  | 2026-02-17 10:00   |

> `idPersona` apunta al cliente titular de la reserva.  
> Estado inicial = `PENDIENTE` hasta que el administrador lo acepte.

---

## 2.- Aceptación por el administrador

Cuando el administrador acepta la reserva:

### Actualización de SOLICITUD

| idSolicitud | estado  |
|------------|---------|
| 201        | PAGADO  |

### Creación de CONTRATO asociado

| referencia | idSolicitud | fechaContrato | fechaEntrada | fechaSalida | numPersonas | tipoPago | precioTotal |
|------------|-------------|---------------|--------------|-------------|-------------|----------|-------------|
| 301        | 201         | 2026-02-17    | 2026-03-01   | 2026-03-05  | 2           | TARJETA  | 450.00      |

> `numPersonas = 2` (cliente + acompañante)  
> `tipoPago` y `precioTotal` se registran según el contrato.

---

## 3.-  Preparación del lote para el Ministerio

Se crea un **LOTE** para enviar la comunicación:

| idLote |codigoLoteMinisterio | tipoOperacion |tipoComunicacion| codigoEstado | estado     |descEstado| identificadorUsuario | nombreUsuario          | aplicacion  | fechaPeticion |fechaProcesamiento|
|--------|---------------------|---------------|----------------|--------------|------------|----------|----------------------|------------------------|-------------|---------------|------------------|
| 401    |NULL                 |A              |PV              |NULL          | PENDIENTE  |NULL      | admin01              | (NombreAdministradora) | Web         | 2026-02-17    |NULL              |

- `idLote` -> Identificador que se crea automáticamente al insertar el registro; es un **PK local**.
- `codigoLoteMinisterio` -> Código que asigna el Ministerio al procesar el lote; inicialmente queda **null** hasta que se reciba la respuesta.
- `tipoOperacion` -> Tipo de operación del lote:
  - `A` -> Alta de comunicaciones
  - `C` -> Consulta
  - `B` -> Anulación
- `tipoComunicacion` -> Tipo de comunicaciones enviadas
  - `PV` -> partes de viajeros
  - `RH` -> reservas de hospedaje
- `codigoEstado` -> Estado numérico devuelto por el Ministerio; inicialmente **null o 0**, luego actualizado según la respuesta (valores 1->6).
  - Estado del lote. Valores:
    `1` -> Lote tramitado sin errores
    `2` -> Lote con errores en la cabecera o formato de la solicitud
    `3` -> Error inesperado
    `4` -> En proceso
    `5` -> Pendiente
    `6` -> Lote tramitado con errores en algunas comunicaciones 
- `descEstado` -> Descripción textual del estado; inicialmente **null**, luego devuelta por el Ministerio.
- `estado` -> Estado definido internamente:
  - `PENDIENTE` -> Lote creado pero no enviado aún
  - `PROCESADO` -> Respuesta recibida correctamente
  - `ERROR` -> Hubo fallo en el envío o procesamiento
- `fechaPeticion` -> Fecha y hora en que se creó la solicitud del lote.
- `fechaProcesamiento` -> Fecha y hora en que se recibe la respuesta del Ministerio; se actualiza automáticamente.
- `identificadorUsuario` -> ID interno del administrador que genera y envía el lote.
- `nombreUsuario` -> Nombre completo del administrador que procesa la petición.
- `aplicacion` -> Nombre de la aplicación cliente que lanza la petición al Ministerio.

---

## 4.-  Comunicación de la reserva

Se crea un registro en **COMUNICACION_RESERVA** por la solicitud:

| idComunicacion | idSolicitud | idLote | codigoMinisterio | estadoProcesamiento | orden |
|----------------|-------------|--------|------------------|---------------------|-------|
| 501            | 201         | 401    | NULL             | PENDIENTE           | 1     |

- `codigoMinisterio` se llenará cuando el Ministerio devuelva el código.  
- `estadoProcesamiento = PENDIENTE` -> antes de recibir la respuesta.  
- `orden` indica la posición de la comunicación dentro del lote.

---

## 5.-  Relación de personas con la comunicación

Se registran todas las personas incluidas en la reserva en **PERSONA_COMUNICACION**:

| id | idComunicacion | idPersona |
|----|----------------|-----------|
| 1  | 501            | 101       |
| 2  | 501            | 102       |

> Permite enviar ambas personas en la comunicación al Ministerio sin duplicar datos de las personas.

---

## 6.-  Envío y respuesta del Ministerio

Se genera el XML de la comunicación

