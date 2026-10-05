SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS tres_bigotes
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tres_bigotes;

CREATE TABLE establecimiento (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  direccion VARCHAR(180) NULL,
  telefono VARCHAR(20) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_rol_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  rol_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuario_correo (correo),
  KEY idx_usuario_establecimiento (establecimiento_id),
  CONSTRAINT fk_usuario_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sesion (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_en DATETIME NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sesion_token (token_hash),
  KEY idx_sesion_usuario (usuario_id),
  CONSTRAINT fk_sesion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE colaborador (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_ingreso DATE NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_colaborador_usuario (usuario_id),
  KEY idx_colaborador_establecimiento (establecimiento_id),
  CONSTRAINT fk_colaborador_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_colaborador_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cliente (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NULL,
  correo VARCHAR(160) NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cliente_telefono (establecimiento_id, telefono),
  CONSTRAINT fk_cliente_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ficha_cliente (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id INT UNSIGNED NOT NULL,
  preferencias TEXT NULL,
  cortes_habituales TEXT NULL,
  formulas TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ficha_cliente (cliente_id),
  CONSTRAINT fk_ficha_cliente FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicio (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  duracion_minutos INT UNSIGNED NOT NULL,
  precio DECIMAL(12,2) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_servicio_establecimiento (establecimiento_id),
  CONSTRAINT fk_servicio_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT chk_servicio_duracion CHECK (duracion_minutos > 0),
  CONSTRAINT chk_servicio_precio CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cita (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  colaborador_id INT UNSIGNED NOT NULL,
  inicio DATETIME NOT NULL,
  fin DATETIME NOT NULL,
  estado ENUM('pendiente', 'en_proceso', 'completada', 'cancelada') NOT NULL,
  notas TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_cita_establecimiento_inicio (establecimiento_id, inicio),
  KEY idx_cita_establecimiento_estado (establecimiento_id, estado),
  KEY idx_cita_cliente (cliente_id),
  KEY idx_cita_colaborador (colaborador_id),
  CONSTRAINT fk_cita_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cita_cliente FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cita_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT,
  CONSTRAINT chk_cita_horario CHECK (fin > inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cita_servicio (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cita_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  precio_aplicado DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cita_servicio (cita_id, servicio_id),
  KEY idx_cita_servicio_servicio (servicio_id),
  CONSTRAINT fk_cita_servicio_cita FOREIGN KEY (cita_id) REFERENCES cita (id) ON DELETE RESTRICT,
  CONSTRAINT fk_cita_servicio_servicio FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE RESTRICT,
  CONSTRAINT chk_cita_servicio_precio CHECK (precio_aplicado >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nota_visita (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id INT UNSIGNED NOT NULL,
  cita_id INT UNSIGNED NULL,
  colaborador_id INT UNSIGNED NOT NULL,
  observacion TEXT NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_nota_cliente (cliente_id),
  KEY idx_nota_cita (cita_id),
  KEY idx_nota_colaborador (colaborador_id),
  CONSTRAINT fk_nota_cliente FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
  CONSTRAINT fk_nota_cita FOREIGN KEY (cita_id) REFERENCES cita (id) ON DELETE RESTRICT,
  CONSTRAINT fk_nota_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aviso_cita (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cita_id INT UNSIGNED NOT NULL,
  destinatario ENUM('cliente', 'colaborador') NOT NULL,
  programado_para DATETIME NOT NULL,
  estado ENUM('pendiente', 'enviado', 'fallido') NOT NULL,
  PRIMARY KEY (id),
  KEY idx_aviso_cita (cita_id),
  CONSTRAINT fk_aviso_cita FOREIGN KEY (cita_id) REFERENCES cita (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regla_comision (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NULL,
  porcentaje DECIMAL(5,2) NOT NULL,
  umbral_cantidad INT UNSIGNED NULL,
  periodo ENUM('dia', 'semana') NULL,
  PRIMARY KEY (id),
  KEY idx_regla_establecimiento (establecimiento_id),
  KEY idx_regla_servicio (servicio_id),
  CONSTRAINT fk_regla_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_regla_servicio FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE RESTRICT,
  CONSTRAINT chk_regla_porcentaje CHECK (porcentaje >= 0 AND porcentaje <= 100),
  CONSTRAINT chk_regla_umbral CHECK (
    (umbral_cantidad IS NULL AND periodo IS NULL)
    OR (umbral_cantidad > 0 AND periodo IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE liquidacion (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  colaborador_id INT UNSIGNED NOT NULL,
  desde DATETIME NOT NULL,
  hasta DATETIME NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_liquidacion_colaborador (establecimiento_id, colaborador_id, desde),
  CONSTRAINT fk_liquidacion_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_liquidacion_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT,
  CONSTRAINT chk_liquidacion_rango CHECK (hasta >= desde),
  CONSTRAINT chk_liquidacion_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE liquidacion_linea (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  liquidacion_id INT UNSIGNED NOT NULL,
  cita_servicio_id INT UNSIGNED NOT NULL,
  base DECIMAL(12,2) NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_liquidacion_linea_cita (cita_servicio_id),
  CONSTRAINT fk_liquidacion_linea_liquidacion FOREIGN KEY (liquidacion_id) REFERENCES liquidacion (id) ON DELETE RESTRICT,
  CONSTRAINT fk_liquidacion_linea_cita FOREIGN KEY (cita_servicio_id) REFERENCES cita_servicio (id) ON DELETE RESTRICT,
  CONSTRAINT chk_liquidacion_linea CHECK (base >= 0 AND porcentaje >= 0 AND monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  tipo ENUM('insumo', 'venta') NOT NULL,
  precio_venta DECIMAL(12,2) NULL,
  stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_producto_establecimiento (establecimiento_id),
  CONSTRAINT fk_producto_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT chk_producto_stock CHECK (stock >= 0 AND stock_minimo >= 0),
  CONSTRAINT chk_producto_precio CHECK (
    (tipo = 'insumo' AND precio_venta IS NULL)
    OR (tipo = 'venta' AND precio_venta IS NOT NULL AND precio_venta >= 0)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE turno_caja (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  saldo_inicial DECIMAL(12,2) NOT NULL,
  abierto_en DATETIME NOT NULL,
  cerrado_en DATETIME NULL,
  estado ENUM('abierto', 'cerrado') NOT NULL,
  abierto_clave INT UNSIGNED GENERATED ALWAYS AS (
    CASE WHEN estado = 'abierto' THEN establecimiento_id ELSE NULL END
  ) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uk_turno_abierto (abierto_clave),
  KEY idx_turno_establecimiento_abierto (establecimiento_id, abierto_en),
  KEY idx_turno_usuario (usuario_id),
  CONSTRAINT fk_turno_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_turno_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE RESTRICT,
  CONSTRAINT chk_turno_saldo CHECK (saldo_inicial >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venta (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establecimiento_id INT UNSIGNED NOT NULL,
  turno_caja_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NULL,
  cita_id INT UNSIGNED NULL,
  total DECIMAL(12,2) NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_venta_turno (turno_caja_id),
  KEY idx_venta_cliente (cliente_id),
  KEY idx_venta_cita (cita_id),
  CONSTRAINT fk_venta_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_turno FOREIGN KEY (turno_caja_id) REFERENCES turno_caja (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_cliente FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_cita FOREIGN KEY (cita_id) REFERENCES cita (id) ON DELETE RESTRICT,
  CONSTRAINT chk_venta_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venta_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  venta_id INT UNSIGNED NOT NULL,
  tipo ENUM('servicio', 'producto') NOT NULL,
  servicio_id INT UNSIGNED NULL,
  producto_id INT UNSIGNED NULL,
  colaborador_id INT UNSIGNED NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_venta_item_venta (venta_id),
  KEY idx_venta_item_servicio (servicio_id),
  KEY idx_venta_item_producto (producto_id),
  KEY idx_venta_item_colaborador (colaborador_id),
  CONSTRAINT fk_venta_item_venta FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_item_servicio FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_item_producto FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT,
  CONSTRAINT fk_venta_item_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaborador (id) ON DELETE RESTRICT,
  CONSTRAINT chk_venta_item_referencia CHECK (
    (tipo = 'servicio' AND servicio_id IS NOT NULL AND producto_id IS NULL)
    OR (tipo = 'producto' AND producto_id IS NOT NULL AND servicio_id IS NULL)
  ),
  CONSTRAINT chk_venta_item_montos CHECK (
    cantidad > 0
    AND precio_unitario >= 0
    AND subtotal = ROUND(cantidad * precio_unitario, 2)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pago (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  venta_id INT UNSIGNED NOT NULL,
  medio ENUM('efectivo', 'nequi', 'daviplata', 'bancolombia', 'tarjeta') NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pago_venta (venta_id),
  CONSTRAINT fk_pago_venta FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE RESTRICT,
  CONSTRAINT chk_pago_monto CHECK (monto > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arqueo_linea (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turno_caja_id INT UNSIGNED NOT NULL,
  medio ENUM('efectivo', 'nequi', 'daviplata', 'bancolombia', 'tarjeta') NOT NULL,
  monto_sistema DECIMAL(12,2) NOT NULL,
  monto_reportado DECIMAL(12,2) NOT NULL,
  diferencia DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_arqueo_medio (turno_caja_id, medio),
  CONSTRAINT fk_arqueo_turno FOREIGN KEY (turno_caja_id) REFERENCES turno_caja (id) ON DELETE RESTRICT,
  CONSTRAINT chk_arqueo_diferencia CHECK (diferencia = monto_reportado - monto_sistema)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimiento_inventario (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  producto_id INT UNSIGNED NOT NULL,
  tipo ENUM('entrada', 'salida', 'venta', 'ajuste') NOT NULL,
  cantidad INT NOT NULL,
  venta_item_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NOT NULL,
  motivo VARCHAR(180) NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_movimiento_producto (producto_id),
  KEY idx_movimiento_venta_item (venta_item_id),
  KEY idx_movimiento_usuario (usuario_id),
  CONSTRAINT fk_movimiento_producto FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_venta_item FOREIGN KEY (venta_item_id) REFERENCES venta_item (id) ON DELETE RESTRICT,
  CONSTRAINT fk_movimiento_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE RESTRICT,
  CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
