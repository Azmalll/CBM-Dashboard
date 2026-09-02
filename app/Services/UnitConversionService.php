<?php

namespace App\Services;

class UnitConversionService
{
    /**
     * Operating parameters that support unit correction,
     * and the allowed unit labels for each.
     *
     * The canonical (stored) unit for every parameter in the
     * dashboard is the FIRST entry of each array.
     */
    public static function parameterUnits(): array
    {
        return [
            'suction_pressure'   => ['Psi', 'Bar', 'kg/cm2'],
            'discharge_pressure' => ['Psi', 'Bar', 'kg/cm2'],
            'flow_rate'          => ['USGPM', 'LPM', 'm3/h'],
            'bearing_temp_m_out' => ['°C', '°F'],
            'bearing_temp_m_in'  => ['°C', '°F'],
            'bearing_temp_p_in'  => ['°C', '°F'],
            'bearing_temp_p_out' => ['°C', '°F'],
            'current_phase_1'    => ['A'],
            'current_phase_2'    => ['A'],
            'current_phase_3'    => ['A'],
            'bentley_motor_x'    => ['um', 'mil'],
            'bentley_motor_y'    => ['um', 'mil'],
            'bentley_pump_x'     => ['um', 'mil'],
            'bentley_pump_y'     => ['um', 'mil'],
        ];
    }

    /**
     * Convert a raw value from one unit to another.
     *
     * @param  float  $value
     * @param  string  $from  Unit the value is currently expressed in.
     * @param  string  $to    Target unit.
     * @return float|null     null when the pair is not convertible.
     */
    public static function convert(float $value, string $from, string $to): ?float
    {
        if (trim($from) === trim($to)) {
            return $value;
        }

        $toBase = self::toBase($value, $from);

        if ($toBase === null) {
            return null;
        }

        return self::fromBase($toBase, $to);
    }

    /**
     * Convert to an SI-ish base unit.
     *
     *  pressure -> Bar
     *  flow     -> L/min
     *  temp     -> Celsius
     *  current  -> Ampere
     *  shaft    -> micrometers
     */
    private static function toBase(float $value, string $unit): ?float
    {
        $unit = trim($unit);

        if (in_array($unit, ['Psi', 'Bar', 'kg/cm2'], true)) {
            return match ($unit) {
                'Psi'    => $value * 0.0689476,
                'kg/cm2' => $value * 0.980665,
                default  => $value, // Bar
            };
        }

        if (in_array($unit, ['USGPM', 'LPM', 'm3/h'], true)) {
            return match ($unit) {
                'USGPM' => $value * 3.78541,
                'm3/h'  => $value * 1000 / 60,
                default => $value, // L/min
            };
        }

        if (in_array($unit, ['°C', '°F'], true)) {
            return match ($unit) {
                '°F'  => ($value - 32) * 5 / 9,
                default => $value, // °C
            };
        }

        if (in_array($unit, ['A', '%FLA'], true)) {
            // %FLA cannot be converted without the nameplate FLA value.
            return match ($unit) {
                '%FLA' => null,
                default => $value, // A
            };
        }

        if (in_array($unit, ['um', 'mil'], true)) {
            return match ($unit) {
                'mil' => $value * 25.4,
                default => $value, // um
            };
        }

        return null;
    }

    /**
     * Convert from a base unit to the requested display unit.
     */
    private static function fromBase(float $base, string $unit): ?float
    {
        $unit = trim($unit);

        if (in_array($unit, ['Psi', 'Bar', 'kg/cm2'], true)) {
            return match ($unit) {
                'Psi'    => $base / 0.0689476,
                'kg/cm2' => $base / 0.980665,
                default  => $base, // Bar
            };
        }

        if (in_array($unit, ['USGPM', 'LPM', 'm3/h'], true)) {
            return match ($unit) {
                'USGPM' => $base / 3.78541,
                'm3/h'  => $base * 60 / 1000,
                default => $base, // L/min
            };
        }

        if (in_array($unit, ['°C', '°F'], true)) {
            return match ($unit) {
                '°F'  => $base * 9 / 5 + 32,
                default => $base, // °C
            };
        }

        if (in_array($unit, ['A', '%FLA'], true)) {
            // Base is Ampere; only A is expressible.
            return match ($unit) {
                '%FLA' => null,
                default => $base, // A
            };
        }

        if (in_array($unit, ['um', 'mil'], true)) {
            return match ($unit) {
                'mil' => $base / 25.4,
                default => $base, // um
            };
        }

        return null;
    }
}
