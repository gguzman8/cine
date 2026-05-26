<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

if (!es_admin()) {
    redirect('index.php');
}

$pdo = null;
try {
    require_once __DIR__ . '/../src/config/database.php';
    limpiar_funciones_expiradas($pdo);
} catch (\RuntimeException $e) {
    // $pdo stays null; monitoring will reflect the outage
}

$total_usuarios   = $pdo ? $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() : 0;
$total_peliculas  = $pdo ? $pdo->query('SELECT COUNT(*) FROM peliculas')->fetchColumn() : 0;
$total_boletos    = $pdo ? $pdo->query('SELECT COUNT(*) FROM detalle_compra')->fetchColumn() : 0;
$total_compras    = $pdo ? $pdo->query('SELECT (SELECT COUNT(*) FROM compras) + (SELECT COUNT(*) FROM dulceria_compras)')->fetchColumn() : 0;
$total_ingresos   = $pdo ? $pdo->query("SELECT COALESCE((SELECT SUM(total) FROM compras), 0) + COALESCE((SELECT SUM(total) FROM dulceria_compras), 0)")->fetchColumn() : 0;
$total_dulceria   = $pdo ? $pdo->query('SELECT COUNT(*) FROM dulceria_detalle')->fetchColumn() : 0;

$ultimas_compras = $pdo ? $pdo->query(
    'SELECT c.id, u.nombre AS usuario, p.titulo, c.total, c.created_at
     FROM compras c
     JOIN usuarios u ON u.id = c.usuario_id
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     ORDER BY c.created_at DESC
     LIMIT 10'
)->fetchAll() : [];

$funciones_hoy = $pdo ? $pdo->query(
    'SELECT f.*, p.titulo,
        (SELECT COUNT(*) FROM asientos a WHERE a.funcion_id = f.id AND a.disponible = TRUE) AS libres
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE DATE(f.horario) = CURDATE() AND f.expirada = FALSE
     ORDER BY f.horario'
)->fetchAll() : [];

// Métricas del servidor
$load       = sys_getloadavg();
$cpu_cores  = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
$cpu_pct    = min(100, round($load[0] / max(1, $cpu_cores) * 100, 1));

$mem = [];
foreach (file('/proc/meminfo') as $line) {
    [$k, $v] = explode(':', $line, 2);
    $mem[trim($k)] = (int) trim($v);
}
$ram_total_mb = intdiv($mem['MemTotal'], 1024);
$ram_used_mb  = intdiv($mem['MemTotal'] - $mem['MemAvailable'], 1024);
$ram_pct      = round($ram_used_mb / $ram_total_mb * 100, 1);

$disk_total_gb = round(disk_total_space('/') / (1024 ** 3), 1);
$disk_free_gb  = round(disk_free_space('/') / (1024 ** 3), 1);
$disk_used_gb  = round($disk_total_gb - $disk_free_gb, 1);
$disk_pct      = round($disk_used_gb / $disk_total_gb * 100, 1);

$uptime_raw = (int) explode(' ', file_get_contents('/proc/uptime'))[0];
$uptime_str = sprintf('%dd %dh %dm',
    intdiv($uptime_raw, 86400),
    intdiv($uptime_raw % 86400, 3600),
    intdiv($uptime_raw % 3600, 60)
);

$apache_ok = function_exists('shell_exec')
    ? trim(shell_exec('systemctl is-active apache2 2>/dev/null') ?? '') === 'active'
    : true;
$mysql_ok = function_exists('shell_exec')
    ? trim(shell_exec('systemctl is-active mysql 2>/dev/null') ?? '') === 'active'
    : ($pdo !== null);

$todas_funciones = $pdo ? $pdo->query(
    "SELECT f.id, f.horario, f.sala, f.es_matinee, f.expirada, p.titulo,
        COALESCE((SELECT COUNT(dc.id) FROM compras c JOIN detalle_compra dc ON dc.compra_id = c.id WHERE c.funcion_id = f.id), 0) AS boletos_vendidos,
        COALESCE((SELECT SUM(c.total) FROM compras c WHERE c.funcion_id = f.id), 0) AS ingresos
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     ORDER BY f.horario DESC"
)->fetchAll() : [];

