<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Design System | Einkaufs App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="assets/tokens.css">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body { background-color: var(--color-bg); font-family: var(--font-family-base); padding-top: 72px; }
    .ds-section { margin-bottom: 3rem; }
    .ds-section-title { font-size: 1.4rem; font-weight: 700; color: var(--color-primary); border-bottom: 2px solid var(--color-primary-light); padding-bottom: 0.4rem; margin-bottom: 1.5rem; }
    .ds-token-label { font-size: 0.78rem; color: var(--color-text-muted); margin-top: 0.4rem; word-break: break-all; }
    .ds-token-value { font-size: 0.72rem; color: var(--color-text-faint); }

    /* Colour swatches */
    .swatch-wrap { display: flex; flex-wrap: wrap; gap: 1rem; }
    .swatch { text-align: center; width: 90px; }
    .swatch-circle { width: 60px; height: 60px; border-radius: var(--radius-circle); margin: 0 auto; border: 1px solid rgba(0,0,0,0.08); }

    /* Spacing bars */
    .spacing-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.6rem; }
    .spacing-bar { background: var(--color-primary); height: 16px; border-radius: 3px; }

    /* Radius demos */
    .radius-wrap { display: flex; flex-wrap: wrap; gap: 1.2rem; align-items: flex-end; }
    .radius-box { width: 64px; height: 64px; background: var(--color-primary-light); border: 2px solid var(--color-primary); }

    /* Shadow demos */
    .shadow-wrap { display: flex; flex-wrap: wrap; gap: 1.5rem; }
    .shadow-box { width: 100px; height: 64px; background: var(--color-surface); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 0.72rem; color: var(--color-text-muted); text-align: center; padding: 0.3rem; }

    /* Component examples */
    .component-example { background: var(--color-surface); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-card); }
    .component-label { font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.8rem; }

    /* Inline sticky-header demo (non-fixed) */
    .sticky-header-demo {
      background: var(--color-surface);
      border-top: 1px solid var(--color-border);
      padding: var(--space-md);
      box-shadow: var(--shadow-header);
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-radius: var(--radius-md);
    }
  </style>
