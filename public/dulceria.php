<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('cliente', 'vendedor');

$productos = $pdo->query('SELECT * FROM productos WHERE activa = TRUE ORDER BY nombre')->fetchAll();

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();

function producto_emoji(string $nombre): string {
    $lower = mb_strtolower($nombre);
    $map = [
        'palomitas' => '🍿', 'refresco'   => '🥤', 'nachos' => '🧀',
        'hot dog'   => '🌭', 'dulces'     => '🍬', 'agua'   => '💧',
        'cafe'      => '☕',  'chocolate'  => '🍫', 'pizza'  => '🍕',
        'sandwich'  => '🥪', 'churros'    => '🥐', 'helado' => '🍦',
    ];
    foreach ($map as $key => $emoji) {
        if (str_contains($lower, $key)) return $emoji;
    }
    return '🛒';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dulcería - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Dulcería layout ─────────────────────────── */
        .dulceria-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 768px) {
            .dulceria-layout { grid-template-columns: 1fr; }
            .cart-panel { position: static !important; }
        }

        /* ── Product grid ────────────────────────────── */
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.2rem;
        }
        .producto-card {
            background: #1e1e1e;
            border: 2px solid #2a2a2a;
            border-radius: 16px;
            padding: 1.4rem 1rem 1rem;
            text-align: center;
            transition: transform .2s, border-color .2s, box-shadow .2s;
        }
        .producto-card:hover {
            transform: translateY(-4px);
            border-color: #444;
        }
        .producto-card.has-items {
            border-color: #e50914;
            box-shadow: 0 0 16px rgba(229,9,20,.2);
        }
        .producto-emoji {
            font-size: 2.6rem;
            display: block;
            margin-bottom: .6rem;
            line-height: 1;
        }
        .producto-nombre {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .3rem;
            color: #fff;
        }
        .producto-desc {
            font-size: .78rem;
            color: #777;
            margin-bottom: .8rem;
            line-height: 1.4;
            min-height: 2.5em;
        }
        .producto-precio {
            font-size: 1.35rem;
            font-weight: 700;
            color: #e50914;
            margin-bottom: 1rem;
        }

        /* ── Quantity control ────────────────────────── */
        .qty-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
        }
        .qty-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid #444;
            background: #111;
            color: #eee;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color .15s, background .15s, transform .1s;
            padding: 0;
        }
        .qty-btn:hover { border-color: #e50914; color: #e50914; }
        .qty-btn:active { transform: scale(.9); }
        .qty-btn.plus:hover { background: #e50914; border-color: #e50914; color: #fff; }
        .qty-input {
            width: 44px;
            text-align: center;
            background: #111;
            border: 1px solid #333;
            color: #fff;
            border-radius: 8px;
            padding: .35rem 0;
            font-size: 1rem;
            font-weight: 600;
        }

        /* ── Cart panel ──────────────────────────────── */
        .cart-panel {
            position: sticky;
            top: 1.5rem;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            overflow: hidden;
        }
        .cart-header {
            padding: 1rem 1.2rem;
            background: #222;
            border-bottom: 1px solid #2a2a2a;
            font-weight: 600;
            font-size: 1rem;
        }
        .cart-footer {
            padding: 1.2rem;
        }
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .cart-total-label { font-size: .85rem; color: #888; text-transform: uppercase; letter-spacing: .05em; }
        .cart-total-amount { font-size: 1.6rem; font-weight: 700; color: #e50914; }
        .btn-comprar {
            width: 100%;
            padding: .8rem;
            font-size: 1rem;
        }
        .btn-comprar:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }

        /* ── Staff client field ──────────────────────── */
        .staff-field {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
        }
        .staff-field label {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            font-size: .85rem;
            color: #aaa;
        }
        .staff-field input {
            padding: .6rem .8rem;
            border: 1px solid #333;
            border-radius: 8px;
            background: #111;
            color: #eee;
            font-size: .95rem;
            transition: border-color .2s;
        }
        .staff-field input:focus { outline: none; border-color: #e50914; }
    </style>
</head>
<body>
    <header>
        <h1>Cine Sendera — Dulcería</h1>
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
                <div class="staff-field">
                    <label>
                        Nombre del cliente
                        <input type="text" name="cliente_nombre" required placeholder="Nombre del comprador">
                    </label>
                </div>
            <?php endif; ?>

            <div class="dulceria-layout">
                <!-- Productos -->
                <div class="productos-grid">
                    <?php foreach ($productos as $p): ?>
                        <div class="producto-card" id="card-<?= $p['id'] ?>">
                            <span class="producto-emoji"><?= producto_emoji($p['nombre']) ?></span>
                            <div class="producto-nombre"><?= h($p['nombre']) ?></div>
                            <div class="producto-desc"><?= h($p['descripcion']) ?></div>
                            <div class="producto-precio">$<?= number_format($p['precio'], 2) ?></div>
                            <div class="qty-control">
                                <button type="button" class="qty-btn minus" onclick="decrement(<?= $p['id'] ?>)">−</button>
                                <input type="number" name="cantidad[<?= $p['id'] ?>]" id="qty-<?= $p['id'] ?>"
                                       value="0" min="0" max="50" class="qty-input" readonly>
                                <button type="button" class="qty-btn plus" onclick="increment(<?= $p['id'] ?>)">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumen -->
                <div class="cart-panel">
                    <div class="cart-header">🛒 Tu pedido</div>
                    <div class="cart-footer">
                        <div class="cart-total-row">
                            <span class="cart-total-label">Total</span>
                            <span class="cart-total-amount" id="cart-total">$0.00</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-comprar" id="btn-comprar" disabled>
                            Confirmar compra
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <p style="margin-top:1.5rem;"><a href="index.php">← Volver a la cartelera</a></p>
    </main>

<script>
const precios = {};
<?php foreach ($productos as $p): ?>
precios[<?= $p['id'] ?>] = <?= $p['precio'] ?>;
<?php endforeach; ?>

function actualizarTotal() {
    let total = 0;
    let count = 0;
    for (const id in precios) {
        const qty = parseInt(document.getElementById('qty-' + id).value) || 0;
        document.getElementById('card-' + id).classList.toggle('has-items', qty > 0);
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
