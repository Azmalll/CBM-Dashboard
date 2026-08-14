<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentInspectionReportController extends Controller
{
    /**
     * Upload / replace the analysis report for one equipment measurement.
     *
     * One EquipmentInspection = one equipment measurement session
     * and therefore one analysis report.
     */
    public function upload(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $validated = $request->validate([
            'analysis_report' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480',
            ],
        ], [
            'analysis_report.required' => 'Silakan pilih file Analysis Report.',
            'analysis_report.file' => 'File yang dipilih tidak valid.',
            'analysis_report.mimes' => 'Analysis Report harus berupa file PDF.',
            'analysis_report.max' => 'Ukuran Analysis Report maksimal 20 MB.',
        ]);

        $oldReport = $equipmentInspection->report_file;

        $file = $validated['analysis_report'];

        $filename = sprintf(
            'equipment-%s-measurement-%s-%s.pdf',
            $equipmentInspection->equipment_id,
            $equipmentInspection->id,
            now()->format('YmdHis')
        );

        $path = $file->storeAs(
            'reports',
            $filename,
            'public'
        );

        $equipmentInspection->update([
            'report_file' => $path,
        ]);

        if (
            $oldReport &&
            Storage::disk('public')->exists($oldReport) &&
            $oldReport !== $path
        ) {
            Storage::disk('public')->delete($oldReport);
        }

        return response()->json([
            'success' => true,
            'message' => 'Analysis Report berhasil di-upload.',
            'reportFile' => $path,
            'reportUrl' => route(
                'equipment-inspection.analysis-report.show',
                $equipmentInspection
            ),
        ]);
    }

    /**
     * Display the analysis report inline in the browser.
     */
    public function show(
        EquipmentInspection $equipmentInspection
    ) {
        $path = $equipmentInspection->report_file;

        abort_unless(
            $path &&
            Storage::disk('public')->exists($path),
            404,
            'Analysis Report belum tersedia.'
        );

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' .
                    basename($path) .
                    '"',
            ]
        );
    }
}