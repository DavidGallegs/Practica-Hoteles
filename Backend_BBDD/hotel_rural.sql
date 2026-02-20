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
    documento VARCHAR(15)
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
    rol VARCHAR(10) COMMENT 'CLIENTE, VIAJERO, ETC',
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
    documento VARCHAR(15),
    soporteDocumento VARCHAR(9),
    codigoParentesco VARCHAR(5),
   
) ENGINE=InnoDB;

CREATE TABLE SOLICITUD (
    idSolicitud INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    estado VARCHAR(20) COMMENT 'PENDIENTE, PAGADO, FINALIZADO, CANCELADO',
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

CREATE TABLE LOTE (
    idLote INT AUTO_INCREMENT PRIMARY KEY,
    codigoLoteMinisterio VARCHAR(100),
    tipoOperacion VARCHAR(1) COMMENT 'A=Alta, C=Consulta, B=Anulacion',
    tipoComunicacion VARCHAR(2) COMMENT 'PV=Partes Viajeros, RH=Reservas Hospedaje',
    codigoEstado INT,
    descEstado VARCHAR(200),
    estado VARCHAR(20) COMMENT 'PENDIENTE, PROCESADO, ERROR',
    fechaPeticion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fechaProcesamiento TIMESTAMP NULL,
    identificadorUsuario VARCHAR(50),
    nombreUsuario VARCHAR(150),
    aplicacion VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE COMUNICACION_RESERVA (
    idComunicacion INT AUTO_INCREMENT PRIMARY KEY,
    idSolicitud INT NOT NULL,
    idLote INT,
    codigoMinisterio VARCHAR(36),
    estadoProcesamiento VARCHAR(20) COMMENT 'PENDIENTE, ACEPTADA, RECHAZADA',
    ordenEnvio INT,
    CONSTRAINT fk_comunicacion_solicitud
        FOREIGN KEY (idSolicitud)
        REFERENCES SOLICITUD(idSolicitud)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_comunicacion_lote
        FOREIGN KEY (idLote)
        REFERENCES LOTE(idLote)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;


