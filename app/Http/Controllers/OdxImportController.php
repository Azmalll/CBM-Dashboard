<?php

namespace App\Http\Controllers;

use App\Services\OdxImportService;
use App\Services\VercelBlobService;
use Illuminate\Http\Request;
use Throwable;

class OdxImportController extends Controller
{
    public function create()
    {
        return view('odx-import.create');
    }

    /**
     * Process ODX yang sudah lebih dulu
     * di-upload langsung ke Vercel Blob.
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

        $pathname =
            $validated['pathname'];

        abort_unless(
            str_starts_with(
                $pathname,
                'odx-temp/'
            ),
            422,
            'Path ODX tidak valid.'
        );

        if (
            !str_ends_with(
                strtolower($pathname),
                '.odx'
            )
        ) {
            abort(
                422,
                'File yang diimport harus ODX.'
            );
        }

        $temporaryPath = null;

        try {

            /*
             * Download ODX dari private Blob.
             */
            $response =
                $blob->get($pathname);

            if (
                $response->getStatusCode() !== 200
            ) {
                throw new \RuntimeException(
                    'ODX tidak ditemukan di Vercel Blob.'
                );
            }

            /*
             * Temporary file untuk parser.
             */
            $temporaryPath =
                tempnam(
                    sys_get_temp_dir(),
                    'odx_'
                );

            if (!$temporaryPath) {
                throw new \RuntimeException(
                    'Tidak dapat membuat temporary file.'
                );
            }

            $handle =
                fopen(
                    $temporaryPath,
                    'wb'
                );

            if (!$handle) {
                throw new \RuntimeException(
                    'Tidak dapat membuka temporary file.'
                );
            }

            $body =
                $response->getBody();

            while (!$body->eof()) {

                fwrite(
                    $handle,
                    $body->read(
                        1024 * 1024
                    )
                );
            }

            fclose($handle);

            /*
             * Blob -> temporary path -> parser.
             */
            $result =
                $odxImportService->import(
                    $temporaryPath
                );

            /*
             * Hapus ODX temporary dari Blob.
             */
            $blob->delete(
                $pathname
            );

            /*
             * Hapus temporary local.
             */
            @unlink(
                $temporaryPath
            );

            $temporaryPath = null;

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    $result['message'],

                'imported' =>
                    $result['imported'],

                'updated' =>
                    $result['updated'],

                'total' =>
                    $result['total'],
            ]);

        } catch (Throwable $e) {

            /*
             * Bersihkan temporary file.
             */
            if (
                $temporaryPath &&
                is_file($temporaryPath)
            ) {
                @unlink(
                    $temporaryPath
                );
            }

            /*
             * Bersihkan temporary Blob.
             */
            try {

                $blob->delete(
                    $pathname
                );

            } catch (Throwable) {

                // Jangan override error utama.
            }

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}