import { put } from '@vercel/blob/client';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('odx-import-form');

    if (!form) {
        return;
    }

    const fileInput =
        document.getElementById('odx_file');

    const button =
        document.getElementById('odx-import-button');

    const status =
        document.getElementById('odx-status');

    const progress =
        document.getElementById('odx-progress');

    const csrf =
        document.querySelector(
            'meta[name="csrf-token"]'
        )?.getAttribute('content');

    if (
        !fileInput ||
        !button ||
        !status ||
        !progress ||
        !csrf
    ) {
        return;
    }

    function showStatus(
        message,
        type = 'info'
    ) {
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

        if (
            !file.name
                .toLowerCase()
                .endsWith('.odx')
        ) {
            showStatus(
                'File harus berekstensi .odx.',
                'error'
            );
            return;
        }

        if (
            file.size >
            50 * 1024 * 1024
        ) {
            showStatus(
                'Ukuran ODX maksimal 50 MB.',
                'error'
            );
            return;
        }

        button.disabled = true;
        button.textContent =
            'Menyiapkan upload...';

        progress.classList.add('hidden');

        try {
            /*
             * 1. Minta client token.
             *
             * Yang dikirim cuma nama file.
             * ODX BESAR belum masuk Laravel.
             */
            const tokenResponse =
                await fetch(
                    '{{ route("odx-import.client-token") }}',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept':
                                'application/json',
                        },
                        body: JSON.stringify({
                            filename: file.name,
                        }),
                    }
                );

            const tokenData =
                await tokenResponse.json();

            if (
                !tokenResponse.ok ||
                !tokenData.token ||
                !tokenData.pathname
            ) {
                throw new Error(
                    tokenData.message ||
                    'Gagal mendapatkan upload token.'
                );
            }

            /*
             * 2. Upload ODX langsung dari browser
             *    ke Vercel Blob.
             *
             * File besar TIDAK melewati Laravel.
             */
            button.textContent =
                'Uploading ODX...';

            const blob =
                await put(
                    tokenData.pathname,
                    file,
                    {
                        access: 'private',
                        token: tokenData.token,
                        multipart: true,
                        contentType:
                            'application/octet-stream',

                        onUploadProgress: ({
                            percentage,
                        }) => {
                            setProgress(
                                percentage
                            );
                        },
                    }
                );

            /*
             * 3. Laravel hanya menerima pathname kecil.
             *
             * Laravel kemudian download temporary,
             * parse ODX, masuk MySQL, lalu menghapus
             * ODX temporary dari Blob.
             */
            button.textContent =
                'Memproses ODX...';

            showStatus(
                'Upload selesai. Sedang memproses data ODX...',
                'info'
            );

            const importResponse =
                await fetch(
                    '{{ route("odx-import.store") }}',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept':
                                'application/json',
                        },
                        body: JSON.stringify({
                            pathname:
                                blob.pathname,
                        }),
                    }
                );

            const importData =
                await importResponse.json();

            if (
                !importResponse.ok ||
                !importData.success
            ) {
                throw new Error(
                    importData.message ||
                    'Import ODX gagal.'
                );
            }

            showStatus(
                `${importData.message} Data baru: ${importData.imported}, diperbarui: ${importData.updated}. Total: ${importData.total}.`,
                'success'
            );

            fileInput.value = '';
            progress.classList.add('hidden');

            button.textContent =
                'Import ODX';

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
        } finally {
            button.disabled = false;
        }
    });
});