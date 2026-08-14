<?php

namespace App\Services;

use Carbon\Carbon;

class OdxParser
{
    /**
     * Parse file ODX dan mengambil seluruh data Overall Velocity.
     *
     * Selain Overall Velocity, parser juga membaca timestamp
     * dari task lain seperti:
     *
     * - Machine Spectrum
     * - VXP Machine Time Signal
     *
     * Timestamp task-task tersebut digunakan untuk menentukan
     * measurement session terbaru pada equipment + measurement point.
     *
     * Machine Spectrum dan Machine Time Signal TIDAK dibuat
     * menjadi Measurement Result.
     */
    public function parseOverallVelocity(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                "ODX file tidak ditemukan: {$filePath}"
            );
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                "ODX file tidak dapat dibuka."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HASIL PARSER
        |--------------------------------------------------------------------------
        */

        $results = [];

        /*
        |--------------------------------------------------------------------------
        | SEMUA TIMESTAMP SESSION
        |--------------------------------------------------------------------------
        |
        | Struktur:
        |
        | [
        |     "Equipment|Measurement Point" => [
        |         timestamp1,
        |         timestamp2,
        |         ...
        |     ]
        | ]
        |
        */

        $sessionTimestamps = [];

        /*
        |--------------------------------------------------------------------------
        | TASK AKTIF
        |--------------------------------------------------------------------------
        */

        $task = [];

        $currentSection = null;

        /*
        |--------------------------------------------------------------------------
        | BACA FILE
        |--------------------------------------------------------------------------
        */

