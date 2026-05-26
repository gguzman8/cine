# Flujo de Compra

## Diagrama de Secuencia

```
Usuario               Navegador/PHP                  MySQL
   │                       │                          │
   │  1. GET /index.php    │                          │
   │──────────────────────►│                          │
   │                       │  2. SELECT peliculas +   │
   │                       │     funciones             │
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
   │  5. Elige función     │                          │
   │                       │  6. AJAX: GET             │
   │                       │     obtener_asientos.php  │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │                       │  JSON: [{id, fila,       │
   │                       │  numero, disponible}]    │
   │◄──────────────────────│                          │
   │                       │                          │
   │  7. Selecciona        │                          │
   │     asientos +        │                          │
   │     (cupón opcional)  │                          │
   │──────────────────────►│                          │
   │   POST a              │                          │
   │   procesar_compra.php │                          │
   │                       │                          │
   │                       │  8. BEGIN TRANSACTION    │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  9. SELECT ... FOR UPDATE│
   │                       │     (bloquea filas)      │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │                       │                          │
   │                       │  10. UPDATE asientos     │
   │                       │     SET disponible=0     │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  11. INSERT compra +     │
   │                       │     detalle_compra       │
   │                       │─────────────────────────►│
   │                       │                          │
   │                       │  12. COMMIT              │
   │                       │─────────────────────────►│
   │                       │                          │
   │◄──────────────────────│                          │
   │                       │                          │
   │  13. GET /ticket.php  │                          │
   │      ?compra_id=1     │                          │
   │──────────────────────►│                          │
   │                       │  14. SELECT compra +     │
   │                       │      detalle + asientos  │
   │                       │─────────────────────────►│
   │                       │◄─────────────────────────│
   │◄──────────────────────│                          │
```

## Explicación del Flujo

### 1. Cartelera
- `index.php` consulta todas las películas activas y las muestra en cards.
- Una segunda tabla lista las funciones disponibles con horarios, sala y precio.
- Las funciones matiné se resaltan con fondo verde y precio tachado + descuento.

### 2. Selección de Función y Asientos
- `compra.php` recibe `pelicula_id` por GET.
- Muestra un `<select>` con funciones disponibles y asientos libres.
- Al elegir una función, se carga el mapa de asientos vía AJAX (`obtener_asientos.php`).
- El mapa muestra 40 asientos (filas A-E, 8 columnas) con colores:
  - **Verde:** disponible
  - **Rojo:** ocupado
  - **Amarillo:** seleccionado por el usuario
- El total se actualiza en tiempo real.
- Si el usuario es `vendedor`, se muestra un campo para ingresar el nombre del cliente.
- Cupón de descuento opcional.

### 3. Transacción de Compra
- `procesar_compra.php` recibe `funcion_id`, `asientos` (IDs separados por coma) por POST.
- Usa una **transacción SQL** con `FOR UPDATE` para evitar condiciones de carrera.
- `FOR UPDATE` bloquea las filas seleccionadas hasta que termine la transacción.
- Descuento matiné: se aplica solo si la función es matiné Y la compra es antes de las 12:00.
- Si es staff, guarda el nombre del vendedor; si es cliente, guarda "Sistema".
- Si hay cupón, valida disponibilidad y aplica descuento.

### 4. Ticket de Confirmación
- `ticket.php` muestra los detalles de la compra: película, horario, asientos, total.
- Muestra el nombre del cliente y quién vendió (staff o "Sistema").
- Staff puede hacer check-in directamente desde el ticket.
- Estado del check-in: pendiente (badge amarillo) o realizado (badge verde).

### 5. Check-in (Staff)
- `staff/checkin.php` permite buscar una compra por ID.
- Muestra los datos completos y permite registrar la entrada.
- `checkin_handler.php` procesa con transacción y `FOR UPDATE`.
- También se puede hacer check-in directamente desde `ticket.php`.

## Manejo de Errores

| Situación | Respuesta |
|---|---|
| No hay suficientes asientos | Rollback + mensaje de error |
| Función no existe o expirada | Rollback + mensaje de error |
| Usuario no logueado | Redirección a login |
| Más de 10 asientos | Mensaje de error |
| CSRF inválido | Formulario rechazado |
| Cupón inválido o agotado | Rollback + mensaje de error |
| Asiento ya no disponible | Rollback + mensaje de error |
