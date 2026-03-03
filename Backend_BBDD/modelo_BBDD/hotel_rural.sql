-- ==========================================
-- CREACIÓN BASE DE DATOS
-- ==========================================

CREATE DATABASE IF NOT EXISTS hotel_rural
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hotel_rural;

-- ==========================================
-- TABLAS PRINCIPALES
-- ==========================================

CREATE TABLE ARRENDADOR (
    codigo VARCHAR(10) PRIMARY KEY,
    tipo VARCHAR(5) COMMENT 'EH, AV, OT, PD',
    nombre VARCHAR(100) NOT NULL,
    apellido1 VARCHAR(100),
    apellido2 VARCHAR(100),
    tipoDocumento VARCHAR(5),
    documento VARCHAR(15) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE ESTABLECIMIENTO (
    codigo VARCHAR(10) PRIMARY KEY,
    tipo VARCHAR(100),
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(100),
    codigoMunicipio VARCHAR(5),
    localidad VARCHAR(100),
    cp VARCHAR(20),
    pais VARCHAR(3)
) ENGINE=InnoDB;


CREATE TABLE PERSONA (
    idPersona INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido1 VARCHAR(50) NOT NULL,
    apellido2 VARCHAR(50),
    fechaNacimiento DATE,
    nacionalidad VARCHAR(70),
    direccion VARCHAR(100),
    codigoMunicipio VARCHAR(5),
    nombreMunicipio VARCHAR(100),
    localidad VARCHAR(100),
    cp VARCHAR(20),
    pais VARCHAR(3),
    telefono VARCHAR(20),
    correo VARCHAR(250),
    sexo VARCHAR(1),
    tipoDocumento VARCHAR(20),
    documento VARCHAR(15) NOT NULL UNIQUE,
    soporteDocumento VARCHAR(9)
) ENGINE=InnoDB;

CREATE TABLE SOLICITUD (
    idSolicitud INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    estado ENUM('PENDIENTE','PAGADO','FINALIZADO','CANCELADO') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_persona
        FOREIGN KEY (idPersona)
        REFERENCES PERSONA(idPersona)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE CONTRATO (
    referencia VARCHAR(50) PRIMARY KEY,
    idSolicitud INT UNIQUE,
    fechaContrato DATE,
    fechaEntrada DATE,
    fechaSalida DATE,
    numPersonas INT,
    tipoPago VARCHAR(50),
    precioTotal DECIMAL(10,2),
    CONSTRAINT fk_contrato_solicitud
        FOREIGN KEY (idSolicitud)
        REFERENCES SOLICITUD(idSolicitud)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ADMINISTRADOR (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(100) UNIQUE NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- ==========================================
-- INTEGRACIÓN MINISTERIO
-- ==========================================

CREATE TABLE COMUNICACIONES_SES (
    id_comuSES INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador interno de la comunicación SES',
    codigo_lote VARCHAR(100) NULL COMMENT 'El Ministerio lo asigna al procesar el lote. Inicialmente queda null.',
    codigo_comunicacion VARCHAR(500) NULL COMMENT 'Identificador individual de comunicación en SES',
    tipo_comunicacion VARCHAR(10) NULL COMMENT 'PV=Parte Viajeros o RH=Reserva Hospedaje',
    tipo_operacion VARCHAR(1) NULL COMMENT 'A=Alta, C=Consulta, B=Anulacion',
    estado_ses VARCHAR(50) NULL COMMENT 'Estado agregado actual en SES',
    codigo_estado INT NULL COMMENT 'Inicialmente null o 0, luego se actualiza con la respuesta del Ministerio',
    descripcion_estado VARCHAR(255) NULL COMMENT 'Descripción del estado devuelto por SES',
    anulada BOOLEAN DEFAULT FALSE COMMENT 'Indica si la comunicación está anulada',
    fecha_peticion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de petición registrada por SES',
    fecha_procesamiento TIMESTAMP NULL COMMENT 'Fecha de procesamiento registrada por SES',
    codigo_arrendador VARCHAR(50) NULL COMMENT 'Código del arrendador/establecimiento en SES',
    aplicacion VARCHAR(50) NULL COMMENT 'Nombre del aplicativo cliente que lanza la petición',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización'

) ENGINE=InnoDB;

CREATE TABLE COMUNICACION_RESERVA (
    idComunicacion INT AUTO_INCREMENT PRIMARY KEY,
    idSolicitud INT NOT NULL,
    id_comuSES INT,
    codigoMinisterio VARCHAR(36),
    estadoProcesamiento VARCHAR(20) COMMENT 'PENDIENTE, ACEPTADA, RECHAZADA',
    ordenEnvio INT,
    CONSTRAINT fk_comunicacion_solicitud
        FOREIGN KEY (idSolicitud)
        REFERENCES SOLICITUD(idSolicitud)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_comunicacion_COMUNICACIONES_SES
        FOREIGN KEY (id_comuSES)
        REFERENCES COMUNICACIONES_SES(id_comuSES)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE PERSONA_COMUNICACION (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idComunicacion INT NOT NULL,
    idPersona INT NOT NULL,
    rol ENUM('TI','VI') DEFAULT 'VI' COMMENT 'Titular o Viajero',
    parentesco VARCHAR(5) NULL,
    CONSTRAINT fk_pc_comunicacion FOREIGN KEY (idComunicacion)
        REFERENCES COMUNICACION_RESERVA(idComunicacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_pc_persona FOREIGN KEY (idPersona)
        REFERENCES PERSONA(idPersona)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)ENGINE=InnoDB;

CREATE TABLE OPERACIONES_SES (
    id_operaciones BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador interno de la operación',
    idSolicitud INT NOT NULL COMMENT 'Referencia a la solicitud de reserva',
    idComunicacion INT NULL COMMENT 'Referencia a la comunicación asociada',
    operacion VARCHAR(30) NOT NULL COMMENT 'Tipo de operación (ALTA, CONSULTA, ANULAR, etc.)',
    http_status INT NULL COMMENT 'Código de estado HTTP de la respuesta',
    ses_codigo INT NULL COMMENT 'Código funcional devuelto por SES',
    ses_descripcion VARCHAR(255) NULL COMMENT 'Descripción funcional devuelta por SES',
    resultado_tecnico VARCHAR(20) NOT NULL COMMENT 'Resultado técnico (OK, ERROR_HTTP, TIMEOUT)',
    resultado_funcional VARCHAR(20) NOT NULL COMMENT 'Resultado lógico (OK, ERROR_FUNCIONAL)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de ejecución de la operación',
    FOREIGN KEY (idSolicitud) REFERENCES SOLICITUD(idSolicitud),
    FOREIGN KEY (idComunicacion) REFERENCES COMUNICACION_RESERVA(idComunicacion)

) ENGINE=InnoDB;