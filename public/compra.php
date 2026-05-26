<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('cliente', 'vendedor');

limpiar_funciones_expiradas($pdo);

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
     WHERE f.pelicula_id = ? AND f.expirada = FALSE
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
    <title><?= es_staff() ? 'Vender' : 'Comprar' ?> boletos - Cine Sendera</title>
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
            <p class="alert alert-error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <form action="procesar_compra.php" method="POST" id="form-compra">
            <input type="hidden" name="pelicula_id" value="<?= $pelicula['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="asientos" id="asientos-input" value="">

            <div class="form-stacked" style="max-width:500px;">
                <label>Función
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
                </label>
            </div>

            <div id="mapa-asientos" style="display:none;">
                <h3 class="section-title">🎫 Selecciona tus asientos</h3>
                <div id="seat-grid" class="seat-grid"></div>
                <div id="seat-info" class="seat-info">
                    <span>Seleccionados: <strong id="selected-count">0</strong></span>
                    <span>Total: <strong id="total-price">$0.00</strong></span>
                </div>
            </div>

            <label for="cupon">Código de cupón (opcional)</label>
            <input type="text" name="cupon" id="cupon" placeholder="Ej: CINE10" maxlength="20" style="max-width:300px;">

            <button type="submit" class="btn btn-primary" id="btn-confirmar" disabled>Confirmar compra</button>
        </form>
        <p><a href="index.php">Volver a la cartelera</a></p>
    </main>

<script>
const seatGrid = document.getElementById('seat-grid');
const seatInfo = document.getElementById('seat-info');
const selectedCount = document.getElementById('selected-count');
const totalPrice = document.getElementById('total-price');
const asientosInput = document.getElementById('asientos-input');
const btnConfirmar = document.getElementById('btn-confirmar');
const mapaAsientos = document.getElementById('mapa-asientos');
const selectFuncion = document.getElementById('funcion');
let selectedSeats = new Set();
let precioBase = <?= json_encode((float)$pelicula['precio']) ?>;
let funcionesData = {};

<?php foreach ($funciones as $f): ?>
funcionesData[<?= $f['id'] ?>] = { es_matinee: <?= $f['es_matinee'] ? 'true' : 'false' ?>, precio: <?= json_encode(precio_con_matinee((float)$pelicula['precio'], (bool)$f['es_matinee'])) ?> };
<?php endforeach; ?>

selectFuncion.addEventListener('change', function() {
    const id = this.value;
    selectedSeats.clear();
    updateUI();
    if (!id) {
        mapaAsientos.style.display = 'none';
        return;
    }
    mapaAsientos.style.display = 'block';
    seatGrid.innerHTML = '<p style="color:#999;">Cargando asientos…</p>';

    fetch('obtener_asientos.php?funcion_id=' + id)
        .then(r => r.json())
        .then(data => {
            renderSeats(data);
        })
        .catch(() => {
            seatGrid.innerHTML = '<p class="error">Error al cargar asientos.</p>';
        });
});

function renderSeats(asientos) {
    const rows = {};
    asientos.forEach(a => {
        if (!rows[a.fila]) rows[a.fila] = [];
        rows[a.fila].push(a);
    });

    let html = '<div class="seat-screen">Pantalla</div><div class="seat-rows">';
    const filasOrden = Object.keys(rows).sort();
    filasOrden.forEach(fila => {
        html += '<div class="seat-row">';
        html += '<span class="seat-row-label">' + fila + '</span>';
        rows[fila].sort((a, b) => a.numero - b.numero).forEach(a => {
            const cls = a.disponible ? 'seat-free' : 'seat-taken';
            const sel = selectedSeats.has(a.id) ? ' seat-selected' : '';
            html += '<button type="button" class="seat ' + cls + sel + '" data-id="' + a.id + '" data-fila="' + a.fila + '" data-num="' + a.numero + '" ' + (a.disponible ? '' : 'disabled') + '>' + a.numero + '</button>';
        });
        html += '<span class="seat-row-label">' + fila + '</span>';
        html += '</div>';
    });
    html += '</div>';
    seatGrid.innerHTML = html;

    seatGrid.querySelectorAll('.seat-free').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            if (selectedSeats.has(id)) {
                selectedSeats.delete(id);
                this.classList.remove('seat-selected');
            } else {
                selectedSeats.add(id);
                this.classList.add('seat-selected');
            }
            updateUI();
        });
    });
    updateUI();
}

function updateUI() {
    const count = selectedSeats.size;
    selectedCount.textContent = count;
    const funcId = selectFuncion.value;
    const data = funcionesData[funcId] || {};
    const price = count * data.precio;
    totalPrice.textContent = '$' + price.toFixed(2);
    asientosInput.value = Array.from(selectedSeats).join(',');
    btnConfirmar.disabled = count === 0;
}
</script>
</body>
</html>
