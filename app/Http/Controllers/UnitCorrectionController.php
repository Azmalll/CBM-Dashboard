<?php

namespace App\Http\Controllers;

use App\Models\OperatingParameterCorrectionLog;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;

class UnitCorrectionController extends Controller
{
    /**
     * Human-readable label for every correctable parameter.
     */
    private function parameterLabels(): array
    {
        return [
            'suction_pressure'   => 'Suction Pressure',
            'discharge_pressure' => 'Discharge Pressure',
            'flow_rate'          => 'Flow Rate',
            'bearing_temp_m_out' => 'Bearing Temp M-Out',
            'bearing_temp_m_in'  => 'Bearing Temp M-In',
            'bearing_temp_p_in'  => 'Bearing Temp P-In',
            'bearing_temp_p_out' => 'Bearing Temp P-Out',
            'current_phase_1'    => 'Current Phase 1 (L1)',
            'current_phase_2'    => 'Current Phase 2 (L2)',
            'current_phase_3'    => 'Current Phase 3 (L3)',
            'bentley_motor_x'    => 'Bentley Motor X',
            'bentley_motor_y'    => 'Bentley Motor Y',
            'bentley_pump_x'     => 'Bentley Pump X',
            'bentley_pump_y'     => 'Bentley Pump Y',
        ];
    }

    /**
     * Unit correction page: scope form + history.
     */
    public function index()
    {
        $areas = \App\Models\Equipment::query()
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        $equipments = \App\Models\Equipment::query()
            ->orderBy('equipment_name')
            ->get([
                'id',
                'equipment_name',
                'area',
            ]);

        $parameterUnits = UnitConversionService::parameterUnits();

        $labels = $this->parameterLabels();

        $parameterOptions = collect($parameterUnits)
            ->map(function (array $units, string $key) use ($labels) {
                return [
                    'key'   => $key,
                    'label' => $labels[$key] ?? $key,
                    'units' => $units,
                ];
            })
            ->values();

        $logs = OperatingParameterCorrectionLog::query()
            ->latest('id')
            ->limit(50)
            ->get();

        return view(
            'unit-correction.index',
            compact(
                'areas',
                'equipments',
                'parameterOptions',
                'logs'
            )
        );
    }

    /**
     * Dry-run: compute what would change, without touching data.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'area'           => ['nullable', 'string'],
            'equipment_id'   => ['nullable', 'integer', 'exists:equipment,id'],
            'parameter_key'  => ['required', 'string'],
            'stored_unit'    => ['required', 'string'],
            'actual_unit'    => ['required', 'string'],
            'date_mode'      => ['required', 'in:all,single,range'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $result = $this->computeAffected($validated);

        // Strip Eloquent models — the preview payload is JSON only.
        $rows = array_map(function ($row) {
            unset($row['record']);
            return $row;
        }, $result['rows']);

        $result['rows'] = $rows;

        $result['parameter_label'] = $this->parameterLabels()[
            $validated['parameter_key']
        ] ?? $validated['parameter_key'];

        return response()->json($result);
    }

    /**
     * Apply the correction after explicit confirmation.
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'area'           => ['nullable', 'string'],
            'equipment_id'   => ['nullable', 'integer', 'exists:equipment,id'],
            'parameter_key'  => ['required', 'string'],
            'stored_unit'    => ['required', 'string'],
            'actual_unit'    => ['required', 'string'],
            'date_mode'      => ['required', 'in:all,single,range'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $result = $this->computeAffected($validated);

        if ($result['affected'] === 0) {
            return redirect()
                ->route('unit-correction.index')
                ->with('error', 'Tidak ada data yang perlu diubah untuk scope ini.');
        }

        $labels = $this->parameterLabels();

        $snapshot = [];

        foreach ($result['rows'] as $row) {
            $record = $row['record'];

            $operatingParameters =
                $record->operating_parameters ?? [];

            $operatingParameters[$validated['parameter_key']] =
                round($row['after'], 4);

            $record->operating_parameters = $operatingParameters;
            $record->save();

            $snapshot[] = [
                'record_id'       => $record->id,
                'equipment_name'  => $row['equipment_name'],
                'date'            => $row['date'],
                'before'          => $row['before'],
                'after'           => $row['after'],
            ];
        }

        OperatingParameterCorrectionLog::create([
            'user_id'           => $request->user()?->id,
            'scope_description' => $result['scope_description'],
            'parameter_key'     => $validated['parameter_key'],
            'stored_unit'       => $validated['stored_unit'],
            'actual_unit'       => $validated['actual_unit'],
            'values'            => $snapshot,
            'records_affected'  => $result['affected'],
        ]);

        return redirect()
            ->route('unit-correction.index')
            ->with(
                'success',
                sprintf(
                    'Unit correction berhasil: %d record "%s" dikonversi (%s → %s).',
                    $result['affected'],
                    $labels[$validated['parameter_key']]
                        ?? $validated['parameter_key'],
                    $validated['stored_unit'],
                    $validated['actual_unit']
                )
            );
    }

    /**
     * Undo a previous correction using its stored snapshot.
     */
    public function undo(Request $request, int $logId)
    {
        $log = OperatingParameterCorrectionLog::findOrFail($logId);

        if ($log->reverted_at !== null) {
            return redirect()
                ->route('unit-correction.index')
                ->with('error', 'Correction ini sudah di-undo sebelumnya.');
        }

        $restored = 0;

        foreach (($log->values ?? []) as $entry) {
            $record = \App\Models\EquipmentInspection::find(
                $entry['record_id'] ?? null
            );

            if (!$record) {
                continue;
            }

            $operatingParameters =
                $record->operating_parameters ?? [];

            $operatingParameters[$log->parameter_key] =
                $entry['before'];

            $record->operating_parameters = $operatingParameters;
            $record->save();

            $restored++;
        }

        $log->reverted_at = now();
        $log->save();

        return redirect()
            ->route('unit-correction.index')
            ->with(
                'success',
                sprintf(
                    'Undo berhasil: %d record dikembalikan ke nilai semula.',
                    $restored
                )
            );
    }

