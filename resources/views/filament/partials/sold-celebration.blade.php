{{--
    "Īpašums pārdots" svinību animācija. Tiek palaista ar pārlūka notikumu
    `pdc-property-sold` (no ātrās darbības "Pārdots" vai īpašuma rediģēšanas
    saglabāšanas ar statusu "Pārdots"). Animācija ir dekoratīva un
    nebloķējoša (pointer-events: none), tāpēc netraucē citiem modāļiem.
--}}
@if (! auth()->user()?->isPhoto())
    <div
        data-pdc-sold-celebration
        x-data="soldCelebration"
        x-on:pdc-property-sold.window="celebrate($event.detail)"
        x-cloak
    >
        <template x-if="open">
            <div class="pdc-sold-overlay" aria-hidden="true">
                <canvas x-ref="canvas" class="pdc-sold-canvas"></canvas>
                <div class="pdc-sold-card">
                    <div class="pdc-sold-emoji">🎉</div>
                    <div class="pdc-sold-title">Īpašums pārdots</div>
                    <div class="pdc-sold-subtitle">Apsveicam! Darījums veiksmīgi reģistrēts.</div>
                </div>
            </div>
        </template>
    </div>

    <style>
        .pdc-sold-overlay {
            position: fixed;
            inset: 0;
            z-index: 2147483646;
            pointer-events: none;
            overflow: hidden;
        }

        .pdc-sold-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .pdc-sold-card {
            position: absolute;
            top: 10vh;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 1.25rem 2rem;
            border-radius: 1rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: pdc-sold-pop 0.45s cubic-bezier(0.22, 1.2, 0.36, 1) both;
        }

        .pdc-sold-emoji {
            font-size: 3rem;
            line-height: 1;
        }

        .pdc-sold-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f7d60;
        }

        .pdc-sold-subtitle {
            font-size: 0.9rem;
            font-weight: 500;
            color: #4b5563;
        }

        .dark .pdc-sold-card {
            background: #111827;
            border-color: #27303a;
        }

        .dark .pdc-sold-title {
            color: #34d399;
        }

        .dark .pdc-sold-subtitle {
            color: #9ca3af;
        }

        @keyframes pdc-sold-pop {
            0% { opacity: 0; transform: translateX(-50%) translateY(-12px) scale(0.9); }
            100% { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .pdc-sold-card { animation: none; }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('soldCelebration', () => ({
                open: false,
                timer: null,
                celebrate() {
                    this.open = true;
                    this.$nextTick(() => this.confetti());
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => { this.open = false; this.timer = null; }, 3400);
                },
                confetti() {
                    const canvas = this.$refs.canvas;
                    if (! canvas) return;
                    const ctx = canvas.getContext('2d');
                    const dpr = window.devicePixelRatio || 1;
                    const W = window.innerWidth;
                    const H = window.innerHeight;
                    canvas.width = W * dpr;
                    canvas.height = H * dpr;
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                    const colors = ['#285854', '#0f7d60', '#f97316', '#facc15', '#ef4444', '#3b82f6', '#a855f7', '#ec4899'];
                    const particles = [];
                    for (let i = 0; i < 160; i++) {
                        particles.push({
                            x: Math.random() * W,
                            y: -20 - Math.random() * H * 0.6,
                            w: 6 + Math.random() * 7,
                            h: 8 + Math.random() * 10,
                            vx: -1.4 + Math.random() * 2.8,
                            vy: 2 + Math.random() * 3.5,
                            rot: Math.random() * Math.PI,
                            vr: -0.12 + Math.random() * 0.24,
                            color: colors[(Math.random() * colors.length) | 0],
                        });
                    }

                    const duration = 3000;
                    const start = performance.now();
                    const draw = (now) => {
                        const t = now - start;
                        ctx.clearRect(0, 0, W, H);
                        ctx.globalAlpha = Math.max(0, 1 - t / duration);
                        for (const p of particles) {
                            p.x += p.vx;
                            p.y += p.vy;
                            p.vy += 0.03;
                            p.rot += p.vr;
                            ctx.save();
                            ctx.translate(p.x, p.y);
                            ctx.rotate(p.rot);
                            ctx.fillStyle = p.color;
                            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                            ctx.restore();
                        }
                        ctx.globalAlpha = 1;
                        if (t < duration && this.open) {
                            requestAnimationFrame(draw);
                        } else {
                            ctx.clearRect(0, 0, W, H);
                        }
                    };
                    requestAnimationFrame(draw);
                },
            }));
        });
    </script>
@endif
