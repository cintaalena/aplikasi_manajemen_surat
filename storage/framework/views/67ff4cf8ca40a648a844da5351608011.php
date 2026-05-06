<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($letter->title); ?> – <?php echo e($letter->no_surat); ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #e5e7eb;
      font-family: "Bookman Old Style", "Bookman", "Palatino Linotype", serif;
      font-size: 12pt;
      color: #000;
      padding: 24px 0;
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      background: #fff;
      margin: 0 auto 24px;
      padding: 20mm 22mm;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    }

    /* ── KOP ─────────────────────────────── */
    .kop-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .kop-logo-cell { width: 110px; vertical-align: middle; text-align: left; }
    .kop-spacer-cell { width: 110px; }
    .kop-logo { width: 90px; height: auto; display: block; }
    .kop-text { text-align: center; font-family: Arial, Helvetica, sans-serif; }
    .kop-line1 { font-size: 16pt; font-weight: 700; line-height: 1.15; letter-spacing: 0.2px; }
    .kop-line2 { font-size: 10pt; font-weight: 400; margin-top: 4px; }
    .kop-rule { margin-top: 6px; }
    .kop-rule .rule-1 { border-top: 2px solid #000; }
    .kop-rule .rule-2 { border-top: 1px solid #000; margin-top: 2px; }

    /* ── JUDUL ───────────────────────────── */
    .judul { text-align: center; margin-top: 18px; }
    .judul-utama { font-weight: bold; text-decoration: underline; font-size: 13pt; text-transform: uppercase; }
    .judul-nomor { margin-top: 4px; }

    /* ── PARAGRAF ────────────────────────── */
    .paragraf { margin-top: 14px; line-height: 1.7; }

    /* ── DATA rows (flex) ────────────────── */
    .data-rows { margin-top: 14px; }
    .data-row { display: flex; align-items: flex-start; line-height: 1.7; }
    .data-lbl { flex: 0 0 200px; padding-left: 36px; word-break: keep-all; }
    .data-sep { flex: 0 0 14px; }
    .data-val { flex: 1; word-break: break-word; }
    .data-spasi { height: 10px; }

    /* ── DATA table ──────────────────────── */
    .data-table { margin-top: 10px; border-collapse: collapse; }
    .data-table td { vertical-align: top; line-height: 1.7; }
    .data-table .lbl { white-space: nowrap; padding-right: 4px; min-width: 180px; padding-left: 24px; }
    .data-table .sep { padding-right: 10px; }
    .data-table .val { }
    .data-table .spasi td { height: 10px; }
    .nowrap { white-space: nowrap; }

    /* ── PENGIKUT table (pindah) ─────────── */
    .pengikut-table { margin-top: 16px; width: 100%; border-collapse: collapse; font-size: 11pt; }
    .pengikut-table th, .pengikut-table td { border: 1px solid #000; padding: 4px 8px; text-align: left; }
    .pengikut-table th { text-align: center; }
    .pengikut-table td.center { text-align: center; }

    /* ── PINDAH sub-table ────────────────── */
    .pindah-table { margin-top: 10px; border-collapse: collapse; }
    .pindah-table td { vertical-align: top; line-height: 1.7; padding-right: 6px; }
    .pindah-table .lbl { min-width: 160px; }
    .pindah-table .indent .lbl { padding-left: 24px; min-width: 160px; }
    .bold { font-weight: bold; }

    /* ── TTD ─────────────────────────────── */
    .ttd-wrapper { display: flex; justify-content: flex-end; margin-top: 40px; }
    .ttd-wrapper-split { display: flex; justify-content: space-between; margin-top: 40px; }
    .ttd { width: 280px; text-align: center; line-height: 1.7; }
    .ttd-tanggal { margin-bottom: 4px; }
    .ttd-jabatan { }
    .ttd-space { height: 70px; }
    .ttd-nama { font-weight: bold; text-decoration: underline; white-space: nowrap; }
    .ttd-nip { }

    /* ── TOOLBAR (non-print) ─────────────── */
    .toolbar {
      width: 210mm;
      margin: 0 auto 16px;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .btn {
      padding: 8px 20px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      font-family: Arial, sans-serif;
      text-decoration: none;
      display: inline-block;
    }
    .btn-print { background: linear-gradient(to right, #7c3aed, #d946ef); color: #fff; }
    .btn-back  { background: #fff; border: 1px solid #d1d5db; color: #374151; }
    .meta { font-family: Arial, sans-serif; font-size: 11px; color: #6b7280; }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none; }
      .page { box-shadow: none; margin: 0; padding: 15mm 18mm; width: 100%; min-height: 0; }
    }
  </style>
</head>
<body>

<?php
/* ── helpers ──────────────────────────────────────────────────────── */
function bl_titleCase(string $s): string {
    return mb_convert_case(trim($s), MB_CASE_TITLE, 'UTF-8');
}

function bl_tanggalIndo(?string $d): string {
    if (!$d) return '';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    try {
        // handle "YYYY-MM-DD" or full datetime
        $ts = strtotime($d);
        if (!$ts) return $d;
        return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    } catch (Exception $e) { return $d; }
}

function bl_pad3($v): string {
    $s = preg_replace('/\D/', '', (string)($v ?? ''));
    if (!$s) return '___';
    return str_pad(substr($s, -3), 3, '0', STR_PAD_LEFT);
}

function bl_terbilang(int $n): string {
    $map = ['','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan','Sepuluh'];
    return $map[$n] ?? (string)$n;
}

$jabatanLabel = [
    'lurah'                             => 'Lurah Fatubesi',
    'sekretaris'                        => 'Sekretaris',
    'kasie_pelayanan_masyarakat'        => 'Kasie Pelayanan Masyarakat',
    'kasie_pem_trantib_umum'            => 'Kasie PEM & Trantibum',
    'pengelola_pemberdayaan_masyarakat' => 'Pengelola Pember. Masy. & Kelembagaan',
    'pengadministrasi_perkantoran'      => 'Pengadministrasi Perkantoran',
    'penata_layanan_operasional'        => 'Penata Layanan Operasional',
];

$p       = $letter->payload ?? [];
$slug    = $letter->template_slug ?? '';
$signer  = $letter->printedBy;
$isLurah = $signer?->jabatan === 'lurah';
$ttdNama = $signer?->name ?? '';
$ttdNip  = $signer?->nip  ?? '';
$ttdJabatan = $jabatanLabel[$signer?->jabatan ?? ''] ?? ($signer?->jabatan ?? '');

$tanggalSurat = $p['tanggalSurat'] ?? $letter->printed_at?->format('Y-m-d') ?? date('Y-m-d');
$tanggalSuratFmt = bl_tanggalIndo($tanggalSurat);
?>


<div class="toolbar">
  <button class="btn btn-print" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
  <a class="btn btn-back" href="<?php echo e(route('arsip-surat.index')); ?>">← Kembali ke Arsip</a>
  <span class="meta"><?php echo e($letter->title); ?> &bull; <?php echo e($letter->no_surat); ?></span>
</div>

<div class="page">

  
  <table class="kop-table" cellspacing="0" cellpadding="0">
    <tbody>
      <tr>
        <td class="kop-logo-cell">
          <?php
            $logoPath = public_path('images/logo.png');
            $logoSrc  = file_exists($logoPath)
              ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
              : asset('images/logo.png');
          ?>
          <img src="<?php echo e($logoSrc); ?>" alt="Logo" class="kop-logo" />
        </td>
        <td class="kop-text">
          <div class="kop-line1">PEMERINTAH KOTA KUPANG</div>
          <div class="kop-line1">KECAMATAN KOTA LAMA</div>
          <div class="kop-line1">KELURAHAN&nbsp;&nbsp;&nbsp;FATUBESI</div>
          <div class="kop-line2">Jln.Sabu No. , Fatubesi - Kupang 85226</div>
        </td>
        <td class="kop-spacer-cell"></td>
      </tr>
    </tbody>
  </table>
  <div class="kop-rule">
    <div class="rule-1"></div>
    <div class="rule-2"></div>
  </div>

  
  <?php if($slug === 'keterangan-domisili'): ?>
  <div class="judul">
    <div class="judul-utama">SURAT KETERANGAN DOMISILI</div>
    <div class="judul-nomor">Nomor : <?php echo e($letter->no_surat); ?></div>
  </div>

  <div class="paragraf">
    Yang bertanda tangan di bawah ini Lurah Fatubesi menerangkan dengan sebenarnya bahwa:
  </div>

  <div class="data-rows">
    <div class="data-row"><div class="data-lbl">Nama</div><div class="data-sep">:</div><div class="data-val"><?php echo e(bl_titleCase($p['nama'] ?? '') ?: '____________________'); ?></div></div>
    <div class="data-row"><div class="data-lbl">NIK</div><div class="data-sep">:</div><div class="data-val"><?php echo e($p['nik'] ?? '' ?: '____________________'); ?></div></div>
    <div class="data-row">
      <div class="data-lbl">Kelahiran</div><div class="data-sep">:</div>
      <div class="data-val"><?php echo e($p['tempatLahir'] ?? ''); ?><?php echo e(isset($p['tanggalLahir']) && $p['tanggalLahir'] ? ', '.bl_tanggalIndo($p['tanggalLahir']) : ''); ?></div>
    </div>
    <div class="data-row"><div class="data-lbl">Jenis Kelamin</div><div class="data-sep">:</div><div class="data-val"><?php echo e($p['jenisKelamin'] ?? '' ?: '________'); ?></div></div>
    <div class="data-row"><div class="data-lbl">Pekerjaan</div><div class="data-sep">:</div><div class="data-val"><?php echo e($p['pekerjaan'] ?? '' ?: '________'); ?></div></div>
    <div class="data-row">
      <div class="data-lbl">Alamat</div><div class="data-sep">:</div>
      <div class="data-val">
        <?php
          $asalJalan = $p['alamatAsalJalan'] ?? '';
          $asalRt    = $p['alamatAsalRt']    ?? '';
          $asalRw    = $p['alamatAsalRw']    ?? '';
          $asalKel   = $p['alamatAsalKelurahan'] ?? '';
          $asalKec   = $p['alamatAsalKecamatan'] ?? '';
          $asalKota  = $p['alamatAsalKota']  ?? 'Kota Kupang';
          $asalProv  = $p['alamatAsalProvinsi'] ?? '';
        ?>
        <?php if($asalJalan || $asalRt || $asalRw || $asalKel || $asalKec): ?>
          <?php echo e($asalJalan ? $asalJalan.', ' : ''); ?>RT.<?php echo e($asalRt ?: '___'); ?>/RW.<?php echo e($asalRw ?: '___'); ?> Kelurahan <?php echo e($asalKel ?: '______'); ?> Kec. <?php echo e($asalKec ?: '______'); ?> <?php echo e($asalKota ?: 'Kota Kupang'); ?><?php echo e($asalProv ? ' Prov. '.$asalProv : ''); ?>

        <?php else: ?>
          RT.___/RW.___ Kelurahan ______ Kec. ______ Kota Kupang
        <?php endif; ?>
      </div>
    </div>
    <div class="data-spasi"></div>
    <div class="data-row">
      <div class="data-lbl">A l a m a t&nbsp;&nbsp;Domisili</div><div class="data-sep">:</div>
      <div class="data-val">RT.<?php echo e($p['rt'] ?? '' ?: '___'); ?>/RW.<?php echo e($p['rw'] ?? '' ?: '___'); ?> Kel. Fatubesi Kec. Kota Lama Kota Kupang</div>
    </div>
  </div>

  <div class="paragraf">
    Berdasarkan laporan dan Rekomendasi dari Ketua RT.<?php echo e(bl_pad3($p['rt'] ?? '')); ?>, yang bersangkutan
    berdomisili di RT.<?php echo e(bl_pad3($p['rt'] ?? '')); ?>/RW.<?php echo e(bl_pad3($p['rw'] ?? '')); ?> Kelurahan Fatubesi Kecamatan Kota Lama Kota Kupang.
  </div>
  <div class="paragraf">
    Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
  </div>

  <div class="ttd-wrapper">
    <div class="ttd">
      <div class="ttd-tanggal">Kupang, <?php echo e($tanggalSuratFmt); ?></div>
      <?php if($isLurah): ?>
        <div class="ttd-jabatan">Lurah Fatubesi,</div>
      <?php else: ?>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan"><?php echo e($ttdJabatan); ?></div>
      <?php endif; ?>
      <div class="ttd-space"></div>
      <div class="ttd-nama"><?php echo e($ttdNama); ?></div>
      <?php if($ttdNip): ?><div class="ttd-nip">NIP. <?php echo e($ttdNip); ?></div><?php endif; ?>
    </div>
  </div>

  
  <?php elseif($slug === 'keterangan-kelahiran'): ?>
  <div class="judul">
    <div class="judul-utama">SURAT KETERANGAN KELAHIRAN</div>
    <div class="judul-nomor">Nomor : <?php echo e($letter->no_surat); ?></div>
  </div>

  <div class="paragraf">
    Yang bertanda tangan di bawah ini, Lurah Fatubesi dengan ini menerangkan bahwa:
  </div>

  <table class="data-table" cellspacing="0" cellpadding="0">
    <tbody>
      <tr><td class="lbl">Nama</td><td class="sep">:</td><td class="val"><?php echo e(bl_titleCase($p['nama'] ?? '') ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Jenis Kelamin</td><td class="sep">:</td><td class="val"><?php echo e($p['jenisKelamin'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Agama</td><td class="sep">:</td><td class="val"><?php echo e($p['agama'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Nama Ayah</td><td class="sep">:</td><td class="val"><?php echo e(bl_titleCase($p['namaAyah'] ?? '') ?: '________'); ?></td></tr>
      <tr><td class="lbl">Nama Ibu</td><td class="sep">:</td><td class="val"><?php echo e(bl_titleCase($p['namaIbu'] ?? '') ?: '________'); ?></td></tr>
      <tr><td class="lbl">Pekerjaan</td><td class="sep">:</td><td class="val"><?php echo e($p['pekerjaan'] ?? '' ?: '________'); ?></td></tr>
      <tr>
        <td class="lbl">Alamat</td><td class="sep">:</td>
        <td class="val">
          <?php
            $alamatK = $p['alamat']     ?? '';
            $rtK     = $p['rt']         ?? '';
            $rwK     = $p['rw']         ?? '';
            $kelK    = $p['kelurahan']  ?? '';
            $kecK    = $p['kecamatan']  ?? '';
          ?>
          <?php if($alamatK || $rtK || $rwK || $kelK || $kecK): ?>
            <?php echo e($alamatK ? $alamatK.', ' : ''); ?>RT.<?php echo e($rtK ?: '___'); ?>/RW.<?php echo e($rwK ?: '___'); ?> Kelurahan <?php echo e($kelK ?: '______'); ?> Kec. <?php echo e($kecK ?: '______'); ?> Kota Kupang
          <?php else: ?>
            RT.___/RW.___ Kelurahan ______ Kec. ______ Kota Kupang
          <?php endif; ?>
        </td>
      </tr>
      <tr class="spasi"><td colspan="3" style="height:10px;"></td></tr>
    </tbody>
  </table>

  <div class="paragraf">
    Sesuai dengan laporan dari orang tuanya bahwa yang bersangkutan lahir pada:
    <table class="data-table" cellspacing="0" cellpadding="0" style="margin-top:8px;">
      <tbody>
        <tr><td class="lbl">Tanggal</td><td class="sep">:</td><td class="val"><?php echo e($p['tanggalLahir'] ? bl_tanggalIndo($p['tanggalLahir']) : '____________________'); ?></td></tr>
        <tr><td class="lbl">Di</td><td class="sep">:</td><td class="val"><?php echo e($p['tempatLahir'] ?? '' ?: '____________________'); ?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="paragraf">
    Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
  </div>

  <div class="ttd-wrapper">
    <div class="ttd">
      <div class="ttd-tanggal">Kupang, <?php echo e($tanggalSuratFmt); ?></div>
      <?php if($isLurah): ?>
        <div class="ttd-jabatan">Lurah Fatubesi,</div>
      <?php else: ?>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan"><?php echo e($ttdJabatan); ?></div>
      <?php endif; ?>
      <div class="ttd-space"></div>
      <div class="ttd-nama"><?php echo e($ttdNama); ?></div>
      <?php if($ttdNip): ?><div class="ttd-nip">NIP. <?php echo e($ttdNip); ?></div><?php endif; ?>
    </div>
  </div>

  
  <?php elseif($slug === 'keterangan-kematian'): ?>
  <div class="judul">
    <div class="judul-utama">SURAT KETERANGAN KEMATIAN</div>
    <div class="judul-nomor">Nomor : <?php echo e($letter->no_surat); ?></div>
  </div>

  <div class="paragraf">
    Yang bertanda tangan di bawah ini Lurah Fatubesi, menerangkan dengan sebenarnya bahwa :
  </div>

  <table class="data-table" cellspacing="0" cellpadding="0">
    <tbody>
      <tr><td class="lbl">Nama</td><td class="sep">:</td><td class="val"><?php echo e(bl_titleCase($p['nama'] ?? '') ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Jenis Kelamin</td><td class="sep">:</td><td class="val"><?php echo e($p['jenisKelamin'] ?? '' ?: '____________________'); ?></td></tr>
      <tr>
        <td class="lbl nowrap">Tempat / Tgl. Lahir</td><td class="sep">:</td>
        <td class="val nowrap"><?php echo e($p['tempatLahir'] ?? '________'); ?>, <?php echo e($p['tanggalLahir'] ? bl_tanggalIndo($p['tanggalLahir']) : '________'); ?></td>
      </tr>
      <tr><td class="lbl">NIK</td><td class="sep">:</td><td class="val"><?php echo e($p['nik'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Agama</td><td class="sep">:</td><td class="val"><?php echo e($p['agama'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Alamat</td><td class="sep">:</td><td class="val"><?php echo e($p['alamat'] ?? '' ?: '____________________'); ?></td></tr>
    </tbody>
  </table>

  <div class="paragraf">
    Telah meninggal dunia karena <?php echo e($p['sebabKematian'] ?? '____________________'); ?> pada :
  </div>

  <table class="data-table" cellspacing="0" cellpadding="0">
    <tbody>
      <tr><td class="lbl">Hari/Tanggal</td><td class="sep">:</td><td class="val"><?php echo e($p['tanggalMeninggal'] ? bl_tanggalIndo($p['tanggalMeninggal']) : '____________________'); ?></td></tr>
      <tr><td class="lbl">Umur</td><td class="sep">:</td><td class="val"><?php echo e($p['umur'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl">Tempat</td><td class="sep">:</td><td class="val"><?php echo e($p['tempatMeninggal'] ?? '' ?: '____________________'); ?></td></tr>
    </tbody>
  </table>

  <div class="paragraf">
    Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
  </div>

  <div class="ttd-wrapper">
    <div class="ttd">
      <div class="ttd-tanggal">Kupang, <?php echo e($tanggalSuratFmt); ?></div>
      <?php if($isLurah): ?>
        <div class="ttd-jabatan">Lurah Fatubesi,</div>
      <?php else: ?>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan"><?php echo e($ttdJabatan); ?></div>
      <?php endif; ?>
      <div class="ttd-space"></div>
      <div class="ttd-nama"><?php echo e($ttdNama); ?></div>
      <?php if($ttdNip): ?><div class="ttd-nip">NIP. <?php echo e($ttdNip); ?></div><?php endif; ?>
    </div>
  </div>

  
  <?php elseif($slug === 'keterangan-pindah'): ?>
  <div class="judul">
    <div class="judul-utama">SURAT KETERANGAN PINDAH</div>
    <div class="judul-nomor">Nomor : <?php echo e($letter->no_surat); ?></div>
  </div>

  <div class="paragraf">
    Yang bertanda tangan di bawah ini Lurah Fatubesi menerangkan dengan sebenarnya bahwa:
  </div>

  <table class="pindah-table" cellspacing="0" cellpadding="0" style="margin-top:14px;">
    <tbody>
      <tr><td class="lbl" style="padding-left:24px;">Nama</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e(bl_titleCase($p['nama'] ?? '') ?: '____________________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Jenis Kelamin</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['jenisKelamin'] ?? '' ?: '________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Tempat/Tgl. Lahir</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['tempatLahir'] ?? ''); ?><?php echo e(isset($p['tanggalLahir']) && $p['tanggalLahir'] ? ', '.bl_tanggalIndo($p['tanggalLahir']) : ''); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">NIK</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['nik'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Status Perkawinan</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['statusPerkawinan'] ?? '' ?: '________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Kewarganegaraan</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['kewarganegaraan'] ?? 'Indonesia'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Agama</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['agama'] ?? '' ?: '________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Pekerjaan</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['pekerjaan'] ?? '' ?: '________'); ?></td></tr>
      <tr><td class="lbl" style="padding-left:24px;">Alamat Asal</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['alamatAsal'] ?? '' ?: '____________________'); ?></td></tr>
    </tbody>
  </table>

  <table class="pindah-table" cellspacing="0" cellpadding="0" style="margin-top:14px;">
    <tbody>
      <tr><td class="bold" style="min-width:160px;padding-left:24px;">Pindah ke</td><td style="padding:0 10px 0 4px;">:</td><td></td></tr>
      <tr><td style="padding-left:48px;">Alamat</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['alamatTujuan'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td style="padding-left:48px;">Desa/Kelurahan</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['desaTujuan'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td style="padding-left:48px;">Kecamatan</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['kecamatanTujuan'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td style="padding-left:48px;">Kab/Kota</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['kabupatenTujuan'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td style="padding-left:48px;">Provinsi</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['provinsiTujuan'] ?? '' ?: '____________________'); ?></td></tr>
      <tr><td style="padding-left:24px;">Pada Tanggal</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($p['tanggalPindah'] ? bl_tanggalIndo($p['tanggalPindah']) : '____________________'); ?></td></tr>
      <tr><td style="padding-left:24px;">Alasan Pindah</td><td style="padding:0 10px 0 4px;">:</td><td><i><?php echo e($p['alasanPindah'] ?? '' ?: '____________________'); ?></i></td></tr>
      <?php $pengikut = $p['pengikut'] ?? []; $jml = count($pengikut); ?>
      <tr><td style="padding-left:24px;">Pengikut</td><td style="padding:0 10px 0 4px;">:</td><td><?php echo e($jml); ?> (<?php echo e(bl_terbilang($jml)); ?>) Orang</td></tr>
    </tbody>
  </table>

  <?php if(count($pengikut) > 0): ?>
  <table class="pengikut-table">
    <thead>
      <tr>
        <th style="width:30px;">No</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>Kelahiran</th>
        <th>Hubungan Keluarga</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $pengikut; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="center"><?php echo e($i + 1); ?></td>
        <td><?php echo e(bl_titleCase($item['nama'] ?? '')); ?></td>
        <td><?php echo e($item['nik'] ?? ''); ?></td>
        <td><?php echo e($item['tempatLahir'] ?? ''); ?><?php echo e(isset($item['tanggalLahir']) && $item['tanggalLahir'] ? ', '.bl_tanggalIndo($item['tanggalLahir']) : ''); ?></td>
        <td><?php echo e($item['hubungan'] ?? ''); ?></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="paragraf" style="text-align:center; margin-top:24px;">
    Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
  </div>

  <div class="ttd-wrapper-split">
    <div class="ttd">
      <div class="ttd-tanggal">Mengetahui,</div>
      <div class="ttd-jabatan">Camat Kota Lama</div>
      <div class="ttd-space"></div>
      <div>______________________</div>
    </div>
    <div class="ttd">
      <div class="ttd-tanggal">Kupang, <?php echo e($tanggalSuratFmt); ?></div>
      <?php if($isLurah): ?>
        <div class="ttd-jabatan">Lurah Fatubesi,</div>
      <?php else: ?>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan"><?php echo e($ttdJabatan); ?></div>
      <?php endif; ?>
      <div class="ttd-space"></div>
      <div class="ttd-nama"><?php echo e($ttdNama); ?></div>
      <?php if($ttdNip): ?><div class="ttd-nip">NIP. <?php echo e($ttdNip); ?></div><?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <div style="margin-top:40px; text-align:center; color:#888; font-style:italic;">
    Pratinjau tidak tersedia untuk jenis surat ini (<?php echo e($slug); ?>).
  </div>
  <?php endif; ?>

</div><!-- /page -->

</body>
</html>
<?php /**PATH C:\xampp\htdocs\manajemen-surat-kelurahan-fatubesi\resources\views/letters/pratinjau.blade.php ENDPATH**/ ?>