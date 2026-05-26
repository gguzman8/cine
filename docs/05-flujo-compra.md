# Flujo de Compra

## Diagrama de Secuencia

```
Usuario               Navegador/PHP                  MySQL
   │                       │                          │
   │  1. GET /index.php    │                          │
   │──────────────────────►│                          │
   │                       │  2. SELECT peliculas     │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │◄──────────────────────│                          │
   │                       │                          │
   │  3. Click en película │                          │
   │──────────────────────►│                          │
   │   GET /compra.php     │                          │
   │   ?pelicula_id=1      │                          │
   │                       │  4. SELECT funciones +   │
   │                       │     COUNT(asientos       │
   │                       │     WHERE disponible)    │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │◄──────────────────────│                          │
   │                       │                          │
   │  5. Elige función +   │                          │
   │     cantidad          │                          │
   │──────────────────────►│                          │
   │   POST a              │                          │
   │   procesar_compra.php │                          │
   │                       │                          │
   │                       │  6. BEGIN TRANSACTION    │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  7. SELECT ... FOR UPDATE│
   │                       │     (bloquea filas)      │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │                       │                          │
   │                       │  8. UPDATE asientos      │
   │                       │     SET disponible=0     │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  9. INSERT compra +      │
   │                       │     detalle_compra       │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  10. COMMIT              │
   │                       │─────────────────────────►│
   │                       │                          │
   │◄──────────────────────│                          │
   │                       │                          │
   │  11. GET /ticket.php  │                          │
   │      ?compra_id=1     │                          │
   │──────────────────────►│                          │
   │                       │  12. SELECT compra +     │
   │                       │      detalle + asientos  │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │◄──────────────────────│                          │
```

## Explicación del Flujo

### 1. Cartelera
- `index.php` consulta todas las películas y las muestra en una cuadrícula.
- Cada película tiene un enlace a `compra.php` con su ID.

### 2. Selección de Función
- `compra.php` recibe `pelicula_id` por GET.
- Consulta las funciones disponibles y la cantidad de asientos libres.
- Muestra un `<select>` con horarios y un `<input>` para cantidad.

### 3. Transacción de Compra
- `procesar_compra.php` recibe `funcion_id`, `cantidad` por POST.
- Usa una **transacción SQL** con `FOR UPDATE` para evitar condiciones de carrera (race conditions).
- `FOR UPDATE` bloquea las filas seleccionadas hasta que termine la transacción, impidiendo que dos usuarios compren el mismo asiento simultáneamente.

### 4. Ticket de Confirmación
- `ticket.php` muestra los detalles de la compra: película, horario, asientos, total.
- Solo el usuario que realizó la compra puede ver su ticket (filtro por `usuario_id`).

## Manejo de Errores

| Situación | Respuesta |
|---|---|
| No hay suficientes asientos | Rollback + mensaje de error |
| Función no existe | Rollback + mensaje de error |
| Usuario no logueado | Redirección a login |
| Cantidad > 10 | Mensaje de error |
| CSRF inválido | Formulario rechazado |
