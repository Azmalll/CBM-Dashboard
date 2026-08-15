<?php

namespace App\Http\Controllers;

use App\Services\OdxImportService;
use App\Services\VercelBlobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OdxImportController extends Controller
{
    public function create()
    {
        return view('odx-import.create');
    }

    /**
     * Generate a short-lived client token.
     *
     * File ODX tidak dikirim ke endpoint ini.
     * Browser hanya menerima token terbatas.
     */
    public function clientToken(
        Request $request,
        VercelBlobService $blob
    ) {
        $validated = $request->validate([
            'filename' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $filename = pathinfo(
            $validated['filename'],
            PATHINFO_FILENAME
        );

        $pathname =
            'odx-temp/' .
            now()->format('Y/m/d') .
            '/' .
            Str::uuid() .
            '-' .
            Str::slug($filename) .
            '.odx';

        $token = $blob->generateClientToken(
            $pathname,
            50 * 1024 * 1024
        );

        return response()->json([
            'token' => $token,
            'pathname' => $pathname,
        ]);
    }

    /**
     * Process ODX yang sudah lebih dulu di-upload
     * langsung ke Vercel Blob.
     */
    public function store(
        Request $request,
        OdxImportService $odxImportService,
        VercelBlobService $blob
    ) {
        $validated = $request->validate([
            'pathname' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $pathname = $validated['pathname'];

        abort_unless(
            str_starts_with($pathname, 'odx-temp/'),
            422,
            'Path ODX tidak valid.'
        );

        $temporaryPath = null;

        try {
            /*
             * Download ODX dari private Blob.
             */
            $response = $blob->get($pathname);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(
                    'ODX tidak ditemukan di Vercel Blob.'
                );
            }

            /*
             * Buat file temporary di runtime Laravel.
             *
             * Ini hanya temporary selama proses import.
             * Bukan storage permanen.
             */
            $temporaryPath = tempnam(
                sys_get_temp_dir(),
                'odx_'
            );

            if (!$temporaryPath) {
                throw new \RuntimeException(
                    'Tidak dapat membuat temporary file.'
                );
            }

            $handle = fopen(
                $temporaryPath,
                'wb'
            );

            if (!$handle) {
                throw new \RuntimeException(
                    'Tidak dapat membuka temporary file.'
                );
            }

            $body = $response->getBody();

            while (!$body->eof()) {
                fwrite(
                    $handle,
                    $body->read(1024 * 1024)
                );
            }

            fclose($handle);

            /*
             * PENTING:
             *
             * Parser/dashboard lama tetap digunakan.
             * Kita hanya mengganti sumber file:
             *
             * Blob → temporary path → parser.
             */
            $result = $odxImportService->import(
                $temporaryPath
            );

            /*
             * ODX temporary tidak perlu disimpan setelah
             * proses import selesai.
             */
            $blob->delete($pathname);

            @unlink($temporaryPath);
            $temporaryPath = null;

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'total' => $result['total'],
            ]);
        } catch (Throwable $e) {

            /*
             * Bersihkan temporary file jika ada.
             */
            if (
                $temporaryPath &&
                is_file($temporaryPath)
            ) {
                @unlink($temporaryPath);
            }

            /*
             * Kalau import gagal, ODX temporary juga
             * kita hapus supaya Blob tidak menumpuk.
             */
            try {
                $blob->delete($pathname);
            } catch (Throwable) {
                // Jangan override error utama.
            }

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}