    /**
     * Collect every value in scope and compute the converted result.
     *
     * Returns:
     * [
     *   scope_description => string,
     *   affected          => int,
     *   rows              => [
     *       record, equipment_name, date, before, after
     *   ]
     * ]
     */
    private function computeAffected(array $input): array
    {
        $query = \App\Models\EquipmentInspection::query()
            ->whereNotNull('operating_parameters')
            ->with(['equipment', 'inspection']);

        if (!empty($input['equipment_id'])) {
            $query->where('equipment_id', $input['equipment_id']);
        } elseif (!empty($input['area'])) {
            $query->whereHas(
                'equipment',
                function ($equipmentQuery) use ($input) {
                    $equipmentQuery->where('area', $input['area']);
                }
            );
        }

        $dateMode = $input['date_mode'] ?? 'all';

        if ($dateMode === 'single' && !empty($input['date_from'])) {
            $query->whereHas(
                'inspection',
                function ($inspectionQuery) use ($input) {
                    $inspectionQuery->whereDate(
                        'inspection_date',
                        $input['date_from']
                    );
                }
            );
        }

        if ($dateMode === 'range') {
            $query->whereHas(
                'inspection',
                function ($inspectionQuery) use ($input) {
                    if (!empty($input['date_from'])) {
                        $inspectionQuery->whereDate(
                            'inspection_date',
                            '>=',
                            $input['date_from']
                        );
                    }

                    if (!empty($input['date_to'])) {
                        $inspectionQuery->whereDate(
                            'inspection_date',
                            '<=',
                            $input['date_to']
                        );
                    }
                }
            );
        }

        $key = $input['parameter_key'];
        $from = $input['stored_unit'];
        $to = $input['actual_unit'];

        $rows = [];

        foreach ($query->get() as $record) {
            $operatingParameters =
                $record->operating_parameters ?? [];

            $raw = $operatingParameters[$key] ?? null;

            if ($raw === null || $raw === '') {
                continue;
            }

            $value = filter_var($raw, FILTER_VALIDATE_FLOAT);

            if ($value === false || $value === null) {
                continue;
            }

            $after = UnitConversionService::convert($value, $from, $to);

            if ($after === null) {
                continue;
            }

            $rows[] = [
                'record'         => $record,
                'equipment_name' => $record->equipment?->equipment_name
                    ?? '-',
                'date'           => optional(
                    $record->inspection
                )->inspection_date,
                'before'         => $value,
                'after'          => round($after, 4),
            ];
        }

        $rows = collect($rows)
            ->sortBy(fn ($row) => (string) $row['date'])
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | SCOPE DESCRIPTION
        |--------------------------------------------------------------------------
        */

        $scopeParts = [];

        if (!empty($input['equipment_id'])) {
            $scopeParts[] = 'Equipment #' . $input['equipment_id'];
        } elseif (!empty($input['area'])) {
            $scopeParts[] = 'Area: ' . $input['area'];
        } else {
            $scopeParts[] = 'Semua area';
        }

        $scopeParts[] = match ($dateMode) {
            'single' => 'Tgl: ' . ($input['date_from'] ?? '-'),
            'range'  => 'Tgl: '
                . ($input['date_from'] ?? 'mulai')
                . ' - '
                . ($input['date_to'] ?? 'sekarang'),
            default  => 'Semua tanggal',
        };

        return [
            'scope_description' => implode(' · ', $scopeParts),
            'affected'          => count($rows),
            'rows'              => $rows,
        ];
    }
}
