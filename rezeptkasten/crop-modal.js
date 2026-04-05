/**
 * crop-modal.js
 * Self-contained canvas-based crop modal.
 * Supports pinch-to-zoom, mouse-wheel zoom, drag/pan, 90° rotation.
 * Always crops a 1:1 square. Output: PNG blob + dataURL via onConfirm callback.
 *
 * Usage:
 *   CropModal.open(file, function(blob, dataURL) { ... });
 *   CropModal.close();
 */
(function () {
  'use strict';

  // ── CSS ────────────────────────────────────────────────────────────────────
  const STYLES = `
    #crop-modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.92);
      z-index: 99999;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    #crop-canvas-area {
      flex: 1;
      position: relative;
      overflow: hidden;
      min-height: 0;
    }
    #crop-canvas {
      display: block;
      width: 100%;
      height: 100%;
      touch-action: none;
      cursor: grab;
    }
    #crop-canvas:active { cursor: grabbing; }
    #crop-modal-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background: #111;
      gap: 8px;
      flex-shrink: 0;
    }
    .crop-btn-group { display: flex; gap: 8px; }
    .crop-btn {
      background: rgba(255,255,255,0.12);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 8px;
      padding: 10px 18px;
      font-size: 15px;
      cursor: pointer;
      min-height: 44px;
      min-width: 44px;
      touch-action: manipulation;
      user-select: none;
      white-space: nowrap;
      line-height: 1.2;
    }
    .crop-btn:active { background: rgba(255,255,255,0.30); }
    .crop-btn.primary { background: #0d6efd; border-color: #0a58ca; }
    .crop-btn.primary:active { background: #0a58ca; }
    #crop-loading {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 18px; background: rgba(0,0,0,0.5);
    }
  `;

  // Inject styles once
  if (!document.getElementById('crop-modal-styles')) {
    const s = document.createElement('style');
    s.id = 'crop-modal-styles';
    s.textContent = STYLES;
    document.head.appendChild(s);
  }

  // ── State ──────────────────────────────────────────────────────────────────
  let overlay      = null;
  let canvasEl     = null;
  let ctx          = null;
  let img          = null;
  let onConfirmCb  = null;

  // Transform (pan/zoom/rotate)
  let tx       = 0;   // pan x (pixels, relative to crop-frame center)
  let ty       = 0;   // pan y
  let scale    = 1;   // zoom
  let angle    = 0;   // rotation degrees (multiple of 90)
  let cropSize = 0;   // side length of the crop square (pixels on canvas)

  // Pointer tracking
  let pointers      = {};   // { pointerId: {x,y} }
  let lastPinchDist = null;
  let lastPanX      = 0;
  let lastPanY      = 0;

  // ── DOM creation ──────────────────────────────────────────────────────────
  function ensureOverlay() {
    if (overlay) return;

    overlay = document.createElement('div');
    overlay.id = 'crop-modal-overlay';
    overlay.innerHTML = `
      <div id="crop-canvas-area">
        <canvas id="crop-canvas"></canvas>
        <div id="crop-loading" style="display:none">Bild wird geladen…</div>
      </div>
      <div id="crop-modal-toolbar">
        <div class="crop-btn-group">
          <button class="crop-btn" id="crop-rot-l" title="90° links drehen">&#8634; &minus;90°</button>
          <button class="crop-btn" id="crop-rot-r" title="90° rechts drehen">&#8635; +90°</button>
        </div>
        <div class="crop-btn-group">
          <button class="crop-btn" id="crop-cancel">Abbrechen</button>
          <button class="crop-btn primary" id="crop-save">Speichern</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    canvasEl = document.getElementById('crop-canvas');
    ctx = canvasEl.getContext('2d');

    // Buttons
    document.getElementById('crop-rot-l').addEventListener('click', () => doRotate(-90));
    document.getElementById('crop-rot-r').addEventListener('click', () => doRotate(90));
    document.getElementById('crop-cancel').addEventListener('click', close);
    document.getElementById('crop-save').addEventListener('click', exportCrop);

    // Mouse wheel zoom (not covered by Pointer Events)
    canvasEl.addEventListener('wheel',      onWheel, { passive: false });

    // Pointer events (handles both mouse and touch uniformly)
    canvasEl.addEventListener('pointerdown',   onPointerDown,  { passive: false });
    canvasEl.addEventListener('pointermove',   onPointerMove,  { passive: false });
    canvasEl.addEventListener('pointerup',     onPointerEnd);
    canvasEl.addEventListener('pointercancel', onPointerEnd);

    // Window resize
    window.addEventListener('resize', onResize);
  }

  // ── Public API ─────────────────────────────────────────────────────────────
  function open(file, onConfirm) {
    onConfirmCb = onConfirm;
    ensureOverlay();
    overlay.style.display = 'flex';
    document.getElementById('crop-loading').style.display = 'flex';

    const reader = new FileReader();
    reader.onload = function (e) {
      const image = new Image();
      image.onload = function () {
        img = image;
        document.getElementById('crop-loading').style.display = 'none';
        resizeCanvas();
        initTransform();
        render();
      };
      image.onerror = function () {
        document.getElementById('crop-loading').textContent = 'Fehler beim Laden des Bildes.';
      };
      image.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  function close() {
    if (overlay) overlay.style.display = 'none';
    img         = null;
    pointers    = {};
    lastPinchDist = null;
  }

  // ── Canvas sizing ──────────────────────────────────────────────────────────
  function resizeCanvas() {
    const area = document.getElementById('crop-canvas-area');
    const rect = area.getBoundingClientRect();
    canvasEl.width  = Math.round(rect.width)  || window.innerWidth;
    canvasEl.height = Math.round(rect.height) || (window.innerHeight - 80);
    cropSize = Math.floor(Math.min(canvasEl.width, canvasEl.height) * 0.85);
  }

  function onResize() {
    if (!overlay || overlay.style.display === 'none' || !img) return;
    resizeCanvas();
    clampState();
    render();
  }

  // ── Transform helpers ──────────────────────────────────────────────────────
  function initTransform() {
    angle = 0;
    tx    = 0;
    ty    = 0;
    // Start with image just covering the crop square
    scale = getMinScale();
  }

  function getMinScale() {
    if (!img) return 1;
    return Math.max(cropSize / img.naturalWidth, cropSize / img.naturalHeight);
  }

  // Effective screen dimensions of the (possibly rotated) image
  function effectiveDims() {
    const rot90 = Math.floor(angle / 90) % 2 !== 0;   // true when rotated 90 or 270
    const ew = (rot90 ? img.naturalHeight : img.naturalWidth)  * scale;
    const eh = (rot90 ? img.naturalWidth  : img.naturalHeight) * scale;
    return { ew, eh };
  }

  // Keep scale ≥ minScale and pan within bounds so crop is always covered
  function clampState() {
    const minScale = getMinScale();
    if (scale < minScale) scale = minScale;

    const { ew, eh } = effectiveDims();
    const maxTx = Math.max(0, (ew - cropSize) / 2);
    const maxTy = Math.max(0, (eh - cropSize) / 2);
    tx = Math.max(-maxTx, Math.min(maxTx, tx));
    ty = Math.max(-maxTy, Math.min(maxTy, ty));
  }

  // ── Rendering ─────────────────────────────────────────────────────────────
  function render() {
    if (!img || !canvasEl) return;

    const cw = canvasEl.width;
    const ch = canvasEl.height;
    const cx = cw / 2;
    const cy = ch / 2;
    const hs = cropSize / 2;   // half side

    ctx.clearRect(0, 0, cw, ch);

    // Draw image with current transform (centered on crop-frame center + pan)
    ctx.save();
    ctx.translate(cx + tx, cy + ty);
    ctx.rotate(angle * Math.PI / 180);
    ctx.scale(scale, scale);
    ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
    ctx.restore();

    // Semi-transparent mask outside crop square
    ctx.fillStyle = 'rgba(0,0,0,0.55)';
    ctx.fillRect(0,       0,        cw,     cy - hs);          // top
    ctx.fillRect(0,       cy + hs,  cw,     ch - cy - hs);     // bottom
    ctx.fillRect(0,       cy - hs,  cx - hs, cropSize);        // left
    ctx.fillRect(cx + hs, cy - hs,  cw - cx - hs, cropSize);   // right

    // Crop border
    ctx.strokeStyle = 'rgba(255,255,255,0.85)';
    ctx.lineWidth   = 2;
    ctx.strokeRect(cx - hs, cy - hs, cropSize, cropSize);

    // Rule-of-thirds grid
    ctx.strokeStyle = 'rgba(255,255,255,0.30)';
    ctx.lineWidth   = 1;
    const third = cropSize / 3;
    for (let i = 1; i <= 2; i++) {
      ctx.beginPath();
      ctx.moveTo(cx - hs + third * i, cy - hs);
      ctx.lineTo(cx - hs + third * i, cy + hs);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(cx - hs, cy - hs + third * i);
      ctx.lineTo(cx + hs, cy - hs + third * i);
      ctx.stroke();
    }
  }

  // ── Rotate ────────────────────────────────────────────────────────────────
  function doRotate(deg) {
    angle = (angle + deg + 360) % 360;
    clampState();
    render();
  }

  // ── Mouse wheel zoom ──────────────────────────────────────────────────────
  function onWheel(e) {
    e.preventDefault();
    const factor = e.deltaY < 0 ? 1.1 : 0.9;
    scale *= factor;
    clampState();
    render();
  }

  // ── Pointer events (touch + mouse, unified) ────────────────────────────────
  function onPointerDown(e) {
    e.preventDefault();
    canvasEl.setPointerCapture(e.pointerId);
    pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

    const pts = Object.values(pointers);
    if (pts.length === 1) {
      lastPanX = pts[0].x;
      lastPanY = pts[0].y;
      lastPinchDist = null;
    } else if (pts.length === 2) {
      lastPinchDist = dist2(pts[0], pts[1]);
    }
  }

  function onPointerMove(e) {
    e.preventDefault();
    if (!pointers[e.pointerId]) return;
    pointers[e.pointerId] = { x: e.clientX, y: e.clientY };

    const pts = Object.values(pointers);
    if (pts.length === 1) {
      tx += pts[0].x - lastPanX;
      ty += pts[0].y - lastPanY;
      lastPanX = pts[0].x;
      lastPanY = pts[0].y;
      clampState();
      render();
    } else if (pts.length === 2) {
      const d = dist2(pts[0], pts[1]);
      if (lastPinchDist && d > 0) {
        scale *= d / lastPinchDist;
        clampState();
        render();
      }
      lastPinchDist = d;
    }
  }

  function onPointerEnd(e) {
    delete pointers[e.pointerId];
    const pts = Object.values(pointers);
    if (pts.length === 1) {
      lastPanX = pts[0].x;
      lastPanY = pts[0].y;
      lastPinchDist = null;
    } else if (pts.length === 0) {
      lastPinchDist = null;
    }
  }

  function dist2(a, b) {
    return Math.hypot(b.x - a.x, b.y - a.y);
  }

  // ── Export ────────────────────────────────────────────────────────────────
  function exportCrop() {
    const outSize = 800;
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width  = outSize;
    exportCanvas.height = outSize;
    const ectx = exportCanvas.getContext('2d');

    // Fill white background (prevents black pixels when PNG has transparency)
    ectx.fillStyle = '#ffffff';
    ectx.fillRect(0, 0, outSize, outSize);

    /*
     * Render: map the crop frame to the full output canvas.
     *   - Image centre in display space is offset (tx, ty) from crop centre.
     *   - 1 display pixel = outSize/cropSize output pixels.
     */
    const ratio = outSize / cropSize;
    ectx.save();
    ectx.translate(outSize / 2 + tx * ratio, outSize / 2 + ty * ratio);
    ectx.rotate(angle * Math.PI / 180);
    ectx.scale(scale * ratio, scale * ratio);
    ectx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
    ectx.restore();

    exportCanvas.toBlob(function (blob) {
      if (!blob) {
        alert('Fehler beim Exportieren des Bildes.');
        return;
      }
      const dataURL = exportCanvas.toDataURL('image/png');
      close();
      if (onConfirmCb) onConfirmCb(blob, dataURL);
    }, 'image/png');
  }

  // ── Expose ─────────────────────────────────────────────────────────────────
  window.CropModal = { open: open, close: close };
})();