        while (($line = fgets($handle)) !== false) {

            $line = trim($line);


            /*
            |--------------------------------------------------------------------------
            | TASK BARU
            |--------------------------------------------------------------------------
            */

            if ($line === '#----- Start Task -----') {

                /*
                | Proses task sebelumnya
                */
                $this->processTask(
                    $results,
                    $sessionTimestamps,
                    $task
                );

                /*
                | Reset task
                */
                $task = [];

                $currentSection = null;

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | PATH
            |--------------------------------------------------------------------------
            */

            if ($line === '#Path') {

                $currentSection = 'path';

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TASK ID
            |--------------------------------------------------------------------------
            */

            if ($line === '#Task ID') {

                $currentSection = 'task_id';

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | MEASUREMENT OBJECT
            |--------------------------------------------------------------------------
            */

            if ($line === '#Meas-Object,Meas-Type,Quantity') {

                $currentSection = 'measurement';

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TREND PLUS REF
            |--------------------------------------------------------------------------
            */

            if ($line === '#TrendPlusRef') {

                $currentSection = 'trend_header';

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DATE
            |--------------------------------------------------------------------------
            |
            | Contoh ODX:
            |
            | #Date
            | 1785828184=Tue Aug 04 14:23:04 2026
            |
            | Timestamp sebelah kiri "=" adalah yang kita gunakan.
            |
            */

            if ($line === '#Date') {

                $currentSection = 'date';

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | PATH DATA
            |--------------------------------------------------------------------------
            */

            if ($currentSection === 'path') {

                if (
                    $line !== '' &&
                    !str_starts_with($line, '#')
                ) {

                    $task['path'] = $line;

                    $currentSection = null;
                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TASK ID DATA
            |--------------------------------------------------------------------------
            */

            if ($currentSection === 'task_id') {

                if (
                    $line !== '' &&
                    !str_starts_with($line, '#')
                ) {

                    $task['task_id'] = (int) $line;

                    $currentSection = null;
                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | MEASUREMENT DATA
            |--------------------------------------------------------------------------
            */

            if ($currentSection === 'measurement') {

                if (
                    $line !== '' &&
                    !str_starts_with($line, '#')
                ) {

                    $parts = preg_split(
                        '/\s+/',
                        $line
                    );

                    if (count($parts) >= 3) {

                        $task['meas_object'] =
                            (int) $parts[0];

                        $task['meas_type'] =
                            (int) $parts[1];

                        $task['quantity'] =
                            (int) $parts[2];
                    }

                    $currentSection = null;
                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DATE DATA
            |--------------------------------------------------------------------------
            */

            if ($currentSection === 'date') {

                if (
                    $line !== '' &&
                    !str_starts_with($line, '#')
                ) {

                    /*
                    | Ambil angka sebelum "="
                    */
                    $parts = explode(
                        '=',
                        $line,
                        2
                    );

                    if (
                        isset($parts[0]) &&
                        is_numeric($parts[0])
                    ) {

                        $timestamp =
                            (int) trim($parts[0]);

                        $task['date_timestamps'][] =
                            $timestamp;
                    }

                    $currentSection = null;
                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TREND HEADER
            |--------------------------------------------------------------------------
            |
            | Setelah #TrendPlusRef biasanya:
            |
            | 3
            |
            | Angka tersebut adalah header.
            |
            */

            if ($currentSection === 'trend_header') {

                if (
                    $line !== '' &&
                    !str_starts_with($line, '#')
                ) {

                    $currentSection = 'trend_data';

                    continue;
                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TREND DATA
            |--------------------------------------------------------------------------
            */

            if ($currentSection === 'trend_data') {

                /*
                |--------------------------------------------------------------------------
                | TREND DATA
                |--------------------------------------------------------------------------
                |
                | Struktur #TrendPlusRef yang terverifikasi pada ODX:
                |
                | timestamp  Peak(0-P)  Peak-to-Peak  RMS  ...
                | 1781858508 8.418442 16.500122 4.998717 ...
                |
                | Jadi:
                | parts[0] = timestamp
                | parts[1] = Peak / 0-P
                | parts[2] = Peak-to-Peak
                | parts[3] = Overall Velocity RMS
                |
                | Crest Factor tidak diambil langsung dari ODX.
                | Kita hitung sesuai definisi:
                |
                | Crest Factor = Peak / RMS
                |
                */

                if (
                    $line === '' ||
                    str_starts_with($line, '#')
                ) {
                    $currentSection = null;
                    continue;
                }

                $parts = preg_split(
                    '/\s+/',
                    $line
                );

                if (count($parts) >= 4) {

                    $timestamp = (int) $parts[0];

                    $peakValue =
                        isset($parts[1]) && is_numeric($parts[1])
                            ? (float) $parts[1]
                            : null;

                    $peakToPeak =
                        isset($parts[2]) && is_numeric($parts[2])
                            ? (float) $parts[2]
                            : null;

                    $rmsValue =
                        isset($parts[3]) && is_numeric($parts[3])
                            ? (float) $parts[3]
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | CREST FACTOR
                    |--------------------------------------------------------------------------
                    |
                    | ODX tidak menuliskan Crest Factor sebagai field terpisah.
                    | Berdasarkan struktur data ODX yang diverifikasi:
                    |
                    | Crest Factor = Peak / RMS
                    |
                    */

                    $crestFactor = null;

                    if (
                        $peakValue !== null &&
                        $rmsValue !== null &&
                        abs($rmsValue) > 0.0000001
                    ) {
                        $crestFactor =
                            $peakValue / $rmsValue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI NILAI RMS
                    |--------------------------------------------------------------------------
                    */

                    if ($rmsValue === null) {
                        continue;
                    }

                    $task['trend_rows'][] = [

                        'timestamp' =>
                            $timestamp,

                        /*
                        | Nilai utama yang masuk sebagai
                        | Overall Velocity adalah RMS.
                        */
                        'measurement_value' =>
                            $rmsValue,

                        /*
                        | Disimpan untuk kompatibilitas kolom existing.
                        | UI baru tidak menampilkan Peak.
                        */
                        'peak_value' =>
                            $peakValue,

                        /*
                        | Peak-to-Peak tersedia di ODX tetapi
                        | database existing belum memiliki kolom
                        | dedicated untuk field ini.
                        */
                        'peak_to_peak' =>
                            $peakToPeak,

                        /*
                        | Crest Factor dihitung dari Peak / RMS.
                        */
                        'crest_factor' =>
                            $crestFactor,

                        'measurement_datetime' =>
                            $this->formatTimestamp(
                                $timestamp
                            ),
                    ];
                }

                continue;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PROSES TASK TERAKHIR
        |--------------------------------------------------------------------------
        */

        $this->processTask(
            $results,
            $sessionTimestamps,
            $task
        );


        fclose($handle);


        /*
        |--------------------------------------------------------------------------
        | ASSIGN SESSION DATETIME
        |--------------------------------------------------------------------------
        |
        | Sekarang semua Overall Velocity sudah terkumpul.
        |
        | Kita cari timestamp session yang paling tepat
        | untuk setiap Overall Velocity.
        */

        foreach ($results as &$result) {

            $equipmentName =
                $result['equipment_name'] ?? null;

            $measurementPointName =
                $result['measurement_point_name'] ?? null;

            $overallTimestamp =
                $result['timestamp'] ?? null;


            if (
                !$equipmentName ||
                !$measurementPointName ||
                !$overallTimestamp
            ) {
                continue;
            }


            $sessionKey =
                $this->makeSessionKey(
                    $equipmentName,
                    $measurementPointName
                );


            $candidateTimestamps =
                $sessionTimestamps[$sessionKey]
                ?? [];


            /*
            |--------------------------------------------------------------------------
            | CARI SESSION TIMESTAMP
            |--------------------------------------------------------------------------
            */

            $sessionTimestamp =
                $this->findSessionTimestamp(
                    $overallTimestamp,
                    $candidateTimestamps
                );


            /*
            |--------------------------------------------------------------------------
            | FALLBACK
            |--------------------------------------------------------------------------
            |
            | Kalau tidak ditemukan task lanjutan,
            | gunakan timestamp Overall Velocity sendiri.
            */

            if ($sessionTimestamp === null) {

                $sessionTimestamp =
                    $overallTimestamp;
            }


            /*
            |--------------------------------------------------------------------------
            | TIMESTAMP YANG DISIMPAN
            |--------------------------------------------------------------------------
            */

            $result['measurement_datetime'] =
                $this->formatTimestamp(
                    $sessionTimestamp
                );


            /*
            | Simpan juga timestamp session mentah
            | untuk debugging / kebutuhan berikutnya.
            */
            $result['session_timestamp'] =
                $sessionTimestamp;


            $result['session_datetime'] =
                $this->formatTimestamp(
                    $sessionTimestamp
                );
        }

        unset($result);


        return $results;
    }


    /**
     * Proses satu task ODX.
     */
    private function processTask(
        array &$results,
        array &$sessionTimestamps,
        array $task
    ): void {

        /*
        |--------------------------------------------------------------------------
        | HARUS ADA PATH
        |--------------------------------------------------------------------------
        */

        if (empty($task['path'])) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PARSE EQUIPMENT & MEASUREMENT POINT
        |--------------------------------------------------------------------------
        */

        [
            $equipmentName,
            $measurementPointName
        ] = $this->extractPathInformation(
            $task['path']
        );


        if (
            !$equipmentName ||
            !$measurementPointName
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | SESSION KEY
        |--------------------------------------------------------------------------
        */

        $sessionKey =
            $this->makeSessionKey(
                $equipmentName,
                $measurementPointName
            );


        /*
        |--------------------------------------------------------------------------
        | SIMPAN TIMESTAMP DARI #DATE
        |--------------------------------------------------------------------------
        |
        | Ini terutama digunakan oleh:
        |
        | - Machine Spectrum
        | - Machine Time Signal
        |
        */

        if (
            !empty($task['date_timestamps']) &&
            is_array($task['date_timestamps'])
        ) {

            foreach (
                $task['date_timestamps']
                as $timestamp
            ) {

                $sessionTimestamps[$sessionKey][] =
                    (int) $timestamp;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SIMPAN TIMESTAMP DARI TREND
        |--------------------------------------------------------------------------
        |
        | Untuk Overall Velocity, timestamp
        | berasal dari TrendPlusRef.
        |
        */

        if (
            !empty($task['trend_rows']) &&
            is_array($task['trend_rows'])
        ) {

            foreach (
                $task['trend_rows']
                as $trend
            ) {

                if (
                    isset($trend['timestamp']) &&
                    is_numeric($trend['timestamp'])
                ) {

                    $sessionTimestamps[$sessionKey][] =
                        (int) $trend['timestamp'];
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | HANYA OVERALL VELOCITY YANG MENJADI RESULT
        |--------------------------------------------------------------------------
        */

        if (
            !str_contains(
                strtolower($task['path']),
                'overall velocity'
            )
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | HARUS ADA TREND DATA
        |--------------------------------------------------------------------------
        */

        if (
            empty($task['trend_rows']) ||
            !is_array($task['trend_rows'])
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | BUAT RESULT OVERALL VELOCITY
        |--------------------------------------------------------------------------
        */

        foreach (
            $task['trend_rows']
            as $trend
        ) {

            if (
                !isset($trend['timestamp']) ||
                !isset($trend['measurement_value'])
            ) {
                continue;
            }


            $results[] = [

                'task_id' =>
                    $task['task_id'] ?? null,

                'path' =>
                    $task['path'],

                'equipment_name' =>
                    $equipmentName,

                'measurement_point_name' =>
                    $measurementPointName,

                /*
                | Timestamp asli Overall Velocity.
                |
                | Akan diganti menjadi session datetime
                | setelah seluruh task selesai dibaca.
                */
                'measurement_datetime' =>
                    $trend['measurement_datetime'],

                /*
                | Timestamp mentah Overall Velocity.
                */
                'timestamp' =>
                    $trend['timestamp'],

                'overall_velocity' =>
                    $trend['measurement_value'],

                'peak_value' =>
                    $trend['peak_value'] ?? null,

                'crest_factor' =>
                    $trend['crest_factor'] ?? null,

                'peak_to_peak' =>
                    $trend['peak_to_peak'] ?? null,

                'meas_object' =>
                    $task['meas_object'] ?? null,

                'meas_type' =>
                    $task['meas_type'] ?? null,

                'quantity' =>
                    $task['quantity'] ?? null,
            ];
        }
    }


    /**
     * Ambil Equipment Name dan Measurement Point
     * dari ODX Path.
     *
     * Contoh:
     *
     * Minas
     * GS-1
     * SB 1.3
     * Motor
     * MIA
     * 102 Overall velocity >120
     */
    private function extractPathInformation(
        string $path
    ): array {

        $pathParts = preg_split(
            '/\\\\+/',
            $path
        );


        $equipmentName = null;

        $measurementPointName = null;


        if (count($pathParts) >= 5) {

            $equipmentName =
                trim($pathParts[2]);

            $measurementPointName =
                trim($pathParts[4]);
        }


        return [
            $equipmentName,
            $measurementPointName,
        ];
    }


    /**
     * Buat key untuk grouping session.
     */
    private function makeSessionKey(
        string $equipmentName,
        string $measurementPointName
    ): string {

        return
            strtolower(
                trim($equipmentName)
            )
            . '|'
            .
            strtolower(
                trim($measurementPointName)
            );
    }


    /**
     * Cari timestamp session yang paling tepat
     * untuk sebuah Overall Velocity.
     *
     * Prinsip:
     *
     * 1. Cari timestamp task lain yang terjadi
     *    setelah Overall Velocity.
     *
     * 2. Hanya gunakan timestamp dalam window
     *    yang wajar.
     *
     * 3. Pilih timestamp paling dekat.
     *
     * Contoh:
     *
     * Overall Velocity
     * 14:22:53
     *
     * Machine Spectrum
     * 14:23:04
     *
     * Machine Time Signal
     * 14:23:11
     *
     * Maka session:
     *
     * 14:23:11
     */
    private function findSessionTimestamp(
        int $overallTimestamp,
        array $candidateTimestamps
    ): ?int {

        if (empty($candidateTimestamps)) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI & UNIQUE
        |--------------------------------------------------------------------------
        */

        $candidateTimestamps =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $candidateTimestamps
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | SORT ASCENDING
        |--------------------------------------------------------------------------
        */

        sort($candidateTimestamps);


        /*
        |--------------------------------------------------------------------------
        | WINDOW SESSION
        |--------------------------------------------------------------------------
        |
        | Measurement Overall, Spectrum dan Time Signal
        | pada satu session biasanya berdekatan.
        |
        | Kita gunakan maksimum 5 menit.
        |
        */

        $maxSessionGap = 300;


        $futureCandidates = [];


        foreach (
            $candidateTimestamps
            as $timestamp
        ) {

            /*
            | Jangan menggunakan timestamp yang
            | lebih awal dari Overall Velocity.
            */

            if ($timestamp < $overallTimestamp) {
                continue;
            }


            $difference =
                $timestamp - $overallTimestamp;


            /*
            | Hanya timestamp dalam window
            | 5 menit setelah Overall.
            */

            if ($difference <= $maxSessionGap) {

                $futureCandidates[] =
                    $timestamp;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | TIDAK ADA CANDIDATE
        |--------------------------------------------------------------------------
        */

        if (empty($futureCandidates)) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | PILIH TIMESTAMP TERAKHIR
        |--------------------------------------------------------------------------
        |
        | Karena session terdiri dari:
        |
        | Overall
        | Spectrum
        | Time Signal
        |
        | kita menggunakan timestamp paling akhir
        | sebagai session datetime.
        */

        return max(
            $futureCandidates
        );
    }


    /**
     * Convert Unix timestamp ODX
     * menjadi waktu lokal Indonesia.
     *
     * ODX timestamp pada data kamu:
     *
     * 1785828173
     *
     * harus direpresentasikan sebagai:
     *
     * 04 Aug 2026 14:22:53
     *
     * bukan 07:22:53.
     */
    private function formatTimestamp(
        int $timestamp
    ): string {

        return Carbon::createFromTimestamp(
            $timestamp,
            'Asia/Jakarta'
        )->format(
            'Y-m-d H:i:s'
        );
    }
}
