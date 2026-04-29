<?php
// verify_overlay.php - Drag & Drop Puzzle CAPTCHA Challenge
// Included by blocks.php when a visitor needs to verify they are human.
// On success: sets $_SESSION['verified_human'] = true and redirects back.

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error    = '';
$verified = false;

// --- Helper: get real visitor IP ---
function overlay_get_ip() {
    foreach (['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}


$visitor_ip   = overlay_get_ip();


// --- Generate puzzle config if not set ---
// Puzzle: a 300x150 image split into 3 columns, user drags the missing piece into the correct slot
// We store the correct slot index (0,1,2) server-side
if (!isset($_SESSION['puzzle_slot'])) {
    $_SESSION['puzzle_slot']  = rand(0, 2); // which of the 3 slots is the missing piece
    $_SESSION['puzzle_theme'] = rand(0, 4); // which visual theme/pattern
}

// --- Handle CAPTCHA submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['puzzle_answer'])) {
    $submitted = intval($_POST['puzzle_answer']);
    $expected  = isset($_SESSION['puzzle_slot']) ? intval($_SESSION['puzzle_slot']) : -1;

    if ($expected !== -1 && $submitted === $expected) {
        unset($_SESSION['puzzle_slot'], $_SESSION['puzzle_theme'], $_SESSION['already_logged']);

        $_SESSION['verified_human'] = true;
        $_SESSION['verified_ip']    = $visitor_ip;

        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
                    || ($_SERVER['SERVER_PORT'] ?? '') === '443';

        setcookie('human_ticket', 'verified', [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $is_https,
            'samesite' => 'Lax',
        ]);
        setcookie('human_ticket_mobile', 'verified', [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => false,
            'secure'   => $is_https,
            'samesite' => 'Lax',
        ]);

        $redirect = htmlspecialchars($_SESSION['blocked_uri'] ?? '/', ENT_QUOTES, 'UTF-8');
        unset($_SESSION['blocked_uri']);
        header("Refresh: 0; url=" . $redirect);
        exit;

    } else {
        // Wrong answer — reset puzzle
        $error = "Incorrect — please try again.";
        unset($_SESSION['puzzle_slot'], $_SESSION['puzzle_theme']);
        $_SESSION['puzzle_slot']  = rand(0, 2);
        $_SESSION['puzzle_theme'] = rand(0, 4);
    }
}

if (empty($_SESSION['blocked_uri'])) {
    $_SESSION['blocked_uri'] = $_SERVER['REQUEST_URI'] ?? '/';
}

$correctSlot  = (int) $_SESSION['puzzle_slot'];
$puzzleTheme  = (int) $_SESSION['puzzle_theme'];

// Puzzle themes — each is a set of SVG shapes/colors that make a recognizable image
// The missing piece slot is cut out and shown separately for dragging
$themes = [
    // 0: Sunset landscape
    ['bg' => '#1a1a2e', 'name' => 'night sky', 'accent' => '#e94560'],
    // 1: Ocean waves
    ['bg' => '#0f3460', 'name' => 'ocean', 'accent' => '#16213e'],
    // 2: Forest
    ['bg' => '#1b4332', 'name' => 'forest', 'accent' => '#40916c'],
    // 3: Desert
    ['bg' => '#c9784a', 'name' => 'desert', 'accent' => '#e9b872'],
    // 4: Mountain
    ['bg' => '#2d3561', 'name' => 'mountain', 'accent' => '#a8dadc'],
];
$theme = $themes[$puzzleTheme];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Check</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0d0d1a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(99,51,255,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(233,69,96,0.1) 0%, transparent 50%);
        }

        .card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 36px 32px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }

        .lock-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6333ff, #e94560);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 22px;
        }

        h1 {
            font-size: 1.35rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        p.subtitle {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.45);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .puzzle-wrapper {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .puzzle-instruction {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 16px;
        }

        /* The main puzzle image with a gap */
        .puzzle-board {
            display: flex;
            gap: 3px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
            height: 100px;
            position: relative;
        }

        .puzzle-piece {
            flex: 1;
            border-radius: 0;
            position: relative;
            overflow: hidden;
        }

        .puzzle-piece canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Drop zone - the missing slot */
        .drop-zone {
            flex: 1;
            border: 2px dashed rgba(99,51,255,0.6);
            border-radius: 4px;
            background: rgba(99,51,255,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            position: relative;
        }

        .drop-zone.drag-over {
            border-color: #6333ff;
            background: rgba(99,51,255,0.2);
            transform: scale(1.02);
        }

        .drop-zone.correct {
            border-color: #40916c;
            background: rgba(64,145,108,0.2);
        }

        .drop-zone-label {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.25);
            letter-spacing: 0.5px;
        }

        /* The draggable piece */
        .drag-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 4px;
        }

        .drag-label {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.35);
        }

        .draggable-piece {
            width: 80px;
            height: 100px;
            border-radius: 8px;
            cursor: grab;
            border: 2px solid rgba(99,51,255,0.5);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(99,51,255,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
            flex-shrink: 0;
        }

        .draggable-piece:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 28px rgba(99,51,255,0.45);
        }

        .draggable-piece:active {
            cursor: grabbing;
            transform: scale(0.97);
        }

        .draggable-piece canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Touch drag visual feedback */
        .dragging-ghost {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.85;
            transform: translate(-50%, -50%) scale(1.05);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
            display: none;
            width: 80px;
            height: 100px;
        }

        .dragging-ghost canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .error-msg {
            background: rgba(233,69,96,0.12);
            border: 1px solid rgba(233,69,96,0.3);
            color: #e94560;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 0.73rem;
            color: rgba(255,255,255,0.2);
        }

        /* Hidden form */
        #verify-form { display: none; }

        /* Success overlay */
        .success-flash {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(64,145,108,0.15);
            z-index: 100;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        @media (max-width: 480px) {
            .card { padding: 24px 18px; }
            h1 { font-size: 1.1rem; }
            .puzzle-board { height: 80px; }
            .draggable-piece { width: 65px; height: 80px; }
            .dragging-ghost { width: 65px; height: 80px; }
        }
    </style>
</head>
<body>

<div class="success-flash" id="successFlash">✓</div>

<div class="card">
    <div class="lock-icon">🔒</div>
    <h1>Quick Security Check</h1>
    <p class="subtitle">Drag the missing piece into the correct position to continue.</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="puzzle-wrapper">
        <div class="puzzle-instruction">Complete the image</div>

        <div class="puzzle-board" id="puzzleBoard">
            <!-- Slots 0, 1, 2 rendered by JS — correct slot becomes drop zone -->
        </div>

        <div class="drag-area">
            <div class="drag-label">Drag this piece →</div>
            <div class="draggable-piece" id="draggablePiece" draggable="true">
                <canvas id="pieceCanvas"></canvas>
            </div>
        </div>
    </div>

    <form method="POST" action="" id="verify-form">
        <input type="hidden" name="puzzle_answer" id="puzzleAnswer" value="">
    </form>

    <p class="footer-note">This check protects the site from automated bots.</p>
</div>

<!-- Ghost element for touch drag -->
<div class="dragging-ghost" id="draggingGhost">
    <canvas id="ghostCanvas"></canvas>
</div>

<script>
(function() {
    const CORRECT_SLOT  = <?= $correctSlot ?>;
    const THEME_INDEX   = <?= $puzzleTheme ?>;
    const BOARD_COLS    = 3;

    // ── Drawing helpers ──────────────────────────────────────────
    const themes = [
        { sky: '#1a1a2e', horizon: '#e94560', ground: '#16213e', star: true  }, // night
        { sky: '#0f3460', horizon: '#533483', ground: '#16213e', star: false }, // ocean
        { sky: '#1b4332', horizon: '#40916c', ground: '#081c15', star: false }, // forest
        { sky: '#c9784a', horizon: '#e9b872', ground: '#7f4f24', star: false }, // desert
        { sky: '#2d3561', horizon: '#a8dadc', ground: '#1d3557', star: true  }, // mountain
    ];
    const T = themes[THEME_INDEX];

    function drawSlice(ctx, sliceIndex, totalSlices, w, h) {
        const sliceW = w / totalSlices;
        const ox = sliceIndex * sliceW; // x offset within full image

        // Sky gradient
        const skyGrad = ctx.createLinearGradient(0, 0, 0, h * 0.65);
        skyGrad.addColorStop(0, T.sky);
        skyGrad.addColorStop(1, T.horizon);
        ctx.fillStyle = skyGrad;
        ctx.fillRect(0, 0, w, h * 0.65);

        // Ground
        const gndGrad = ctx.createLinearGradient(0, h * 0.65, 0, h);
        gndGrad.addColorStop(0, T.horizon);
        gndGrad.addColorStop(1, T.ground);
        ctx.fillStyle = gndGrad;
        ctx.fillRect(0, h * 0.65, w, h * 0.35);

        // Stars / dots
        if (T.star) {
            ctx.fillStyle = 'rgba(255,255,255,0.7)';
            // Deterministic star positions based on slice
            for (let i = 0; i < 8; i++) {
                const sx = ((ox * 7 + i * 37 + sliceIndex * 13) % (sliceW * 0.9));
                const sy = ((ox * 3 + i * 19 + sliceIndex * 7)  % (h * 0.55));
                const r  = (i % 3 === 0) ? 1.5 : 1;
                ctx.beginPath();
                ctx.arc(sx, sy, r, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        // Horizon shapes (mountains / waves / trees based on theme)
        ctx.fillStyle = T.ground;
        ctx.beginPath();
        if (THEME_INDEX === 2 || THEME_INDEX === 4) {
            // Mountains / trees — triangles
            const peaks = [
                { x: -ox * 0.3,           y: h * 0.45, w: sliceW * 1.1 },
                { x: sliceW * 0.5 - ox * 0.1, y: h * 0.38, w: sliceW * 0.8 },
            ];
            peaks.forEach(p => {
                ctx.beginPath();
                ctx.moveTo(p.x, h * 0.65);
                ctx.lineTo(p.x + p.w / 2, p.y);
                ctx.lineTo(p.x + p.w, h * 0.65);
                ctx.closePath();
                ctx.fillStyle = THEME_INDEX === 4 ? '#1d3557' : '#2d6a4f';
                ctx.fill();
            });
        } else {
            // Waves / dunes — curves
            ctx.beginPath();
            ctx.moveTo(0, h * 0.65);
            const freq = THEME_INDEX === 1 ? 0.04 : 0.02;
            for (let x = 0; x <= sliceW; x++) {
                const y = h * 0.65 + Math.sin((x + ox) * freq * Math.PI) * (h * 0.06);
                ctx.lineTo(x, y);
            }
            ctx.lineTo(sliceW, h);
            ctx.lineTo(0, h);
            ctx.closePath();
            ctx.fillStyle = T.ground;
            ctx.fill();
        }

        // Subtle vertical separator line
        ctx.strokeStyle = 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(w - 0.5, 0);
        ctx.lineTo(w - 0.5, h);
        ctx.stroke();
    }

    // ── Build board ───────────────────────────────────────────────
    const board      = document.getElementById('puzzleBoard');
    const pieceEl    = document.getElementById('draggablePiece');
    const pieceCanvas= document.getElementById('pieceCanvas');
    const ghostCanvas= document.getElementById('ghostCanvas');
    const ghost      = document.getElementById('draggingGhost');

    // Board piece height from CSS
    const PIECE_H = 100;

    function buildBoard() {
        board.innerHTML = '';

        for (let i = 0; i < BOARD_COLS; i++) {
            if (i === CORRECT_SLOT) {
                // Drop zone
                const dz = document.createElement('div');
                dz.className = 'drop-zone';
                dz.dataset.slot = i;
                dz.innerHTML = '<span class="drop-zone-label">Drop here</span>';
                board.appendChild(dz);

                dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
                dz.addEventListener('dragleave', ()  => dz.classList.remove('drag-over'));
                dz.addEventListener('drop',      e   => { e.preventDefault(); handleDrop(i, dz); });
            } else {
                // Filled slice
                const wrap = document.createElement('div');
                wrap.className = 'puzzle-piece';
                const c = document.createElement('canvas');
                wrap.appendChild(c);
                board.appendChild(wrap);

                // Size canvas to actual rendered size
                requestAnimationFrame(() => {
                    const W = wrap.offsetWidth;
                    const H = wrap.offsetHeight || PIECE_H;
                    c.width  = W;
                    c.height = H;
                    const ctx = c.getContext('2d');
                    drawSlice(ctx, i, BOARD_COLS, W, H);
                });
            }
        }
    }

    // Draw the draggable piece & ghost
    function drawDraggablePiece() {
        requestAnimationFrame(() => {
            const W = pieceEl.offsetWidth  || 80;
            const H = pieceEl.offsetHeight || PIECE_H;

            [pieceCanvas, ghostCanvas].forEach(c => {
                c.width  = W;
                c.height = H;
                const ctx = c.getContext('2d');
                drawSlice(ctx, CORRECT_SLOT, BOARD_COLS, W, H);
            });
        });
    }

    buildBoard();
    drawDraggablePiece();

    // ── Drag & Drop (desktop) ─────────────────────────────────────
    pieceEl.addEventListener('dragstart', e => {
        e.dataTransfer.setData('text/plain', 'piece');
        e.dataTransfer.effectAllowed = 'move';
    });

    function handleDrop(slotIndex, dropZone) {
        if (slotIndex !== CORRECT_SLOT) return;
        submitAnswer(slotIndex, dropZone);
    }

    // ── Touch drag (mobile) ───────────────────────────────────────
    let touchActive = false;

    pieceEl.addEventListener('touchstart', e => {
        touchActive = true;
        ghost.style.display = 'block';
        movGhost(e.touches[0]);
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!touchActive) return;
        e.preventDefault();
        movGhost(e.touches[0]);
        highlightDropZones(e.touches[0]);
    }, { passive: false });

    document.addEventListener('touchend', e => {
        if (!touchActive) return;
        touchActive = false;
        ghost.style.display = 'none';
        const touch = e.changedTouches[0];
        const el = document.elementFromPoint(touch.clientX, touch.clientY);
        const dz = el ? el.closest('.drop-zone') : null;
        if (dz) {
            const slot = parseInt(dz.dataset.slot);
            submitAnswer(slot, dz);
        }
        clearDropHighlights();
    });

    function movGhost(touch) {
        ghost.style.left = touch.clientX + 'px';
        ghost.style.top  = touch.clientY + 'px';
    }

    function highlightDropZones(touch) {
        document.querySelectorAll('.drop-zone').forEach(dz => {
            const r = dz.getBoundingClientRect();
            const over = touch.clientX >= r.left && touch.clientX <= r.right
                      && touch.clientY >= r.top  && touch.clientY <= r.bottom;
            dz.classList.toggle('drag-over', over);
        });
    }

    function clearDropHighlights() {
        document.querySelectorAll('.drop-zone').forEach(dz => dz.classList.remove('drag-over'));
    }

    // ── Submit ────────────────────────────────────────────────────
    function submitAnswer(slot, dropZone) {
        dropZone.classList.remove('drag-over');
        dropZone.classList.add('correct');
        dropZone.innerHTML = '';

        // Place the piece visually in the slot
        const c = document.createElement('canvas');
        dropZone.appendChild(c);
        const W = dropZone.offsetWidth;
        const H = dropZone.offsetHeight || PIECE_H;
        c.width  = W;
        c.height = H;
        drawSlice(c.getContext('2d'), CORRECT_SLOT, BOARD_COLS, W, H);

        // Hide the draggable piece
        pieceEl.style.opacity = '0';
        pieceEl.style.pointerEvents = 'none';

        // Flash success then submit
        const flash = document.getElementById('successFlash');
        flash.style.display = 'flex';
        setTimeout(() => {
            document.getElementById('puzzleAnswer').value = slot;
            document.getElementById('verify-form').submit();
        }, 400);
    }

    // Redraw on resize
    window.addEventListener('resize', () => {
        buildBoard();
        drawDraggablePiece();
    });
})();
</script>
</body>
</html>
<?php exit; ?>
