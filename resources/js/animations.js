/**
 * VokaTrack Interactive Animation & Micro-Interactions Engine
 * Lightweight, GPU-accelerated, zero-dependency, modular.
 */

// =============================================================================
// 1. STAT COUNTERS (requestAnimationFrame with easeOutQuart)
// =============================================================================
export function initStatCounters() {
    const counterElements = document.querySelectorAll('.stat-counter, [data-counter]');

    if (!counterElements.length) return;

    const easeOutQuart = (t) => 1 - Math.pow(1 - t, 4);

    const animateCounter = (el) => {
        if (el.dataset.hasAnimated === 'true') return;

        const rawText = el.dataset.counter || el.dataset.target || el.textContent.trim();
        // Extract numeric part (supports decimals, e.g. "98.5%")
        const match = rawText.match(/([0-9]+(?:[\.,][0-9]+)?)/);
        if (!match) return;

        const targetNumber = parseFloat(match[1].replace(',', '.'));
        if (isNaN(targetNumber)) return;

        el.dataset.hasAnimated = 'true';
        const prefix = rawText.slice(0, match.index);
        const suffix = rawText.slice(match.index + match[0].length);
        const isDecimal = match[1].includes('.') || match[1].includes(',');
        const duration = 850; // ms
        const startTime = performance.now();

        const update = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easeOutQuart(progress);
            const current = targetNumber * easedProgress;

            let formatted = isDecimal ? current.toFixed(1) : Math.round(current).toString();
            if (rawText.includes(',')) formatted = formatted.replace('.', ',');

            el.textContent = `${prefix}${formatted}${suffix}`;

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                // Restore exact final text
                el.textContent = rawText;
            }
        };

        requestAnimationFrame(update);
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        counterElements.forEach(el => observer.observe(el));
    } else {
        counterElements.forEach(el => animateCounter(el));
    }
}

// =============================================================================
// 2. DISMISSIBLE & AUTO-FADING FLASH ALERTS
// =============================================================================
export function initFlashAlerts() {
    const alerts = document.querySelectorAll('#flash-success, #flash-error, .flash-alert');

    alerts.forEach(alert => {
        // Add entry slide animation class
        alert.classList.add('animate-slide-down');

        // Close button interceptor for smooth slide out
        const closeBtn = alert.querySelector('button');
        if (closeBtn) {
            closeBtn.onclick = (e) => {
                e.preventDefault();
                dismissAlert(alert);
            };
        }

        // Auto-dismiss after 6 seconds
        setTimeout(() => {
            if (document.body.contains(alert)) {
                dismissAlert(alert);
            }
        }, 6000);
    });

    function dismissAlert(el) {
        el.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-12px) scale(0.98)';
        el.style.maxHeight = el.scrollHeight + 'px';

        setTimeout(() => {
            el.style.maxHeight = '0px';
            el.style.marginTop = '0px';
            el.style.marginBottom = '0px';
            el.style.paddingTop = '0px';
            el.style.paddingBottom = '0px';
            el.style.borderWidth = '0px';
        }, 120);

        setTimeout(() => {
            el.remove();
        }, 360);
    }
}

