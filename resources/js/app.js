// Broken images (deleted file, network error) fall back to the brand placeholder instead of a broken icon.
document.addEventListener(
    'error',
    (event) => {
        const img = event.target;
        if (!(img instanceof HTMLImageElement) || !img.dataset.fallback || img.src.endsWith(img.dataset.fallback)) {
            return;
        }
        img.removeAttribute('srcset');
        img.src = img.dataset.fallback;
    },
    true,
);
