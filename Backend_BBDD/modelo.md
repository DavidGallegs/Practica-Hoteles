# MODELO BASE DE DATOS

~~~sql
// ==========================================
// 1. USUARIOS Y CONFIGURACIÓN (Tablas fijas)
// ==========================================

Table ADMINISTRADOR {
  idUser varchar [pk] 
  userName varchar
  password varchar
  email varchar
}

Table CONFIGURACION {
  // Datos fijos de tu establecimiento
  codigoArrendador varchar
  codigoEstablecimiento varchar
  nombreEstablecimiento varchar
}

// ==========================================
// 2. EL NÚCLEO (La Solicitud y sus Personas)
// ==========================================

Table SOLICITUD {
  id_solicitud varchar [pk] // Tu ID interno (Ej: SOL-104)
  estado varchar [note: "PENDIENTE, PAGADO, FINALIZADO, CANCELADO"]
  created_at timestamp
}

Table PERSONA {
  // Aquí están los datos REALES para el SES y el Parte.
  // No hace falta copiarlos a otra tabla.
  id_persona int [pk, increment] 
  id_solicitud varchar [ref: > SOLICITUD.id_solicitud] 
  
  rol varchar // TITULAR o VIAJERO
  nombre varchar
  apellido1 varchar
  apellido2 varchar
  tipoDocumento varchar
  numeroDocumento varchar
  telefono varchar
  correo varchar
  parentesco varchar
}

Table CONTRATO {
  id_solicitud varchar [pk, ref: - SOLICITUD.id_solicitud] // 1 a 1
  referencia varchar
  fechaContrato date
  fechaEntrada date
  fechaSalida date
  numPersonas integer
  tipo_pago varchar
  precioTotal decimal 
}

// ==========================================
// 3. COMUNICACIÓN CON POLICÍA (SES / HOSPEDERÍAS)
// ==========================================

// PASO 1: Cuando pagan (La reserva en sí)
Table COMUNICACION_RESERVA {
  id_comunicacion int [pk, increment]
  id_solicitud varchar [ref: - SOLICITUD.id_solicitud] // Vinculado a la solicitud
  
  id_SES_reserva varchar [note: "El ID que devuelva el sistema al comunicar la reserva"]
  fecha_envio datetime
  estado_envio varchar [note: "ENVIADO, ERROR"]
}

// PASO 2: Cuando llegan (El Check-in / Parte de Viajeros)
Table PARTE_VIAJEROS {
  id_parte int [pk, increment]
  id_solicitud varchar [ref: - SOLICITUD.id_solicitud] // Vinculado a la misma solicitud
  
  numero_parte_oficial varchar [note: "El número consecutivo del parte de viajeros"]
  fecha_entrada_real datetime
  firma_cliente varchar [note: "Ruta de la imagen de la firma"]
  
  // Datos de control del envío a la policía
  fecha_envio_policia datetime
  estado_envio varchar [note: "PENDIENTE, ENVIADO_OK, ERROR"]
  codigo_respuesta_policia varchar
} 
~~~