// =============================================================================
// 3. TOAST NOTIFICATION UTILITY (Floating Modern Glassmorphic Toasts)
// =============================================================================
export function showToast(message, type = 'success', duration = 3500) {
    let container = document.getElementById('vt-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'vt-toast-container';
        container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 pointer-events-none max-w-sm w-full px-3 sm:px-0';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-white/95 backdrop-blur-md border shadow-xl text-xs font-semibold animate-slide-down transition-all duration-300';

    let iconSvg = '';
    let borderClass = 'border-slate-200';
    let textColor = 'text-slate-800';

    if (type === 'success') {
        borderClass = 'border-emerald-200 bg-emerald-50/95 text-emerald-950 shadow-emerald-500/10';
        iconSvg = `<span class="w-6 h-6 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>`;
    } else if (type === 'error') {
        borderClass = 'border-rose-200 bg-rose-50/95 text-rose-950 shadow-rose-500/10';
        iconSvg = `<span class="w-6 h-6 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0"><svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>`;
    } else if (type === 'warning') {
        borderClass = 'border-amber-200 bg-amber-50/95 text-amber-950 shadow-amber-500/10';
        iconSvg = `<span class="w-6 h-6 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-bold text-xs">!</span>`;
    } else {
        borderClass = 'border-indigo-200 bg-indigo-50/95 text-indigo-950 shadow-indigo-500/10';
        iconSvg = `<span class="w-6 h-6 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0"><svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>`;
    }

    toast.classList.add(...borderClass.split(' '));
    toast.innerHTML = `
        <div class="flex items-center gap-2.5 min-w-0">
            ${iconSvg}
            <span class="truncate">${message}</span>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-black/5 transition-colors cursor-pointer shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    `;

    const closeBtn = toast.querySelector('button');
    closeBtn.onclick = () => removeToast(toast);

    container.appendChild(toast);

    setTimeout(() => {
        if (document.body.contains(toast)) {
            removeToast(toast);
        }
    }, duration);

    function removeToast(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px) scale(0.95)';
        setTimeout(() => el.remove(), 250);
    }
}

// Make showToast globally available
window.showToast = showToast;

// =============================================================================
// 4. CAMERA SHUTTER SNAPSHOT FLASH EFFECT
// =============================================================================
export function triggerCameraFlash(containerId = 'camera-viewport') {
    const container = document.getElementById(containerId) || document.getElementById('camera-overlay')?.parentElement;
    if (!container) return;

    let flash = container.querySelector('.camera-shutter-flash');
    if (!flash) {
        flash = document.createElement('div');
        flash.className = 'camera-shutter-flash absolute inset-0 bg-white pointer-events-none z-30 transition-opacity duration-150 opacity-0';
        container.appendChild(flash);
    }

    requestAnimationFrame(() => {
        flash.style.opacity = '0.9';
        setTimeout(() => {
            flash.style.opacity = '0';
        }, 120);
    });
}
window.triggerCameraFlash = triggerCameraFlash;

// =============================================================================
// 5. SMOOTH MODAL TRANSITIONS (Helper)
// =============================================================================
export function openAnimatedModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.classList.remove('animate-fade-out');
    modal.classList.add('animate-fade-in');

    const dialog = modal.querySelector('.bg-white, [role="dialog"]');
    if (dialog) {
        dialog.classList.remove('animate-scale-out');
        dialog.classList.add('animate-scale-in');
    }

    document.body.style.overflow = 'hidden';
}

export function closeAnimatedModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('animate-fade-in');
    modal.classList.add('animate-fade-out');

    const dialog = modal.querySelector('.bg-white, [role="dialog"]');
    if (dialog) {
        dialog.classList.remove('animate-scale-in');
        dialog.classList.add('animate-scale-out');
    }

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex', 'animate-fade-out');
        if (dialog) dialog.classList.remove('animate-scale-out');
        document.body.style.overflow = '';
    }, 180);
}

window.openAnimatedModal = openAnimatedModal;
window.closeAnimatedModal = closeAnimatedModal;

// =============================================================================
// 6. TACTILE BUTTON PRESS & RIPPLE FEEDBACK
// =============================================================================
export function initTactileButtons() {
    document.addEventListener('pointerdown', (e) => {
        const btn = e.target.closest('button, .btn-interactive, a[role="button"]');
        if (!btn || btn.disabled || btn.classList.contains('pointer-events-none')) return;

        btn.classList.add('active-press');
    });

    document.addEventListener('pointerup', (e) => {
        const btn = e.target.closest('button, .btn-interactive, a[role="button"]');
        if (btn) {
            btn.classList.remove('active-press');
        }
    });
}

// =============================================================================
// AUTO-INITIALIZATION ON DOM LOAD
// =============================================================================
document.addEventListener('DOMContentLoaded', () => {
    initStatCounters();
    initFlashAlerts();
    initTactileButtons();
});

// Re-run counters on Livewire navigation if Livewire is present
if (window.Livewire) {
    document.addEventListener('livewire:navigated', () => {
        initStatCounters();
        initFlashAlerts();
    });
}
