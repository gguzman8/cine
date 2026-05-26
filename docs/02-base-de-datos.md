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
│ rol          │     │ precio         │     │ created_at       │
│ created_at   │     │ created_at     │     └──────┬───────────┘
└──────┬───────┘     └────────────────┘            │
       │                                            │
       │ 1:N                                 1:N
       │                                            │
       │    ┌──────────────────┐                    │
       │    │     compras      │                    │
       │    ├──────────────────┤                    │
       └────┤ FK usuario_id    │                    │
            │ FK funcion_id ───┘                    │
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
            │ FK asiento_id ───┼─────┤ fila            │
            │ precio           │     │ numero          │
            └──────────────────┘     │ disponible (bool)│
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
| precio | DECIMAL(10,2) | Precio por boleto |
| created_at | TIMESTAMP | Fecha de alta |

### `funciones`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| pelicula_id | INT (FK → peliculas.id) | Película proyectada |
| horario | DATETIME | Fecha y hora de la función |
| sala | VARCHAR(50) | Sala donde se proyecta |
| created_at | TIMESTAMP | Fecha de alta |

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
| usuario_id | INT (FK → usuarios.id) | Comprador |
| funcion_id | INT (FK → funciones.id) | Función seleccionada |
| total | DECIMAL(10,2) | Monto total (precio × cantidad) |
| created_at | TIMESTAMP | Fecha de la compra |

### `detalle_compra`
| Columna | Tipo | Descripción |
|---|---|---|
| id | INT (PK) | Identificador |
| compra_id | INT (FK → compras.id) | Compra padre |
| asiento_id | INT (FK → asientos.id) | Asiento adquirido |
| precio | DECIMAL(10,2) | Precio pagado por este asiento |

## Procedimiento Almacenado

### `generar_asientos(p_funcion_id INT)`
Genera 40 asientos (5 filas × 8 columnas) para una función.

```sql
CALL generar_asientos(1);  -- Genera asientos A1-E8 para la función ID=1
```

## Datos de Prueba

El schema incluye 3 películas y 6 funciones. Al ejecutar `setup.sh`, se genera automáticamente la matriz de asientos para cada función.