$cupones   = $pdo ? $pdo->query('SELECT * FROM cupones ORDER BY created_at DESC')->fetchAll() : [];
$productos = $pdo ? $pdo->query('SELECT * FROM productos ORDER BY activa DESC, nombre')->fetchAll() : [];
$peliculas = $pdo ? $pdo->query(
    'SELECT p.id, p.titulo, p.precio, p.poster, p.activa,
        (SELECT COUNT(*) FROM funciones f WHERE f.pelicula_id = p.id AND f.expirada = FALSE) AS funciones_count
     FROM peliculas p ORDER BY p.activa DESC, p.titulo'
)->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Admin</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="dulceria.php" class="btn-outline">Dulcería</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Panel de Administración</h2>

        <?php if (!$pdo): ?>
            <p class="error">Base de datos no disponible — mostrando solo métricas del sistema.</p>
        <?php endif; ?>

        <section class="admin-stats">
            <div class="stat-card">
                <h3><?= $total_peliculas ?></h3>
                <p>Películas</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_usuarios ?></h3>
                <p>Usuarios</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_compras ?></h3>
                <p>Compras totales</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($total_ingresos, 2) ?></h3>
                <p>Ingresos totales</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_boletos ?></h3>
                <p>Boletos vendidos</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_dulceria ?></h3>
                <p>Productos dulcería</p>
            </div>
        </section>

        <section>
            <h3>Monitoreo del Servidor</h3>
            <div class="monitor-grid">
                <div class="monitor-card">
                    <div class="monitor-label">CPU</div>
                    <div class="monitor-value"><?= $cpu_pct ?>%</div>
                    <div class="progress"><div class="progress-fill" style="width:<?= $cpu_pct ?>%;background:<?= $cpu_pct > 80 ? '#e50914' : ($cpu_pct > 50 ? '#ff9800' : '#4caf50') ?>;"></div></div>
                    <div class="monitor-sub">Carga: <?= $load[0] ?> / <?= $load[1] ?> / <?= $load[2] ?> &bull; <?= $cpu_cores ?> núcleos</div>
                </div>
                <div class="monitor-card">
                    <div class="monitor-label">RAM</div>
                    <div class="monitor-value"><?= $ram_used_mb ?> MB / <?= $ram_total_mb ?> MB</div>
                    <div class="progress"><div class="progress-fill" style="width:<?= $ram_pct ?>%;background:<?= $ram_pct > 80 ? '#e50914' : ($ram_pct > 50 ? '#ff9800' : '#4caf50') ?>;"></div></div>
                    <div class="monitor-sub"><?= $ram_pct ?>% usado</div>
                </div>
                <div class="monitor-card">
                    <div class="monitor-label">Disco</div>
                    <div class="monitor-value"><?= $disk_used_gb ?> GB / <?= $disk_total_gb ?> GB</div>
                    <div class="progress"><div class="progress-fill" style="width:<?= $disk_pct ?>%;background:<?= $disk_pct > 80 ? '#e50914' : ($disk_pct > 50 ? '#ff9800' : '#4caf50') ?>;"></div></div>
                    <div class="monitor-sub"><?= $disk_pct ?>% usado &bull; <?= $disk_free_gb ?> GB libres</div>
                </div>
                <div class="monitor-card">
                    <div class="monitor-label">Uptime</div>
                    <div class="monitor-value"><?= $uptime_str ?></div>
                    <div class="monitor-sub">Tiempo activo del sistema</div>
                </div>
                <div class="monitor-card">
                    <div class="monitor-label">Apache</div>
                    <div class="monitor-value <?= $apache_ok ? 'status-ok' : 'status-err' ?>"><?= $apache_ok ? 'Activo' : 'Inactivo' ?></div>
                    <div class="monitor-sub"><span class="service-dot <?= $apache_ok ? 'dot-ok' : 'dot-err' ?>"></span> HTTP Server</div>
                </div>
                <div class="monitor-card">
                    <div class="monitor-label">MySQL</div>
                    <div class="monitor-value <?= $mysql_ok ? 'status-ok' : 'status-err' ?>"><?= $mysql_ok ? 'Activo' : 'Inactivo' ?></div>
                    <div class="monitor-sub"><span class="service-dot <?= $mysql_ok ? 'dot-ok' : 'dot-err' ?>"></span> Base de datos</div>
                </div>
            </div>
        </section>

        <section>
            <h3 class="section-title">📅 Funciones de Hoy <span class="count"><?= count($funciones_hoy) ?></span></h3>
            <table>
                <thead>
                    <tr><th>Película</th><th>Horario</th><th>Sala</th><th>Asientos libres</th><th>Matiné</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($funciones_hoy as $f): ?>
                        <tr>
                            <td><?= h($f['titulo']) ?></td>
                            <td><?= date('H:i', strtotime($f['horario'])) ?></td>
                            <td><?= h($f['sala']) ?></td>
                            <td><?= $f['libres'] ?>/40</td>
                            <td><?= $f['es_matinee'] ? '✅' : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <section>
            <h3 class="section-title">📋 Todas las Funciones <span class="count"><?= count($todas_funciones) ?></span></h3>
            <table>
                <thead>
                    <tr><th>Película</th><th>Horario</th><th>Sala</th><th>Matiné</th><th>Boletos vendidos</th><th>Ingresos</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($todas_funciones as $f): ?>
                        <tr class="<?= $f['expirada'] ? 'row-disabled' : '' ?>">
                            <td><?= h($f['titulo']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($f['horario'])) ?></td>
                            <td><?= h($f['sala']) ?></td>
                            <td><?= $f['es_matinee'] ? '✅ Sí' : '—' ?></td>
                            <td><?= $f['boletos_vendidos'] ?>/40</td>
                            <td><strong>$<?= number_format($f['ingresos'], 2) ?></strong></td>
                            <td><span class="badge <?= $f['expirada'] ? 'badge-gray' : 'badge-green' ?>"><?= $f['expirada'] ? 'Expirada' : 'Activa' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3 class="section-title">🧾 Últimas Compras <span class="count"><?= count($ultimas_compras) ?></span></h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Usuario</th><th>Película</th><th>Total</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_compras as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= h($c['usuario']) ?></td>
                            <td><?= h($c['titulo']) ?></td>
                            <td><strong>$<?= number_format($c['total'], 2) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <section>
            <h3 class="section-title">👥 Usuarios Registrados <span class="count"><?= $total_usuarios ?? 0 ?></span></h3>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pdo->query('SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY created_at DESC') as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= h($u['nombre']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td><span class="badge <?= match($u['rol']) { 'admin' => 'badge-red', 'vendedor' => 'badge-yellow', default => 'badge-blue' } ?>"><?= h($u['rol']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3 class="section-title">🏷️ Cupones de Descuento <span class="count"><?= count($cupones) ?></span></h3>
            <table>
                <thead>
                    <tr><th>Código</th><th>Descuento</th><th>Usos</th><th>Máximo</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cupones as $cp): ?>
                        <tr>
                            <td><strong><?= h($cp['codigo']) ?></strong></td>
                            <td><span class="badge badge-green"><?= $cp['descuento_porcentaje'] ?>%</span></td>
                            <td><?= $cp['usos_actuales'] ?></td>
                            <td><?= $cp['usos_maximos'] ?></td>
                            <td><span class="status-dot <?= $cp['activo'] ? 'status-on' : 'status-off' ?>"></span><span class="badge <?= $cp['activo'] ? 'badge-green' : 'badge-gray' ?>"><?= $cp['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3 class="section-title" id="peliculas">🎬 Películas Registradas <span class="count"><?= count($peliculas) ?></span></h3>
            <table>
                <thead>
                    <tr><th>ID</th><th>Título</th><th>Precio</th><th>Funciones</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($peliculas as $p): ?>
                        <tr class="<?= !$p['activa'] ? 'row-disabled' : '' ?>">
                            <td><?= $p['id'] ?></td>
                            <td><?= h($p['titulo']) ?></td>
                            <td>$<?= number_format($p['precio'], 2) ?></td>
                            <td><?= $p['funciones_count'] ?></td>
                            <td><span class="status-dot <?= $p['activa'] ? 'status-on' : 'status-off' ?>"></span><span class="badge <?= $p['activa'] ? 'badge-green' : 'badge-gray' ?>"><?= $p['activa'] ? 'Activa' : 'Inactiva' ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="admin_pelicula_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-edit">✏️ Editar</a>
                                    <a href="admin_pelicula_toggle_handler.php?id=<?= $p['id'] ?>" class="btn btn-sm <?= $p['activa'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>"><?= $p['activa'] ? '🚫 Desactivar' : '✅ Activar' ?></a>
                                    <?php if ($p['funciones_count'] == 0): ?>
                                        <a href="admin_pelicula_delete_handler.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('¿Eliminar <?= h($p['titulo']) ?>?')">🗑️ Eliminar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3 class="section-title">🎬 Agregar Película</h3>
            <?php if (!empty($_SESSION['error_pelicula'])): ?>
                <p class="alert alert-error"><?= h($_SESSION['error_pelicula']); unset($_SESSION['error_pelicula']); ?></p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success_pelicula'])): ?>
                <p class="alert alert-success"><?= h($_SESSION['success_pelicula']); unset($_SESSION['success_pelicula']); ?></p>
            <?php endif; ?>
            <form action="admin_pelicula_handler.php" method="POST" class="form-stacked">
                <label>Título
                    <input type="text" name="titulo" required>
                </label>
                <label>Sinopsis
                    <input type="text" name="sinopsis" placeholder="Descripción breve">
                </label>
                <label>Precio ($)
                    <input type="number" name="precio" step="0.01" min="1" required>
                </label>
                <label>Poster
                    <input type="text" name="poster" placeholder="default.svg">
                </label>
                <button type="submit" class="btn">Crear película</button>
            </form>
        </section>

        <section id="dulceria">
            <h3 class="section-title">🍿 Productos de Dulcería <span class="count"><?= count($productos) ?></span></h3>
            <?php if (!empty($_SESSION['error_producto'])): ?>
                <p class="alert alert-error"><?= h($_SESSION['error_producto']); unset($_SESSION['error_producto']); ?></p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success_producto'])): ?>
                <p class="alert alert-success"><?= h($_SESSION['success_producto']); unset($_SESSION['success_producto']); ?></p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $pr): ?>
                        <tr class="<?= !$pr['activa'] ? 'row-disabled' : '' ?>">
                            <td><?= $pr['id'] ?></td>
                            <td><?= h($pr['nombre']) ?></td>
                            <td>$<?= number_format($pr['precio'], 2) ?></td>
                            <td><span class="badge <?= $pr['activa'] ? 'badge-green' : 'badge-gray' ?>"><?= $pr['activa'] ? 'Activo' : 'Inactivo' ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="admin_producto_edit.php?id=<?= $pr['id'] ?>" class="btn btn-sm btn-edit">✏️ Editar</a>
                                    <a href="admin_producto_toggle_handler.php?id=<?= $pr['id'] ?>" class="btn btn-sm <?= $pr['activa'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>"><?= $pr['activa'] ? '🚫 Desactivar' : '✅ Activar' ?></a>
                                    <a href="admin_producto_delete_handler.php?id=<?= $pr['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('¿Eliminar <?= h($pr['nombre']) ?>?')">🗑️ Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form action="admin_producto_handler.php" method="POST" class="form-stacked" style="margin-top:1rem;">
                <label>Nombre del producto
                    <input type="text" name="nombre" required placeholder="Ej: Palomitas Grandes">
                </label>
                <label>Descripción
                    <input type="text" name="descripcion" placeholder="Descripción breve">
                </label>
                <label>Precio ($)
                    <input type="number" name="precio" step="0.01" min="1" required>
                </label>
                <button type="submit" class="btn">Agregar producto</button>
            </form>
        </section>

        <section>
            <h3 class="section-title" id="funciones">📅 Agregar Función</h3>
            <?php if (!empty($_SESSION['error_funcion'])): ?>
                <p class="alert alert-error"><?= h($_SESSION['error_funcion']); unset($_SESSION['error_funcion']); ?></p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success_funcion'])): ?>
                <p class="alert alert-success"><?= h($_SESSION['success_funcion']); unset($_SESSION['success_funcion']); ?></p>
            <?php endif; ?>
            <form action="admin_funcion_handler.php" method="POST" class="form-stacked">
                <label>Película
                    <select name="pelicula_id" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($peliculas as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (!empty($_SESSION['nueva_pelicula_id']) && $_SESSION['nueva_pelicula_id'] == $p['id']) ? 'selected' : '' ?>><?= h($p['titulo']) ?></option>
                        <?php endforeach; unset($_SESSION['nueva_pelicula_id']); ?>
                    </select>
                </label>
                <label>Horario
                    <input type="datetime-local" name="horario" required>
                </label>
                <label>Sala
                    <input type="text" name="sala" placeholder="Sala 1" required>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="es_matinee" value="1"> Matiné
                </label>
                <button type="submit" class="btn">Agregar función</button>
            </form>
        </section>

        <section>
            <h3 class="section-title">👤 Registrar Staff</h3>
            <?php if (!empty($_SESSION['error_staff'])): ?>
                <p class="alert alert-error"><?= h($_SESSION['error_staff']); unset($_SESSION['error_staff']); ?></p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success_staff'])): ?>
                <p class="alert alert-success"><?= h($_SESSION['success_staff']); unset($_SESSION['success_staff']); ?></p>
            <?php endif; ?>
            <form action="admin_staff_handler.php" method="POST" class="form-stacked">
                <label>Nombre
                    <input type="text" name="nombre" required>
                </label>
                <label>Correo
                    <input type="email" name="email" required>
                </label>
                <label>Contraseña
                    <input type="password" name="password" minlength="6" required>
                </label>
                <button type="submit" class="btn">Crear staff</button>
            </form>
        </section>
    </main>
</body>
</html>
