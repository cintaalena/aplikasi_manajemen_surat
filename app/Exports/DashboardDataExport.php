<?php

namespace App\Exports;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardDataExport implements WithMultipleSheets
{
    protected array $metrics;

    public function __construct(array $metrics)
    {
        $this->metrics = $metrics;
    }

    public function sheets(): array
    {
        return [
            new ArraySheetExport('Surat', $this->buildSuratSheet()),
            new ArraySheetExport('Penduduk', $this->buildPendudukSheet()),
            new ArraySheetExport('Gender', $this->buildGenderSheet()),
            new ArraySheetExport('Kepala Keluarga', $this->buildKkSheet()),
            new ArraySheetExport('Usia', $this->buildAgeSheet()),
            new ArraySheetExport('Pekerjaan', $this->buildJobSheet()),
            new ArraySheetExport('Pendidikan', $this->buildEducationSheet()),
        ];
    }

    protected function buildSuratSheet(): array
    {
        $rows = [
            ['Jenis Surat', 'Jumlah'],
        ];

        $suratRows = $this->metrics['letters_by_template'] ?? [];

        foreach ($suratRows as $row) {
            $rows[] = [
                $row['label'] ?? '-',
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $rows[] = [
            'TOTAL',
            (int) ($this->metrics['total_letters'] ?? 0),
        ];

        return $rows;
    }

    protected function buildPendudukSheet(): array
    {
        $rows = [
            ['Keterangan', 'Jumlah'],
            ['Total Penduduk', (int) ($this->metrics['population']['total'] ?? 0)],
            ['Laki-laki', (int) ($this->metrics['population']['laki_laki'] ?? 0)],
            ['Perempuan', (int) ($this->metrics['population']['perempuan'] ?? 0)],
            ['Total Kepala Keluarga', (int) ($this->metrics['population']['kepala_keluarga'] ?? 0)],
            [''],
            ['Agama', 'Jumlah'],
        ];

        $agamaRows = $this->metrics['religion_groups']['rows'] ?? [];

        foreach ($agamaRows as $row) {
            $rows[] = [
                $row['agama'] ?? '-',
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $rows[] = [
            'TOTAL',
            (int) ($this->metrics['religion_groups']['totals']['jumlah'] ?? 0),
        ];

        return $rows;
    }

    protected function buildGenderSheet(): array
    {
        $rows = [
            ['RT/RW', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ];

        $dataRows = $this->metrics['gender_groups']['rows'] ?? [];

        foreach ($dataRows as $row) {
            $rows[] = [
                $row['rt_rw'] ?? '-',
                (int) ($row['laki_laki'] ?? 0),
                (int) ($row['perempuan'] ?? 0),
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $totals = $this->metrics['gender_groups']['totals'] ?? [];

        $rows[] = [
            'TOTAL',
            (int) ($totals['laki_laki'] ?? 0),
            (int) ($totals['perempuan'] ?? 0),
            (int) ($totals['jumlah'] ?? 0),
        ];

        return $rows;
    }

    protected function buildKkSheet(): array
    {
        $rows = [
            ['RT/RW', 'Laki-laki', 'Perempuan', 'Jumlah Kepala Keluarga'],
        ];

        $dataRows = $this->metrics['kk_groups']['rows'] ?? [];

        foreach ($dataRows as $row) {
            $rows[] = [
                $row['rt_rw'] ?? '-',
                (int) ($row['kk_laki_laki'] ?? 0),
                (int) ($row['kk_perempuan'] ?? 0),
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $totals = $this->metrics['kk_groups']['totals'] ?? [];

        $rows[] = [
            'TOTAL',
            (int) ($totals['laki_laki'] ?? 0),
            (int) ($totals['perempuan'] ?? 0),
            (int) ($totals['jumlah'] ?? 0),
        ];

        return $rows;
    }

    protected function buildAgeSheet(): array
    {
        $rows = [
            ['Kelompok Umur', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ];

        $dataRows = $this->metrics['age_groups']['rows'] ?? [];

        foreach ($dataRows as $row) {
            $rows[] = [
                $row['kelompok_umur'] ?? '-',
                (int) ($row['laki_laki'] ?? 0),
                (int) ($row['perempuan'] ?? 0),
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $totals = $this->metrics['age_groups']['totals'] ?? [];

        $rows[] = [
            'TOTAL',
            (int) ($totals['laki_laki'] ?? 0),
            (int) ($totals['perempuan'] ?? 0),
            (int) ($totals['jumlah'] ?? 0),
        ];

        return $rows;
    }

    protected function buildJobSheet(): array
    {
        $rows = [
            ['Pekerjaan', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ];

        $dataRows = $this->metrics['job_groups']['rows'] ?? [];

        foreach ($dataRows as $row) {
            $rows[] = [
                $row['pekerjaan'] ?? '-',
                (int) ($row['laki_laki'] ?? 0),
                (int) ($row['perempuan'] ?? 0),
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $totals = $this->metrics['job_groups']['totals'] ?? [];

        $rows[] = [
            'TOTAL',
            (int) ($totals['laki_laki'] ?? 0),
            (int) ($totals['perempuan'] ?? 0),
            (int) ($totals['jumlah'] ?? 0),
        ];

        return $rows;
    }

    protected function buildEducationSheet(): array
    {
        $rows = [
            ['Tingkat Pendidikan', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ];

        $dataRows = $this->metrics['education_groups']['rows'] ?? [];

        foreach ($dataRows as $row) {
            $rows[] = [
                $row['pendidikan'] ?? '-',
                (int) ($row['laki_laki'] ?? 0),
                (int) ($row['perempuan'] ?? 0),
                (int) ($row['jumlah'] ?? 0),
            ];
        }

        $totals = $this->metrics['education_groups']['totals'] ?? [];

        $rows[] = [
            'TOTAL',
            (int) ($totals['laki_laki'] ?? 0),
            (int) ($totals['perempuan'] ?? 0),
            (int) ($totals['jumlah'] ?? 0),
        ];

        return $rows;
    }
}