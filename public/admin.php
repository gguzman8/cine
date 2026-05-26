<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

if (!es_admin()) {
    redirect('index.php');
}

limpiar_funciones_expiradas($pdo);

$total_usuarios  = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$total_compras   = $pdo->query('SELECT COUNT(*) FROM compras')->fetchColumn();
$total_ingresos  = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM compras')->fetchColumn();
$total_peliculas = $pdo->query('SELECT COUNT(*) FROM peliculas')->fetchColumn();
$total_boletos   = $pdo->query('SELECT COUNT(*) FROM detalle_compra')->fetchColumn();

$ultimas_compras = $pdo->query(
    'SELECT c.id, u.nombre AS usuario, p.titulo, c.total, c.created_at
     FROM compras c
     JOIN usuarios u ON u.id = c.usuario_id
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     ORDER BY c.created_at DESC
     LIMIT 10'
)->fetchAll();

$funciones_hoy = $pdo->query(
    'SELECT f.*, p.titulo,
        (SELECT COUNT(*) FROM asientos a WHERE a.funcion_id = f.id AND a.disponible = TRUE) AS libres
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE DATE(f.horario) = CURDATE() AND f.expirada = FALSE
     ORDER BY f.horario'
)->fetchAll();

$cupones   = $pdo->query('SELECT * FROM cupones ORDER BY created_at DESC')->fetchAll();
$peliculas = $pdo->query(
    'SELECT p.id, p.titulo, p.precio, p.poster, p.activa,
        (SELECT COUNT(*) FROM funciones f WHERE f.pelicula_id = p.id AND f.expirada = FALSE) AS funciones_count
     FROM peliculas p ORDER BY p.activa DESC, p.titulo'
)->fetchAll();
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
            <a href="index.php">Cartelera</a>
            <a href="staff/vender.php">Vender</a>
            <a href="staff/checkin.php">Check-in</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Panel de Administración</h2>

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
                <p>Compras</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($total_ingresos, 2) ?></h3>
                <p>Ingresos totales</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_boletos ?></h3>
                <p>Boletos vendidos</p>
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
                <label>Título <input type="text" name="titulo" required></label>
                <label>Sinopsis <input type="text" name="sinopsis" placeholder="Descripción breve"></label>
                <label>Precio ($) <input type="number" name="precio" step="0.01" min="1" required></label>
                <label>Poster <input type="text" name="poster" placeholder="default.svg"></label>
                <button type="submit" class="btn btn-primary">Crear película</button>
            </form>
        </section>

        <section>
            <h3 class="section-title" id="funciones">🎫 Agregar Función</h3>
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
                <label>Horario <input type="datetime-local" name="horario" required></label>
                <label>Sala <input type="text" name="sala" placeholder="Sala 1" required></label>
                <label class="checkbox-label"><input type="checkbox" name="es_matinee" value="1"> Matiné</label>
                <button type="submit" class="btn btn-primary">Agregar función</button>
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
                <label>Nombre <input type="text" name="nombre" required></label>
                <label>Correo <input type="email" name="email" required></label>
                <label>Contraseña <input type="password" name="password" minlength="6" required></label>
                <button type="submit" class="btn btn-primary">Crear staff</button>
            </form>
        </section>
    </main>
</body>
</html>
