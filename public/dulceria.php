<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('cliente', 'vendedor');

$productos = $pdo->query('SELECT * FROM productos WHERE activa = TRUE ORDER BY nombre')->fetchAll();

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dulcería - Cine Uwuntu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Uwuntu — Dulcería</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <?php if (!es_staff() && !es_admin()): ?>
                <a href="index.php" class="btn-outline">Cartelera</a>
            <?php endif; ?>
            <?php if (es_staff()): ?>
                <a href="staff/vender.php" class="btn-outline">Vender</a>
                <a href="staff/checkin.php" class="btn-outline">Check-in <span class="badge badge-yellow"><?= $checkin_pendientes ?></span></a>
            <?php endif; ?>
            <a href="dulceria.php" class="btn-outline">Dulcería</a>
            <a href="perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>🍿 Dulcería</h2>

        <?php if (!empty($_SESSION['error_dulceria'])): ?>
            <p class="alert alert-error"><?= h($_SESSION['error_dulceria']); unset($_SESSION['error_dulceria']); ?></p>
        <?php endif; ?>

        <form action="procesar_dulceria.php" method="POST" id="form-dulceria">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <?php if (es_staff()): ?>
                <div class="form-stacked" style="max-width:500px;margin-bottom:1.5rem;">
                    <label>Nombre del cliente
                        <input type="text" name="cliente_nombre" required placeholder="Nombre del comprador">
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-stacked" style="max-width:500px;margin-bottom:1.5rem;">
                <label class="check">
                    <input type="checkbox" name="para_llevar" id="para-llevar" value="1" onchange="toggleBoleto()">
                    🛵 Llevar a la sala (ingresa tu número de boleto)
                </label>
                <div id="boleto-field" style="display:none;margin-top:.5rem;">
                    <label>Número de boleto (compra #)
                        <input type="number" name="boleto_id" id="boleto-id" min="1" placeholder="Ej: 1">
                    </label>
                    <p id="boleto-error" class="error" style="display:none;font-size:.85rem;margin-top:.3rem;"></p>
                </div>
            </div>

            <div class="cartelera">
                <?php foreach ($productos as $p): ?>
                    <div class="pelicula" style="text-align:center;">
                        <h3><?= h($p['nombre']) ?></h3>
                        <p style="color:#999;font-size:.85rem;"><?= h($p['descripcion']) ?></p>
                        <p style="font-size:1.3rem;font-weight:bold;color:#e50914;">$<?= number_format($p['precio'], 2) ?></p>
                        <div style="display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:.8rem;">
                            <button type="button" class="btn btn-sm btn-muted" onclick="decrement(<?= $p['id'] ?>)">−</button>
                            <input type="number" name="cantidad[<?= $p['id'] ?>]" id="qty-<?= $p['id'] ?>" value="0" min="0" max="50" style="width:50px;text-align:center;background:#222;border:1px solid #444;color:#fff;border-radius:4px;padding:.3rem;" readonly>
                            <button type="button" class="btn btn-sm btn-muted" onclick="increment(<?= $p['id'] ?>)">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="cart-summary" style="margin-top:2rem;padding:1rem;background:#191919;border-radius:8px;border:1px solid #2a2a2a;text-align:center;">
                <p style="font-size:1.1rem;">Total: <strong id="cart-total" style="color:#e50914;">$0.00</strong></p>
                <button type="submit" class="btn btn-primary" id="btn-comprar" disabled style="margin-top:.8rem;">Confirmar compra</button>
            </div>
        </form>
        <p style="margin-top:1rem;"><a href="index.php">Volver a la cartelera</a></p>
    </main>

<script>
const precios = {};
<?php foreach ($productos as $p): ?>
    precios[<?= $p['id'] ?>] = <?= $p['precio'] ?>;
<?php endforeach; ?>

function toggleBoleto() {
    const check = document.getElementById('para-llevar');
    const field = document.getElementById('boleto-field');
    const input = document.getElementById('boleto-id');
    field.style.display = check.checked ? 'block' : 'none';
    if (!check.checked) { input.value = ''; document.getElementById('boleto-error').style.display = 'none'; }
}

document.getElementById('boleto-id').addEventListener('input', function() {
    const err = document.getElementById('boleto-error');
    const val = this.value.trim();
    if (val === '' || parseInt(val) <= 0) {
        err.style.display = 'none';
        return;
    }
    fetch('validar_boleto.php?compra_id=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {
            if (!data.valido) {
                err.textContent = '❌ El número de boleto no existe.';
                err.style.display = 'block';
            } else {
                err.style.display = 'none';
            }
        })
        .catch(() => {});
});

document.getElementById('form-dulceria').addEventListener('submit', function(e) {
    const check = document.getElementById('para-llevar');
    if (check.checked) {
        const input = document.getElementById('boleto-id');
        const err = document.getElementById('boleto-error');
        if (!input.value || parseInt(input.value) <= 0) {
            err.textContent = '❌ Ingresa un número de boleto válido.';
            err.style.display = 'block';
            e.preventDefault();
            return;
        }
    }
});

function actualizarTotal() {
    let total = 0;
    let count = 0;
    for (const id in precios) {
        const el = document.getElementById('qty-' + id);
        const qty = parseInt(el.value) || 0;
        total += qty * precios[id];
        count += qty;
    }
    document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
    document.getElementById('btn-comprar').disabled = count === 0;
}

function increment(id) {
    const el = document.getElementById('qty-' + id);
    el.value = Math.min(50, (parseInt(el.value) || 0) + 1);
    actualizarTotal();
}

function decrement(id) {
    const el = document.getElementById('qty-' + id);
    el.value = Math.max(0, (parseInt(el.value) || 0) - 1);
    actualizarTotal();
}

actualizarTotal();
</script>
</body>
</html>
