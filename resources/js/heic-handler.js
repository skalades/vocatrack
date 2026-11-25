/**
 * Modular client-side HEIC/HEIF image handler.
 *
 * 1. Provides lazy-loaded HEIC -> JPEG conversion for file inputs before upload.
 * 2. Automatically intercepts <img> load failures for same-origin HEIC/HEIF images
 *    (e.g. Chrome/Edge/Firefox which lack native HEIC support) and renders them as JPEG.
 */

const HEIC_MIME_TYPES = new Set([
    'image/heic',
    'image/heif',
    'image/heic-sequence',
    'image/heif-sequence',
    'application/x-heic',
]);

const HEIC_FTYP_BRANDS = new Set([
    'heic', 'heix', 'hevc', 'hevx',
    'heim', 'heis', 'hevm', 'hevs',
    'mif1', 'msf1',
]);

const convertedUrlCache = new Map();
const inFlightConversions = new Map();
const failedUrlCache = new Set();

let heicConverterLoader = null;

function loadHeic2Any() {
    if (!heicConverterLoader) {
        heicConverterLoader = import('heic2any').then((mod) => mod.default || mod);
    }
    return heicConverterLoader;
}

export function isHeicMimeOrExt(mime = '', urlOrName = '') {
    const normalizedMime = String(mime).toLowerCase().split(';')[0].trim();
    if (HEIC_MIME_TYPES.has(normalizedMime)) {
        return true;
    }

    const cleanPath = String(urlOrName).split('?')[0].split('#')[0].toLowerCase();
    return cleanPath.endsWith('.heic') || cleanPath.endsWith('.heif');
}

export async function hasHeicMagicBytes(blob) {
    if (!blob || typeof blob.slice !== 'function' || blob.size < 12) {
        return false;
    }

    try {
        const buffer = await blob.slice(0, 16).arrayBuffer();
        const bytes = new Uint8Array(buffer);
        const ftyp = String.fromCharCode(bytes[4], bytes[5], bytes[6], bytes[7]);
        if (ftyp !== 'ftyp') {
            return false;
        }
        const brand = String.fromCharCode(bytes[8], bytes[9], bytes[10], bytes[11]).toLowerCase();
        return HEIC_FTYP_BRANDS.has(brand);
    } catch {
        return false;
    }
}

export async function isHeicBlob(blob, contentType = '', urlOrName = '') {
    if (isHeicMimeOrExt(contentType, urlOrName)) {
        return true;
    }
    return hasHeicMagicBytes(blob);
}

export async function convertHeicBlobToJpeg(blob, quality = 0.85) {
    const heic2any = await loadHeic2Any();
    const result = await heic2any({
        blob,
        toType: 'image/jpeg',
        quality,
    });
    return Array.isArray(result) ? result[0] : result;
}

function normalizeSameOriginUrl(rawSrc) {
    if (!rawSrc || rawSrc.startsWith('data:') || rawSrc.startsWith('blob:')) {
        return null;
    }

    try {
        const parsed = new URL(rawSrc, window.location.origin);
        if (parsed.origin !== window.location.origin) {
            return null;
        }
        return parsed.href;
    } catch {
        return null;
    }
}

export function getConvertedImageUrl(rawSrc) {
    const normalized = normalizeSameOriginUrl(rawSrc);
    if (!normalized) {
        return null;
    }
    return convertedUrlCache.get(normalized) || null;
}

export async function resolveHeicImageUrl(rawSrc) {
    const url = normalizeSameOriginUrl(rawSrc);
    if (!url) {
        return null;
    }

    if (convertedUrlCache.has(url)) {
        return convertedUrlCache.get(url);
    }
    if (failedUrlCache.has(url)) {
        return null;
    }
    if (inFlightConversions.has(url)) {
        return inFlightConversions.get(url);
    }

    const task = (async () => {
        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            if (!response.ok) {
                failedUrlCache.add(url);
                return null;
            }

            const contentType = response.headers.get('content-type') || '';
            const blob = await response.blob();
            if (!(await isHeicBlob(blob, contentType, url))) {
                failedUrlCache.add(url);
                return null;
            }

            const jpegBlob = await convertHeicBlobToJpeg(blob, 0.85);
            const objectUrl = URL.createObjectURL(jpegBlob);
            convertedUrlCache.set(url, objectUrl);
            return objectUrl;
        } catch (err) {
            console.warn('HEIC fallback decode failed:', err);
            failedUrlCache.add(url);
            return null;
        } finally {
            inFlightConversions.delete(url);
        }
    })();

    inFlightConversions.set(url, task);
    return task;
}

async function handleBrokenImage(img) {
    if (!(img instanceof HTMLImageElement)) {
        return;
    }

    if (img.dataset.heicState) {
        return;
    }

    const rawSrc = img.getAttribute('src') || img.currentSrc;
    const normalized = normalizeSameOriginUrl(rawSrc);
    if (!normalized) {
        return;
    }

    img.dataset.heicState = 'processing';
    img.classList.add('animate-pulse', 'opacity-60');

    const convertedUrl = await resolveHeicImageUrl(normalized);

    img.classList.remove('animate-pulse', 'opacity-60');

    if (convertedUrl) {
        img.dataset.heicOriginalSrc = rawSrc;
        img.dataset.heicState = 'done';
        img.src = convertedUrl;

        if (img.id === 'lightbox-image') {
            const downloadLink = document.getElementById('lightbox-download-link');
            if (downloadLink) {
                downloadLink.href = convertedUrl;
            }
        }
    } else {
        img.dataset.heicState = 'failed';
    }
}

function scanBrokenImages() {
    document.querySelectorAll('img').forEach((img) => {
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src') && !img.dataset.heicState) {
            handleBrokenImage(img);
        }
    });
}

export function initHeicSupport() {
    if (window.__vtHeicInitialized) {
        return;
    }
    window.__vtHeicInitialized = true;

    window.vtIsHeicFile = (file) => isHeicBlob(file, file?.type || '', file?.name || '');
    window.vtConvertHeicBlob = convertHeicBlobToJpeg;
    window.vtGetConvertedImageUrl = getConvertedImageUrl;
    window.vtResolveHeicImageUrl = resolveHeicImageUrl;

    document.addEventListener('error', (event) => {
        if (event.target instanceof HTMLImageElement) {
            handleBrokenImage(event.target);
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scanBrokenImages, { once: true });
    } else {
        scanBrokenImages();
    }
}
