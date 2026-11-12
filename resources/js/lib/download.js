function save(href, filename) {
    const link = document.createElement('a');
    link.href = href;
    link.download = filename;
    link.click();
}

export function downloadSvg(svg, filename) {
    const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    save(url, filename);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export async function downloadPng(svg, size, filename, background) {
    const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);

    try {
        const image = new Image();
        image.src = url;
        await image.decode();
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        context.fillStyle = background;
        context.fillRect(0, 0, size, size);
        context.drawImage(image, 0, 0, size, size);
        save(canvas.toDataURL('image/png'), filename);
    } finally {
        URL.revokeObjectURL(url);
    }
}
