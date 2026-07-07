<?php

namespace App\Support;

/**
 * Daftar doc_key dokumen pendukung yang wajib ada per template_slug surat.
 * Harus tetap sinkron dengan daftar dokumen di resources/js/Pages/SuratTemplates/Show.vue.
 */
class LetterDocumentRequirements
{
    public static function requiredKeys(?string $templateSlug, array $payload = []): array
    {
        return match ($templateSlug) {
            'keterangan-domisili' => [
                'suratPengantarRtRwDom',
                'fotoKtpDomisili',
            ],
            'keterangan-kematian' => [
                'suratPengantarRtRw',
                'suratKetKematian',
                'fotoKtpAlmarhum',
                'fotoKkAlmarhum',
                'fotoKtpPemohon',
                'suratPernyataanPelapor',
            ],
            'keterangan-pindah' => [
                'suratPengantarRt',
                'fotoKtpPindah',
                'fotoKkPindah',
                'suratKetPasFoto',
                'pasFotoPindah',
            ],
            'keterangan-kelahiran' => self::kelahiranKeys($payload['jenisPendaftaranKelahiran'] ?? null),
            default => [],
        };
    }

    private static function kelahiranKeys(?string $kasus): array
    {
        return match ($kasus) {
            'normal_0_60', 'normal_lebih_60' => array_merge(
                [
                    'suratKetLahir',
                    'fotoKkKelahiran',
                    'fotoKtpAyahIbu',
                    'fotoBukuNikah',
                    'fotoKtp2Saksi',
                    'suratPengantarRtRwLahir',
                ],
                $kasus === 'normal_lebih_60'
                    ? ['sptjmDataKelahiran', 'suratPernyataanBelumAkta', 'fotoIjazahOrtu']
                    : []
            ),
            'luar_nikah' => [
                'suratKetLahirLN',
                'fotoKkIbu',
                'fotoKtpAyahIbuLN',
                'fotoKtp2SaksiLN',
                'suratPengantarRtRwLN',
            ],
            // Kasus belum diketahui (data lama sebelum field ini disimpan, atau belum dipilih) —
            // tidak bisa ditentukan dokumen wajibnya, jadi jangan tandai sebagai kurang lengkap.
            default => [],
        };
    }

    /**
     * @param  string[]  $existingKeys  doc_key dokumen yang sudah diupload untuk surat ini
     * @return string[]  doc_key yang wajib tapi belum ada
     */
    public static function missingKeys(?string $templateSlug, array $payload, array $existingKeys): array
    {
        return array_values(array_diff(self::requiredKeys($templateSlug, $payload), $existingKeys));
    }
}
