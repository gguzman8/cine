<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

$pelicula_id = (int) ($_GET['pelicula_id'] ?? 0);

$pelicula = $pdo->prepare('SELECT * FROM peliculas WHERE id = ?');
$pelicula->execute([$pelicula_id]);
$pelicula = $pelicula->fetch();

if (!$pelicula) {
    redirect('index.php');
}

$funciones = $pdo->prepare(
    'SELECT f.*,
        (SELECT COUNT(*) FROM asientos a WHERE a.funcion_id = f.id AND a.disponible = TRUE) AS asientos_libres
     FROM funciones f
     WHERE f.pelicula_id = ?
     ORDER BY f.horario'
);
$funciones->execute([$pelicula_id]);
$funciones = $funciones->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar boletos - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="index.php" class="btn-outline">Cartelera</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2><?= h($pelicula['titulo']) ?></h2>
        <p><strong>Precio base:</strong> $<?= number_format($pelicula['precio'], 2) ?></p>

        <?php if (!empty($_SESSION['error'])): ?>
            <p class="error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <form action="procesar_compra.php" method="POST">
            <input type="hidden" name="pelicula_id" value="<?= $pelicula['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <label for="funcion">Función</label>
            <select name="funcion_id" id="funcion" required>
                <option value="">Selecciona horario</option>
                <?php foreach ($funciones as $f):
                    $precio_efectivo = precio_con_matinee((float)$pelicula['precio'], (bool)$f['es_matinee']);
                ?>
                    <option value="<?= $f['id'] ?>" data-libres="<?= $f['asientos_libres'] ?>">
                        <?= date('d/m/Y H:i', strtotime($f['horario'])) ?>
                        — <?= h($f['sala']) ?>
                        (<?= $f['asientos_libres'] ?> libres)
                        <?php if ($f['es_matinee']): ?>
                            — Matiné $<?= number_format($precio_efectivo, 2) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="cantidad">Cantidad de boletos</label>
            <input type="number" name="cantidad" id="cantidad" min="1" max="10" value="1" required>

            <label for="cupon">Código de cupón (opcional)</label>
            <input type="text" name="cupon" id="cupon" placeholder="Ej: CINE10" maxlength="20">

            <button type="submit">Confirmar compra</button>
        </form>
        <p><a href="index.php">Volver a la cartelera</a></p>
    </main>
</body>
</html>