</head>
<body>

  <!-- Sticky Header -->
  <div class="sticky-header d-flex align-items-center">
    <a href="start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <strong>Design System</strong>
  </div>

  <div class="container py-4">
    <h1 class="mb-1" style="color:var(--color-primary);">Design System</h1>
    <p class="text-muted mb-5">Alle Design-Tokens und Live-Komponenten der Einkaufs App auf einen Blick.</p>

    <!-- ═══════════════════════════════════════════════════ COLOURS -->
    <div class="ds-section">
      <div class="ds-section-title">Colours</div>
      <div class="swatch-wrap">

        <?php
        $colours = [
          '--color-primary'          => '#4B15DA',
          '--color-primary-light'    => '#ede8fd',
          '--color-primary-border'   => '#4B15DA33',
          '--color-primary-icon'     => '#815BE5',
          '--color-accent'           => '#23af64',
          '--color-accent-green'     => '#4caf50',
          '--color-accent-green-bg'  => '#f7fff6',
          '--color-info'             => '#2196F3',
          '--color-bg'               => '#f5f5f5',
          '--color-surface'          => '#ffffff',
          '--color-border'           => '#ddd',
          '--color-border-subtle'    => '#e0e0e0',
          '--color-text'             => '#212529',
          '--color-text-secondary'   => '#333',
          '--color-text-muted'       => '#555',
          '--color-text-faint'       => '#888',
          '--color-placeholder-bg'   => '#e0e0e0',
          '--color-placeholder-text' => '#c0c0c0',
          '--color-table-header-bg'  => '#f7f7f7',
          '--color-table-border'     => '#e0e0e0',
          '--color-danger'           => '#d9534f',
          '--color-danger-hover'     => '#b52b27',
        ];
        foreach ($colours as $var => $hex):
        ?>
        <div class="swatch">
          <div class="swatch-circle" style="background:var(<?= $var ?>);"></div>
          <div class="ds-token-label"><?= $var ?></div>
          <div class="ds-token-value"><?= $hex ?></div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ TYPOGRAPHY -->
    <div class="ds-section">
      <div class="ds-section-title">Typography</div>

      <div class="mb-3">
        <div class="ds-token-label">--font-family-base</div>
        <div style="font-family:var(--font-family-base); font-size:1.1rem;">Roboto — The quick brown fox jumps over the lazy dog</div>
      </div>
      <?php
      $sizes = [
        '--font-size-base'    => '1rem',
        '--font-size-sm'      => '0.97em',
        '--font-size-icon'    => '24px',
        '--font-size-icon-lg' => '48px',
        '--font-size-icon-xl' => '80px',
      ];
      foreach ($sizes as $var => $val):
      ?>
      <div class="mb-2 d-flex align-items-baseline gap-3">
        <span class="ds-token-label" style="min-width:160px;"><?= $var ?></span>
        <span style="font-size:var(<?= $var ?>); line-height:1.1;">Aa</span>
        <span class="ds-token-value"><?= $val ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════ SPACING -->
    <div class="ds-section">
      <div class="ds-section-title">Spacing</div>
      <?php
      $spaces = [
        '--space-xs' => '0.3rem',
        '--space-sm' => '0.5rem',
        '--space-md' => '0.75rem',
        '--space-lg' => '1rem',
        '--space-xl' => '1.2rem',
      ];
      foreach ($spaces as $var => $val):
      ?>
      <div class="spacing-row">
        <span class="ds-token-label" style="min-width:100px;"><?= $var ?></span>
        <div class="spacing-bar" style="width:var(<?= $var ?>);"></div>
        <span class="ds-token-value"><?= $val ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════ BORDER RADIUS -->
    <div class="ds-section">
      <div class="ds-section-title">Border Radius</div>
      <div class="radius-wrap">
        <?php
        $radii = [
          '--radius-sm'     => '8px',
          '--radius-md'     => '12px',
          '--radius-lg'     => '13px',
          '--radius-xl'     => '16px',
          '--radius-circle' => '50%',
          '--radius-pill'   => '1rem',
        ];
        foreach ($radii as $var => $val):
        ?>
        <div class="text-center">
          <div class="radius-box" style="border-radius:var(<?= $var ?>);"></div>
          <div class="ds-token-label"><?= $var ?></div>
          <div class="ds-token-value"><?= $val ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ SHADOWS -->
    <div class="ds-section">
      <div class="ds-section-title">Shadows</div>
      <div class="shadow-wrap">
        <?php
        $shadows = [
          '--shadow-card'        => '0 2px 6px rgba(0,0,0,0.15)',
          '--shadow-card-hover'  => '0 6px 16px rgba(0,0,0,0.2)',
          '--shadow-header'      => '0 2px 8px rgba(0,0,0,0.1)',
          '--shadow-icon'        => '0 2px 8px rgba(40,40,40,0.12)',
          '--shadow-kachel'      => '0 2px 6px rgba(0,0,0,0.08)',
          '--shadow-kachel-hover'=> '0 4px 16px rgba(76,175,80,0.05)',
        ];
        foreach ($shadows as $var => $val):
        ?>
        <div class="text-center">
          <div class="shadow-box" style="box-shadow:var(<?= $var ?>);"><?= htmlspecialchars($var) ?></div>
          <div class="ds-token-value mt-1"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ TRANSITIONS -->
    <div class="ds-section">
      <div class="ds-section-title">Transitions</div>
      <?php
      $transitions = [
        '--transition-card'   => 'box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out',
        '--transition-border' => 'border-color 0.2s, box-shadow 0.2s',
      ];
      foreach ($transitions as $var => $val):
      ?>
      <div class="mb-2">
        <span class="ds-token-label"><?= $var ?></span>
        <span class="ds-token-value ms-2"><?= htmlspecialchars($val) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════ COMPONENTS -->
    <div class="ds-section-title" style="font-size:1.6rem; color:var(--color-primary); border-bottom: 2px solid var(--color-primary-light); padding-bottom:0.4rem; margin-bottom:2rem;">Live Components</div>

    <!-- card-md -->
    <div class="component-example">
      <div class="component-label">.card-md (tile card)</div>
      <a href="#" class="text-decoration-none" onclick="return false;">
        <div class="card card-md p-4 bg-white" style="max-width:340px;">
          <div class="d-flex align-items-center">
            <span class="icon-gradient">
              <span class="material-symbols-outlined">palette</span>
            </span>
            <div>
              <h5 class="text-dark mb-1">Design System</h5>
            </div>
          </div>
        </div>
      </a>
    </div>

    <!-- icon-gradient -->
    <div class="component-example">
      <div class="component-label">.icon-gradient (icon circle)</div>
      <span class="icon-gradient">
        <span class="material-symbols-outlined">palette</span>
      </span>
    </div>

    <!-- Buttons -->
    <div class="component-example">
      <div class="component-label">.btn (Bootstrap primary &amp; outline-dark)</div>
      <button class="btn btn-primary me-2" style="background:var(--color-primary); border-color:var(--color-primary);">Primary</button>
      <button class="btn btn-outline-dark">Outline Dark</button>
    </div>

    <!-- sticky-header (inline demo) -->
    <div class="component-example">
      <div class="component-label">.sticky-header (inline demo)</div>
      <div class="sticky-header-demo">
        <button class="btn btn-outline-dark btn-sm">
          <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <strong>Seitentitel</strong>
        <span></span>
      </div>
    </div>

    <!-- loading-spinner -->
    <div class="component-example">
      <div class="component-label">.loading-spinner</div>
      <div style="display:flex; align-items:center; justify-content:center; height:80px;">
        <div class="loading-spinner"></div>
      </div>
    </div>

    <!-- modal-dots -->
    <div class="component-example">
      <div class="component-label">.modal-dots (dot indicator)</div>
      <div class="modal-dots">
        <span class="modal-dot active"></span>
        <span class="modal-dot"></span>
        <span class="modal-dot"></span>
      </div>
    </div>

    <!-- modal-nav-btn -->
    <div class="component-example">
      <div class="component-label">.modal-nav-btn</div>
      <button class="modal-nav-btn me-2">Zurück</button>
      <button class="modal-nav-btn">Weiter</button>
    </div>

  </div><!-- /container -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
