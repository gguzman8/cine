# Seguridad

## 1. Hash de Contraseñas (PHP)

**Archivo:** `src/auth/register_handler.php`

```php
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

- Algoritmo: **bcrypt** con costo 12 (~250ms por hash)
- Nunca se almacena la contraseña en texto plano
- Verificación con `password_verify()` en `login_handler.php`

## 2. Protección contra SQL Injection

Todas las consultas usan **prepared statements** con PDO:

```php
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
```

Esto separa los datos del SQL, haciendo imposible la inyección.

## 3. Sesiones Seguras

**Archivo:** `src/includes/session.php`

| Medida | Código |
|---|---|
| Modo estricto de sesión | `session.use_strict_mode = 1` |
| Cookie HTTP-only | `session.cookie_httponly = 1` (no accesible desde JS) |
| SameSite Strict | `session.cookie_samesite = 'Strict'` (mitiga CSRF) |
| Regenerar ID al login | `session_regenerate_id(true)` |

## 4. CSRF (Cross-Site Request Forgery)

**Archivo:** `src/includes/functions.php`

- Token CSRF generado con `bin2hex(random_bytes(32))`
- Verificación en cada POST con `hash_equals()` (timing-safe)

## 5. Firewall (UFW)

**Aplicado por:** `setup.sh`

```
ufw default deny incoming        # Bloquear todo por defecto
ufw default allow outgoing       # Permitir tráfico saliente
ufw allow 80/tcp                 # HTTP (Apache)
ufw allow 2222/tcp               # SSH personalizado
ufw limit 2222/tcp               # Rate-limit SSH (máx 6 conexiones/30s)
```

## 6. Rate-Limit SSH con `ufw limit`

`ufw limit` aplica una política de **6 conexiones por 30 segundos** en el puerto SSH. Superado ese límite, la IP se bloquea temporalmente. Esto mitiga ataques de fuerza bruta.

## 7. Headers de Seguridad (Apache)

En `setup.sh` se configura Apache con:
- `Options -Indexes` — desactiva listado de directorios
- Sin ServerSignature — no expone versión de Apache/PHP

## 8. Principio de Mínimo Privilegio (BD)

- Usuario `cine_user` con acceso **solo** a la base de datos `cine`
- No tiene permisos para otras bases de datos del sistema
- Conexión solo desde `localhost` (no remota)

## 9. Logs de Seguridad

- `/var/log/cine_error.log` — eventos de watchdog, respaldos, errores
- Apache logs en `/var/log/apache2/` — accesos y errores web
- Los scripts nunca registran contraseñas en los logs

## 10. Checklist de Seguridad

- [ ] Contraseñas con bcrypt (cost >= 10)
- [ ] Prepared statements en todas las queries
- [ ] Sesión con httponly + samesite
- [ ] Token CSRF en formularios POST
- [ ] Firewall con UFW (deny incoming por defecto)
- [ ] SSH en puerto no estándar (2222)
- [ ] Rate-limit en SSH
- [ ] Options -Indexes en Apache
- [ ] Usuario BD sin permisos globales
- [ ] Backups con verificación de espacio en disco
