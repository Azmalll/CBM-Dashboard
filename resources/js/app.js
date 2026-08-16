import { upload } from '@vercel/blob/client';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('odx-import-form');

    if (!form) {
        return;
    }

    const fileInput = document.getElementById('odx_file');
    const button = document.getElementById('odx-import-button');
    const status = document.getElementById('odx-status');
    const progress = document.getElementById('odx-progress');

    const uploadUrl = form.dataset.uploadUrl;
    const importUrl = form.dataset.importUrl;

    if (
        !fileInput ||
        !button ||
        !status ||
        !progress ||
        !uploadUrl ||
        !importUrl
    ) {
        console.error('ODX import configuration is incomplete.');
        return;
    }

    function showStatus(message, type = 'info') {
        status.textContent = message;

        status.className =
            'mb-6 px-5 py-4 rounded-xl border';

        if (type === 'success') {
            status.classList.add(
                'bg-green-100',
                'border-green-300',
                'text-green-800'
            );
        } else if (type === 'error') {
            status.classList.add(
                'bg-red-100',
                'border-red-300',
                'text-red-800'
            );
        } else {
            status.classList.add(
                'bg-blue-100',
                'border-blue-300',
                'text-blue-800'
            );
        }

        status.classList.remove('hidden');
    }

    function setProgress(percent) {
        progress.textContent =
            `Upload ODX: ${Math.round(percent)}%`;

        progress.classList.remove('hidden');
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const file = fileInput.files?.[0];

        if (!file) {
            showStatus(
                'Silakan pilih file ODX.',
                'error'
            );
            return;
        }

        if (!file.name.toLowerCase().endsWith('.odx')) {
            showStatus(
                'File harus berekstensi .odx.',
                'error'
            );
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            showStatus(
                'Ukuran ODX maksimal 50 MB.',
                'error'
            );
            return;
        }

        button.disabled = true;
        button.textContent = 'Uploading...';
        progress.classList.add('hidden');

        try {

            /*
             * Buat pathname sendiri agar Laravel dapat
             * mengenali bahwa file berasal dari area ODX temporary.
             */
            const safeName =
                file.name
                    .replace(/[^a-zA-Z0-9._-]/g, '-')
                    .replace(/-+/g, '-');

            const pathname =
                `odx-temp/${crypto.randomUUID()}-${safeName}`;

            /*
             * Browser -> Vercel Blob.
             *
             * File besar tidak melewati Laravel.
             */
            const blob = await upload(
                pathname,
                file,
                {
                    access: 'private',
                    handleUploadUrl: uploadUrl,
                    multipart: true,

                    clientPayload: JSON.stringify({
                        type: 'odx'
                    }),

                    onUploadProgress: ({ percentage }) => {
                        setProgress(percentage);
                    },
                }
            );

            showStatus(
                'Upload ODX selesai. Sedang memproses data...',
                'info'
            );

            button.textContent = 'Memproses...';

            const csrfToken =
                document.querySelector(
                    'meta[name="csrf-token"]'
                )?.getAttribute('content') || '';

            const importResponse = await fetch(
                importUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,

                        Accept:
                            'application/json',
                    },

                    body: JSON.stringify({
                        pathname: blob.pathname
                    }),
                }
            );

            const importData =
                await importResponse
                    .json()
                    .catch(() => ({}));

            if (!importResponse.ok) {
                throw new Error(
                    importData.message ||
                    'Import ODX gagal.'
                );
            }

            showStatus(
                importData.message ||
                'Import ODX berhasil.',
                'success'
            );

            fileInput.value = '';
            progress.classList.add('hidden');

            setTimeout(() => {
                window.location.reload();
            }, 1200);

        } catch (error) {

            console.error(
                'ODX import error:',
                error
            );

            showStatus(
                error instanceof Error
                    ? error.message
                    : 'Import ODX gagal.',
                'error'
            );

            button.textContent =
                'Import ODX';

            button.disabled = false;
        }
    });
});