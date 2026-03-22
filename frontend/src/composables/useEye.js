import { onMounted, onUnmounted, ref } from 'vue';

export function useEye(canvasId = 'hero-canvas') {
  let canvas = null;
  let ctx = null;
  let animationFrameId = null;
  let frame = 0;

  /* Particle system */
  const PARTS = 70;
  let parts = [];

  /* Gaze state */
  let mouseX = 0.5;
  let mouseY = 0.5;
  let gazeX = 0.5;
  let gazeY = 0.5;

  function initializeParticles() {
    parts = [];
    for (let i = 0; i < PARTS; i++) {
      parts.push({
        x: Math.random(),
        y: Math.random(),
        r: Math.random() * 1.8 + 0.4,
        speed: Math.random() * 0.00018 + 0.00006,
        opacity: Math.random() * 0.5 + 0.15,
      });
    }
  }

  function handleMouseMove(e) {
    mouseX = e.clientX / window.innerWidth;
    mouseY = e.clientY / window.innerHeight;
  }

  function resize() {
    if (!canvas) return;
    const dpr = window.devicePixelRatio || 1;
    canvas.width = canvas.offsetWidth * dpr;
    canvas.height = canvas.offsetHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function draw() {
    if (!canvas || !ctx) return;

    animationFrameId = requestAnimationFrame(draw);
    frame++;

    const W = canvas.offsetWidth;
    const H = canvas.offsetHeight;
    ctx.clearRect(0, 0, W, H);

    /* Smooth gaze follow */
    gazeX += (mouseX - gazeX) * 0.055;
    gazeY += (mouseY - gazeY) * 0.055;
    gazeX += Math.sin(frame * 0.008) * 0.0006; /* idle micro-drift */
    gazeY += Math.cos(frame * 0.011) * 0.0004;

    /* Eye centre — right portion of canvas */
    const cx = W * 0.52;
    const cy = H * 0.5;
    const R = Math.min(W, H) * 0.32;

    /* ── OUTER GLOW ─────────────────────────────────────────────────────── */
    const glow = ctx.createRadialGradient(cx, cy, R * 0.6, cx, cy, R * 1.6);
    glow.addColorStop(0, 'rgba(201,169,110,0.07)');
    glow.addColorStop(1, 'rgba(201,169,110,0)');
    ctx.beginPath();
    ctx.arc(cx, cy, R * 1.6, 0, Math.PI * 2);
    ctx.fillStyle = glow;
    ctx.fill();

    /* ── SCLERA CLIP ────────────────────────────────────────────────────── */
    const EW = R * 1.38;
    const EH = R * 0.82;
    ctx.save();
    ctx.beginPath();
    ctx.ellipse(cx, cy, EW, EH, 0, 0, Math.PI * 2);
    ctx.clip();

    /* Sclera fill */
    const sg = ctx.createRadialGradient(cx - R * 0.15, cy - R * 0.2, 0, cx, cy, R * 1.1);
    sg.addColorStop(0, '#FDFCFA');
    sg.addColorStop(0.7, '#F5F0E8');
    sg.addColorStop(1, '#E8DFD0');
    ctx.beginPath();
    ctx.ellipse(cx, cy, EW, EH, 0, 0, Math.PI * 2);
    ctx.fillStyle = sg;
    ctx.fill();

    /* ── IRIS ────────────────────────────────────────────────────────────── */
    const maxShift = R * 0.18;
    const gx = cx + (gazeX - 0.5) * 2 * maxShift;
    const gy = cy + (gazeY - 0.5) * 2 * maxShift * 0.7;
    const IR = R * 0.52;

    /* Iris base */
    const ig = ctx.createRadialGradient(gx - IR * 0.2, gy - IR * 0.25, IR * 0.05, gx, gy, IR);
    ig.addColorStop(0, '#3d6b50');
    ig.addColorStop(0.35, '#2a4a3a');
    ig.addColorStop(0.75, '#1e3328');
    ig.addColorStop(1, '#0f1f18');
    ctx.beginPath();
    ctx.arc(gx, gy, IR, 0, Math.PI * 2);
    ctx.fillStyle = ig;
    ctx.fill();

    /* Iris fibres + rings */
    ctx.save();
    ctx.beginPath();
    ctx.arc(gx, gy, IR, 0, Math.PI * 2);
    ctx.clip();

    for (let f = 0; f < 48; f++) {
      const a = (f / 48) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(gx + Math.cos(a) * IR * 0.22, gy + Math.sin(a) * IR * 0.22);
      ctx.lineTo(gx + Math.cos(a) * IR * 0.97, gy + Math.sin(a) * IR * 0.97);
      ctx.strokeStyle = f % 3 === 0 ? 'rgba(80,140,100,0.22)' : 'rgba(20,50,30,0.13)';
      ctx.lineWidth = 0.7;
      ctx.stroke();
    }
    [0.28, 0.48, 0.68, 0.85].forEach(function (rr) {
      ctx.beginPath();
      ctx.arc(gx, gy, IR * rr, 0, Math.PI * 2);
      ctx.strokeStyle = 'rgba(15,35,22,0.28)';
      ctx.lineWidth = 1.2;
      ctx.stroke();
    });
    ctx.restore();

    /* Limbal ring */
    const lim = ctx.createRadialGradient(gx, gy, IR * 0.82, gx, gy, IR * 1.02);
    lim.addColorStop(0, 'rgba(0,0,0,0)');
    lim.addColorStop(1, 'rgba(0,0,0,0.55)');
    ctx.beginPath();
    ctx.arc(gx, gy, IR, 0, Math.PI * 2);
    ctx.fillStyle = lim;
    ctx.fill();

    /* ── PUPIL ───────────────────────────────────────────────────────────── */
    const PR = IR * 0.38;
    const pg = ctx.createRadialGradient(gx, gy, 0, gx, gy, PR);
    pg.addColorStop(0, '#000000');
    pg.addColorStop(1, '#070707');
    ctx.beginPath();
    ctx.arc(gx, gy, PR, 0, Math.PI * 2);
    ctx.fillStyle = pg;
    ctx.fill();

    /* ── CORNEAL GLOSS ───────────────────────────────────────────────────── */
    const gg = ctx.createRadialGradient(gx - IR * 0.1, gy - IR * 0.35, 0, gx, gy, IR * 1.1);
    gg.addColorStop(0, 'rgba(255,255,255,0.10)');
    gg.addColorStop(0.4, 'rgba(255,255,255,0.03)');
    gg.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.beginPath();
    ctx.arc(gx, gy, IR * 1.05, 0, Math.PI * 2);
    ctx.fillStyle = gg;
    ctx.fill();

    /* ── CATCHLIGHTS ─────────────────────────────────────────────────────── */
    const cl1x = gx + IR * 0.28;
    const cl1y = gy - IR * 0.32;
    const cl1g = ctx.createRadialGradient(cl1x, cl1y, 0, cl1x, cl1y, IR * 0.18);
    cl1g.addColorStop(0, 'rgba(255,255,255,0.92)');
    cl1g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.beginPath();
    ctx.arc(cl1x, cl1y, IR * 0.18, 0, Math.PI * 2);
    ctx.fillStyle = cl1g;
    ctx.fill();

    const cl2x = gx - IR * 0.22;
    const cl2y = gy - IR * 0.2;
    const cl2g = ctx.createRadialGradient(cl2x, cl2y, 0, cl2x, cl2y, IR * 0.07);
    cl2g.addColorStop(0, 'rgba(255,255,255,0.6)');
    cl2g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.beginPath();
    ctx.arc(cl2x, cl2y, IR * 0.07, 0, Math.PI * 2);
    ctx.fillStyle = cl2g;
    ctx.fill();

    ctx.restore(); /* end sclera clip */

    /* ── EYELIDS ─────────────────────────────────────────────────────────── */
    const lidCol = '#F5F0E8'; /* matches page background */

    ctx.beginPath();
    ctx.moveTo(cx - EW, cy);
    ctx.quadraticCurveTo(cx, cy - EH * 1.18, cx + EW, cy);
    ctx.lineTo(cx + EW, cy - EH * 2);
    ctx.lineTo(cx - EW, cy - EH * 2);
    ctx.closePath();
    ctx.fillStyle = lidCol;
    ctx.fill();

    ctx.beginPath();
    ctx.moveTo(cx - EW, cy);
    ctx.quadraticCurveTo(cx, cy + EH * 0.92, cx + EW, cy);
    ctx.lineTo(cx + EW, cy + EH * 2);
    ctx.lineTo(cx - EW, cy + EH * 2);
    ctx.closePath();
    ctx.fillStyle = lidCol;
    ctx.fill();

    ctx.beginPath();
    ctx.moveTo(cx - EW, cy);
    ctx.quadraticCurveTo(cx, cy - EH * 1.18, cx + EW, cy);
    ctx.strokeStyle = '#1A1A1A';
    ctx.lineWidth = 2.5;
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(cx - EW, cy);
    ctx.quadraticCurveTo(cx, cy + EH * 0.92, cx + EW, cy);
    ctx.strokeStyle = '#2A2A2A';
    ctx.lineWidth = 1.8;
    ctx.stroke();

    /* ── ORBIT RINGS ─────────────────────────────────────────────────────── */
    const ringAngle = frame * 0.004;
    const ringTilt = Math.sin(frame * 0.006) * 0.18;
    const ringR = R * 1.38;

    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(ringAngle);
    ctx.beginPath();
    ctx.ellipse(0, 0, ringR, ringR * (0.22 + Math.abs(Math.sin(ringTilt)) * 0.12), ringTilt, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(201,169,110,0.55)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.rotate(-ringAngle * 2.3);
    ctx.beginPath();
    ctx.ellipse(0, 0, ringR * 0.88, ringR * 0.88 * (0.18 + Math.abs(Math.sin(-ringTilt)) * 0.1), -ringTilt * 0.7, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(201,169,110,0.25)';
    ctx.lineWidth = 0.8;
    ctx.stroke();
    ctx.restore();

    /* ── PARTICLES ───────────────────────────────────────────────────────── */
    parts.forEach(function (p) {
      p.y -= p.speed;
      if (p.y < -0.05) p.y = 1.05;
      ctx.beginPath();
      ctx.arc(p.x * W, p.y * H, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(201,169,110,' + p.opacity + ')';
      ctx.fill();
    });
  }

  function initialize() {
    canvas = document.getElementById(canvasId);
    if (!canvas) {
      console.warn(`Canvas with id "${canvasId}" not found`);
      return;
    }
    ctx = canvas.getContext('2d');
    initializeParticles();
    resize();
    draw();
  }

  function cleanup() {
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
    }
    document.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('resize', resize);
  }

  onMounted(() => {
    initialize();
    document.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('resize', resize);
  });

  onUnmounted(() => {
    cleanup();
  });

  return {
    initialize,
    cleanup,
  };
}
