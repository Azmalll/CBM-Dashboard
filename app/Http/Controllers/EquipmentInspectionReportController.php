<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspection;
use App\Services\VercelBlobService;
use Illuminate\Http\Request;
use RuntimeException;

class EquipmentInspectionReportController extends Controller
{
    /**
     * Upload / replace Analysis Report.
     *
     * File disimpan di Vercel Blob,
     * bukan di storage lokal Laravel.
     */
    public function upload(
        Request $request,
        EquipmentInspection $equipmentInspection,
        VercelBlobService $blob
    ) {
        $validated = $request->validate([
            'analysis_report' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480',
            ],
        ], [
            'analysis_report.required' =>
                'Silakan pilih file Analysis Report.',
            'analysis_report.file' =>
                'File yang dipilih tidak valid.',
            'analysis_report.mimes' =>
                'Analysis Report harus berupa file PDF.',
            'analysis_report.max' =>
                'Ukuran Analysis Report maksimal 20 MB.',
        ]);

        $oldReport = $equipmentInspection->report_file;

        $file = $validated['analysis_report'];

        $filename = sprintf(
            'equipment-%s-measurement-%s-%s.pdf',
            $equipmentInspection->equipment_id,
            $equipmentInspection->id,
            now()->format('YmdHis')
        );

        $pathname = 'reports/' . $filename;

        try {
            $blob = $blob->upload(
                $pathname,
                $file->getRealPath(),
                'application/pdf'
            );

            $equipmentInspection->update([
                'report_file' => $blob['pathname'],
            ]);

            /*
             * Hapus report lama HANYA kalau report lama
             * sudah merupakan pathname Vercel Blob.
             *
             * Report lokal lama tidak disentuh.
             */
            if (
                $oldReport &&
                str_starts_with($oldReport, 'reports/')
            ) {
                try {
                    $blob->delete($oldReport);
                } catch (RuntimeException) {
                    // Jangan menggagalkan upload baru
                    // hanya karena penghapusan file lama gagal.
                }
            }

            return response()->json([
                'success' => true,
                'message' =>
                    'Analysis Report berhasil di-upload.',
                'reportFile' => $blob['pathname'],
                'reportUrl' => route(
                    'equipment-inspection.analysis-report.show',
                    $equipmentInspection
                ),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display private Analysis Report inline.
     */
    public function show(
        EquipmentInspection $equipmentInspection,
        VercelBlobService $blob
    ) {
        $path = $equipmentInspection->report_file;

        abort_unless(
            $path,
            404,
            'Analysis Report belum tersedia.'
        );

        /*
         * Untuk sementara kita hanya melayani report
         * yang sudah menggunakan Vercel Blob.
         */
        abort_unless(
            str_starts_with($path, 'reports/'),
            404,
            'Analysis Report belum tersedia di Blob storage.'
        );

        try {
            $response = $blob->get($path);

            $status = $response->getStatusCode();

            abort_unless(
                $status === 200,
                404,
                'Analysis Report tidak ditemukan di Blob storage.'
            );

            $contentType =
                $response->getHeaderLine('Content-Type')
                ?: 'application/pdf';

            $contentLength =
                $response->getHeaderLine('Content-Length');

            return response()->stream(
                function () use ($response) {
                    $body = $response->getBody();

                    while (!$body->eof()) {
                        echo $body->read(8192);
                    }
                },
                200,
                array_filter([
                    'Content-Type' => $contentType,
                    'Content-Disposition' =>
                        'inline; filename="' .
                        basename($path) .
                        '"',
                    'Content-Length' => $contentLength ?: null,
                    'Cache-Control' => 'private, no-store',
                ])
            );
        } catch (RuntimeException $e) {
            abort(
                500,
                'Gagal mengambil Analysis Report dari Blob storage.'
            );
        }
    }
}