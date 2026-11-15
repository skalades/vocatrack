/**
 * VokaTrack Motion Layer — animasi interaktif untuk level Guru & Siswa.
 * Aktif bila <body data-app="teacher|student|admin|industry|school|principal">. Menghormati prefers-reduced-motion.
 *
 * Atribut yang dikenali:
 *   data-reveal            elemen muncul (fade + naik) saat masuk viewport
 *   data-reveal-stagger    anak-anak langsung elemen ini muncul bergiliran
 *   data-grow-x            bar progres tumbuh dari kiri
 *   data-grow-y            bar grafik tumbuh dari bawah
 *   data-ring              lingkaran SVG progres "digambar"
 *   data-counter           angka menghitung naik (sudah ditangani animations.js)
 *   data-tilt              kartu miring halus mengikuti kursor
 *   data-spotlight         cahaya lembut mengikuti kursor
 *   data-ripple            efek riak saat ditekan
 */

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

function onVisible(elements, callback, options = {}) {
    if (!('IntersectionObserver' in window)) {
        elements.forEach((el) => callback(el));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                callback(entry.target);
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px', ...options });
    elements.forEach((el) => io.observe(el));
}

// -----------------------------------------------------------------------------
// 1. Reveal on scroll (+ deteksi kartu otomatis untuk halaman tanpa atribut)
// -----------------------------------------------------------------------------
function collectAutoCards(app) {
    const main = document.querySelector('main');
    if (!main) return [];
    const selector = app === 'student'
        ? '[class*="rounded-[2"], [class*="rounded-[3"], .rounded-3xl, .rounded-2xl'
        : '.rounded-3xl, .rounded-2xl, [class*="rounded-[2"]';
    const skip = '.fixed, nav, header, [id*="modal"], [id*="drawer"], [data-reveal], [data-reveal-stagger], [data-no-reveal]';
    const candidates = [...main.querySelectorAll(selector)].filter((el) => {
        if (el.closest(skip)) return false;
        const cs = getComputedStyle(el);
        if (cs.position === 'absolute' || cs.position === 'fixed' || cs.display === 'none') return false;
        return el.offsetHeight > 40;
    });
    // ambil kartu terluar saja
    return candidates.filter((el) => !candidates.some((other) => other !== el && other.contains(el)));
}

export function initReveal(app) {
    const groups = [...document.querySelectorAll('[data-reveal-stagger]')];
    const singles = [...document.querySelectorAll('[data-reveal]')];
    const autos = collectAutoCards(app);

    const targets = [];
    groups.forEach((group) => {
        [...group.children].forEach((child, i) => {
            if (child.matches('script, template, style')) return;
            child.style.setProperty('--vt-delay', `${Math.min(i, 8) * 70}ms`);
            targets.push(child);
        });
    });
    singles.forEach((el) => targets.push(el));

    // kartu otomatis: beri jeda berdasarkan urutan baris layar
    autos.forEach((el, i) => {
        el.style.setProperty('--vt-delay', `${Math.min(i, 6) * 60}ms`);
        targets.push(el);
    });

    targets.forEach((el) => el.classList.add('vt-reveal'));
    // paksa reflow agar transisi berjalan
    void document.body.offsetHeight;
    onVisible(targets, (el) => {
        el.classList.add('vt-in');
        // lepas kelas setelah selesai agar tidak bentrok dengan hover/tilt
        const done = (ev) => {
            if (ev.target !== el || ev.propertyName !== 'transform') return;
            el.classList.remove('vt-reveal', 'vt-in');
            el.style.removeProperty('--vt-delay');
            el.removeEventListener('transitionend', done);
        };
        el.addEventListener('transitionend', done);
    });
}

// -----------------------------------------------------------------------------
// 2. Bar progres & grafik tumbuh
// -----------------------------------------------------------------------------
export function initGrowth() {
    const bars = [...document.querySelectorAll('[data-grow-x], [data-grow-y]')];
    bars.forEach((el) => el.classList.add(el.hasAttribute('data-grow-y') ? 'vt-grow-y' : 'vt-grow-x'));
    bars.forEach((el, i) => {
        if (el.hasAttribute('data-grow-y')) el.style.setProperty('--vt-delay', `${(i % 7) * 70}ms`);
    });
    void document.body.offsetHeight;
    onVisible(bars, (el) => el.classList.add('vt-grown'), { rootMargin: '0px' });
}

// -----------------------------------------------------------------------------
// 3. Ring SVG digambar
// -----------------------------------------------------------------------------
export function initRings() {
    document.querySelectorAll('[data-ring]').forEach((circle) => {
        const dashArray = (circle.getAttribute('stroke-dasharray') || '').trim().split(/[\s,]+/);
        const hasOffset = circle.hasAttribute('stroke-dashoffset');
        circle.style.transition = 'none';

        if (hasOffset) {
            const total = parseFloat(dashArray[0]);
            const target = circle.getAttribute('stroke-dashoffset');
            circle.style.strokeDashoffset = total;
            onVisible([circle], () => {
                requestAnimationFrame(() => {
                    circle.style.transition = 'stroke-dashoffset 1.1s cubic-bezier(0.16, 1, 0.3, 1) 0.15s';
                    circle.style.strokeDashoffset = target;
                });
            });
        } else if (dashArray.length === 2) {
            const [filled, total] = dashArray;
            circle.style.strokeDasharray = `0 ${total}`;
            onVisible([circle], () => {
                requestAnimationFrame(() => {
                    circle.style.transition = 'stroke-dasharray 1.1s cubic-bezier(0.16, 1, 0.3, 1) 0.15s';
                    circle.style.strokeDasharray = `${filled} ${total}`;
                });
            });
        }
    });
}

// -----------------------------------------------------------------------------
// 4. Tilt & spotlight (hanya perangkat dengan mouse)
// -----------------------------------------------------------------------------
export function initPointerEffects() {
    if (!finePointer()) return;

    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.classList.add('vt-tilt');
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            card.style.setProperty('--vt-rx', `${(-y * 5).toFixed(2)}deg`);
            card.style.setProperty('--vt-ry', `${(x * 6).toFixed(2)}deg`);
        });
        card.addEventListener('pointerleave', () => {
            card.style.setProperty('--vt-rx', '0deg');
            card.style.setProperty('--vt-ry', '0deg');
        });
    });

    document.querySelectorAll('[data-spotlight]').forEach((el) => {
        el.classList.add('vt-spotlight');
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.style.setProperty('--vt-mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--vt-my', `${e.clientY - r.top}px`);
        });
    });
}

