# Base de Datos

## Esquema Entidad-Relación

```
┌──────────────┐     ┌────────────────┐     ┌──────────────────┐
│   usuarios   │     │   peliculas    │     │    funciones     │
├──────────────┤     ├────────────────┤     ├──────────────────┤
│ PK id        │     │ PK id          │     │ PK id            │
│ nombre       │     │ titulo         │     │ FK pelicula_id   │
│ email (UQ)   │     │ sinopsis       │     │ horario          │
│ password_hash│     │ poster         │     │ sala             │
│ rol          │     │ precio         │     │ es_matinee       │
│ created_at   │     │ activa         │     │ expirada         │
└──────┬───────┘     │ created_at     │     │ created_at       │
       │             └────────────────┘     └──────┬───────────┘
       │                                            │
       │ 1:N                                 1:N
       │                                            │
       │    ┌──────────────────┐                    │
       │    │     compras      │                    │
       │    ├──────────────────┤                    │
       └────┤ FK usuario_id    │                    │
            │ FK funcion_id ───┘                    │
            │ cliente_nombre                        │
            │ vendedor_nombre                       │
            │ FK cupon_id (nullable)                │
            │ checkin_at (nullable)                 │
            │ total                                 │
            │ created_at                            │
            └────────┬─────────┘                    │
                     │                              │
               1:N                             1:N
                     │                              │
            ┌────────▼─────────┐     ┌──────────────▼──┐
            │ detalle_compra   │     │    asientos      │
            ├──────────────────┤     ├─────────────────┤
            │ PK id            │     │ PK id           │
            │ FK compra_id     │     │ FK funcion_id   │
            │ FK asiento_id ───┼─────┤ fila (A-E)      │
            │ precio           │     │ numero (1-8)    │
            └──────────────────┘     │ disponible (bool)│
                                     └──────────────────┘

┌──────────────────┐     ┌─────────────────────┐
│    cupones       │     │    api_tokens        │
├──────────────────┤     ├─────────────────────┤
│ PK id            │     │ PK id               │
│ codigo (UQ)      │     │ FK usuario_id       │
│ descripcion      │     │ token (UQ)          │
│ descuento_%      │     │ created_at          │
│ usos_maximos     │     └─────────────────────┘
│ usos_actuales    │
│ activo           │
│ created_at       │
└──────────────────┘
```

## Diccionario de Tablas

### `usuarios`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK, AUTO_INCREMENT) | Identificador único |
| nombre | VARCHAR(100) | Nombre completo |
| email | VARCHAR(255) (UNIQUE) | Correo electrónico |
| password_hash | VARCHAR(255) | Hash bcrypt de la contraseña |
| rol | ENUM('cliente','admin','vendedor') | Rol del usuario |
| created_at | TIMESTAMP | Fecha de registro |

### `peliculas`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| titulo | VARCHAR(200) | Título de la película |
| sinopsis | TEXT | Descripción breve |
| poster | VARCHAR(255) | Nombre del archivo de imagen |
| precio | DECIMAL(10,2) | Precio base por boleto |
| activa | BOOLEAN | TRUE = visible en cartelera, FALSE = oculta |
| created_at | TIMESTAMP | Fecha de alta |

### `funciones`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| pelicula_id | INT (FK → peliculas.id) | Película proyectada |
| horario | DATETIME | Fecha y hora de la función |
| sala | VARCHAR(50) | Sala donde se proyecta |
| es_matinee | BOOLEAN | TRUE = función matiné (descuento si se compra antes de 12:00) |
| expirada | BOOLEAN | TRUE = función ya pasó (marcada por cron o al cargar página) |
| created_at | TIMESTAMP | Fecha de alta |

**Índice:** `idx_expirada` sobre `expirada`.

### `asientos`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| funcion_id | INT (FK → funciones.id) | Función a la que pertenece |
| fila | CHAR(1) | Letra de fila (A-E) |
| numero | INT | Número de asiento (1-8) |
| disponible | BOOLEAN | TRUE = libre, FALSE = ocupado |

**Restricción:** `UNIQUE(funcion_id, fila, numero)` — no hay asientos duplicados por función.

### `compras`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador de compra |
| usuario_id | INT (FK → usuarios.id) | Usuario que realizó la compra |
| funcion_id | INT (FK → funciones.id) | Función seleccionada |
| cliente_nombre | VARCHAR(255) | Nombre del cliente (lo ingresa staff o se auto-asigna) |
| vendedor_nombre | VARCHAR(255) | "Sistema" si compra web, o nombre del staff que vendió |
| cupon_id | INT (FK nullable → cupones.id) | Cupón aplicado (si hay) |
| checkin_at | DATETIME (nullable) | Fecha/hora de check-in (NULL = pendiente) |
| total | DECIMAL(10,2) | Monto total pagado |
| created_at | TIMESTAMP | Fecha de la compra |

### `detalle_compra`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| compra_id | INT (FK → compras.id) | Compra padre |
| asiento_id | INT (FK → asientos.id) | Asiento adquirido |
| precio | DECIMAL(10,2) | Precio pagado por este asiento |

### `cupones`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| codigo | VARCHAR(20) (UNIQUE) | Código promocional |
| descripcion | VARCHAR(255) | Descripción del cupón |
| descuento_porcentaje | INT | Porcentaje de descuento |
| usos_maximos | INT | Máximo de usos permitidos |
| usos_actuales | INT | Usos realizados hasta ahora |
| activo | BOOLEAN | TRUE = cupón vigente |
| created_at | TIMESTAMP | Fecha de creación |

### `api_tokens`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| usuario_id | INT (FK → usuarios.id) | Usuario propietario del token |
| token | VARCHAR(64) (UNIQUE) | Token Bearer para autenticación API |
| created_at | TIMESTAMP | Fecha de creación |

## Procedimiento Almacenado

### `generar_asientos(p_funcion_id INT)`
Genera 40 asientos (5 filas × 8 columnas) para una función.

```sql
CALL generar_asientos(1);  -- Genera asientos A1-E8 para la función ID=1
```

## Datos de Prueba

El schema incluye 3 películas, 9 funciones y 3 cupones. Al ejecutar `setup.sh`, se genera automáticamente la matriz de asientos para cada función y los usuarios por defecto vía `src/seed.php`.

### Películas
| Título | Precio |
|---|---|
| Dune: Parte Dos | $95.00 |
| Kung Fu Panda 4 | $75.00 |
| Intensamente 2 | $80.00 |

### Cupones
| Código | Descuento | Usos máximos |
|---|---|---|
| CINE10 | 10% | 50 |
| BIENVENIDO | 15% | 30 |
| MATINE20 | 20% | 20 |