// -----------------------------------------------------------------------------
// 5. Ripple saat ditekan
// -----------------------------------------------------------------------------
export function initRipple() {
    document.addEventListener('pointerdown', (e) => {
        const host = e.target.closest('[data-ripple]');
        if (!host) return;
        const r = host.getBoundingClientRect();
        const size = Math.max(r.width, r.height) * 1.6;
        const dot = document.createElement('span');
        dot.className = 'vt-ripple-dot';
        dot.style.width = dot.style.height = `${size}px`;
        dot.style.left = `${e.clientX - r.left - size / 2}px`;
        dot.style.top = `${e.clientY - r.top - size / 2}px`;
        host.classList.add('vt-ripple-host');
        host.appendChild(dot);
        dot.addEventListener('animationend', () => dot.remove());
    });
}

// -----------------------------------------------------------------------------
// 6. Transisi antar halaman (fade keluar sebelum pindah)
// -----------------------------------------------------------------------------
export function initPageTransitions() {
    const main = document.querySelector('main');
    if (!main) return;

    const restore = () => main.classList.remove('vt-leaving');
    window.addEventListener('pageshow', restore);

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-transition')) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        let url;
        try { url = new URL(a.href, location.href); } catch { return; }
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search) return;
        if (/\/(download|pdf|export)(\/|$|\?)/i.test(url.pathname) || /\.(pdf|csv|xlsx|zip)$/i.test(url.pathname)) return;

        main.classList.add('vt-leaving');
        // jaga-jaga bila navigasi tidak terjadi (mis. respons file)
        setTimeout(restore, 1500);
    });
}

// -----------------------------------------------------------------------------
// Init
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const app = document.body.dataset.app;
    if (!['teacher', 'student', 'admin', 'industry', 'school', 'principal'].includes(app)) return;

    document.documentElement.classList.add('vt-motion');
    initRipple();
    if (reduceMotion()) return;

    initReveal(app);
    initGrowth();
    initRings();
    initPointerEffects();
    initPageTransitions();
});
