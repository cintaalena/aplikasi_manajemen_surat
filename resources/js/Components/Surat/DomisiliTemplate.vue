<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { toTitleCase } from '@/utils/textFormat'
import { useAsset } from '@/composables/useAsset'

const { asset } = useAsset()

const props = defineProps({
  form: { type: Object, required: true },
  tanggalIndo: { type: Function, required: true },
  signer: { type: Object, default: null },
})
const { form, tanggalIndo } = props

const pad3 = (v) => {
  const s = String(v ?? '').replace(/\D/g, '')
  if (!s) return '___'
  return s.padStart(3, '0').slice(-3)
}

const formatTanggalSurat = () => {
  try {
    const t = (form && form.tanggalSurat) ? form.tanggalSurat : null
    if (t) return tanggalIndo(t)
    const nowIso = new Date().toISOString().split('T')[0]
    return tanggalIndo(nowIso)
  } catch (e) {
    return ''
  }
}

const jabatanLabel = {
  lurah:                             'Lurah Fatubesi',
  sekretaris:                        'Sekretaris',
  kasie_pelayanan_masyarakat:        'Kasie Pelayanan Masyarakat',
  kasie_pem_trantib_umum:            'Kasie PEM & Trantibum',
  pengelola_pemberdayaan_masyarakat: 'Pengelola Pember. Masy. & Kelembagaan',
  pengadministrasi_perkantoran:      'Pengadministrasi Perkantoran',
  penata_layanan_operasional:        'Penata Layanan Operasional',
}

const authUser = computed(() => props.signer ?? usePage().props.auth?.user ?? {})
const isLurah  = computed(() => authUser.value.jabatan === 'lurah')
const ttdNama  = computed(() => authUser.value.name ?? '')
const ttdNip   = computed(() => authUser.value.nip  ?? '')
const ttdJabatanLabel = computed(() => jabatanLabel[authUser.value.jabatan] ?? authUser.value.jabatan ?? '')
</script>

<template>
  <!-- Area print (template asli) -->
  <div class="print-area mt-4 rounded-xl border border-gray-200 bg-white p-6">
    <!-- KOP -->
    <table class="kop" cellspacing="0" cellpadding="0">
      <tbody>
        <tr>
          <td class="kop-logo">
            <img src="/images/logo_kop.png" alt="Logo" class="logo" />
          </td>
          <td class="kop-text">
            <div class="kop-line1">PEMERINTAH KOTA KUPANG</div>
            <div class="kop-line1">KECAMATAN KOTA LAMA</div>
            <div class="kop-line1">KELURAHAN&nbsp;&nbsp;&nbsp;FATUBESI</div>
            <div class="kop-line2">Jln.Sabu No. , Fatubesi - Kupang 85226</div>
          </td>
          <td class="kop-spacer"></td>
        </tr>
      </tbody>
    </table>

    <div class="kop-rule">
      <div class="rule-1"></div>
      <div class="rule-2"></div>
    </div>

    <!-- JUDUL -->
    <div class="judul">
      <div class="judul-utama">SURAT KETERANGAN DOMISILI</div>
      <div class="judul-nomor">Nomor : {{ form.noSurat || '71/Kel.Ftbs.475/X/2025' }}</div>
    </div>

    <!-- PEMBUKA -->
    <div class="paragraf">
      Yang bertanda tangan di bawah ini Lurah Fatubesi menerangkan dengan sebenarnya bahwa:
    </div>

    <!-- DATA -->
    <table class="data-table" cellspacing="0" cellpadding="0" style="margin-top:14px; width:100%;">
      <tbody>
        <tr>
          <td class="lbl">Nama</td><td class="sep">:</td>
          <td class="val">{{ toTitleCase(form.nama) || '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">NIK</td><td class="sep">:</td>
          <td class="val">{{ form.nik || '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Kelahiran</td><td class="sep">:</td>
          <td class="val">{{ form.tempatLahir || '________' }}{{ form.tanggalLahir ? ', ' + tanggalIndo(form.tanggalLahir) : '' }}</td>
        </tr>
        <tr>
          <td class="lbl">Jenis Kelamin</td><td class="sep">:</td>
          <td class="val">{{ form.jenisKelamin || '________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Pekerjaan</td><td class="sep">:</td>
          <td class="val">{{ form.pekerjaan || '________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Alamat</td><td class="sep">:</td>
          <td class="val">
            <span v-if="form.alamatAsalJalan || form.alamatAsalRt || form.alamatAsalRw || form.alamatAsalKelurahan || form.alamatAsalKecamatan">
              <span v-if="form.alamatAsalJalan">{{ form.alamatAsalJalan }}, </span>RT.{{ form.alamatAsalRt || '___' }}/RW.{{ form.alamatAsalRw || '___' }} Kelurahan {{ form.alamatAsalKelurahan || '______' }} Kec. {{ form.alamatAsalKecamatan || '______' }}{{ form.alamatAsalKota ? ' ' + form.alamatAsalKota : ' Kota Kupang' }}{{ form.alamatAsalProvinsi ? ' Prov. ' + form.alamatAsalProvinsi : '' }}
            </span>
            <span v-else>RT.___/RW.___ Kelurahan ______ Kec. ______ Kota Kupang</span>
          </td>
        </tr>
        <tr><td colspan="3" style="height:10px;"></td></tr>
        <tr>
          <td class="lbl" style="white-space:nowrap;">A l a m a t&nbsp;&nbsp;Domisili</td><td class="sep">:</td>
          <td class="val">RT.{{ form.rt || '___' }}/RW.{{ form.rw || '___' }} Kel. Fatubesi Kec. Kota Lama Kota Kupang</td>
        </tr>
      </tbody>
    </table>

    <!-- ISI -->
    <div class="paragraf paragraf-isi">
    Berdasarkan laporan dan Rekomendasi dari Ketua RT.{{ pad3(form.rt) }}, yang bersangkutan
    berdomisili di RT.{{ pad3(form.rt) }}/RW.{{ pad3(form.rw) }} Kelurahan Fatubesi Kecamatan Kota Lama Kota
    Kupang.
    </div>

    <div class="paragraf paragraf-isi">
      Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan
      sebagaimana mestinya.
    </div>

    <!-- TTD -->
  <div class="ttd-wrapper">
    <div class="ttd">
      <div class="ttd-tanggal">Kupang, {{ formatTanggalSurat() || '1 Oktober 2025' }}</div>
      <template v-if="isLurah">
        <div class="ttd-jabatan">Lurah Fatubesi,</div>
      </template>
      <template v-else>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan">{{ ttdJabatanLabel }}</div>
      </template>
      <div class="ttd-space"></div>
      <div class="ttd-nama">{{ ttdNama }}</div>
      <div class="ttd-nip" v-if="ttdNip">NIP. {{ ttdNip }}</div>
    </div>
  </div>
  </div>
</template>

<style>
.print-area{
  font-family: "Bookman Old Style", "Bookman", "Palatino Linotype", serif;
  font-size: 12pt;
  color: #000;
}

/* KOP */
.kop{
  width: 100%;
  table-layout: fixed; /* bikin kontrol lebar kolom lebih stabil */
}
.kop-logo{
  width: 140px;          /* NAIKKAN ini, jangan kecil */
  vertical-align: middle;
  text-align: left;
}
.logo{
  width: 120px;          /* realistis untuk kop */
  height: auto;
  display: block;
  margin: 0;
  mix-blend-mode: multiply;
}
.kop-spacer{
  width: 140px;          /* samakan dengan kolom logo biar header center */
}

.kop-text{
  text-align: center;         
  font-family: Arial, Helvetica, sans-serif;
}

.kop-line1{
  font-size: 16pt;
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: 0.2px;
}
.kop-line2{
  font-size: 10pt;
  font-weight: 400;
  line-height: 1.2;
  margin-top: 4px;
}

.kop-rule{ margin-top:6px; }
.rule-1{ border-top:2px solid #000; }
.rule-2{ border-top:1px solid #000; margin-top:2px; }

/* Judul */
.judul{ text-align:center; margin-top:14px; }
.judul-utama{
  font-weight: bold;
  text-decoration: underline;
  font-size: 13pt;
}
.judul-nomor{ margin-top:2px; }

/* Paragraf */
.paragraf{ margin-top:14px; line-height:1.6; }

/* Data - table layout (reliable) */
.data-table{
  margin-top: 14px;
  border-collapse: collapse;
  width: 100%;
  table-layout: fixed;
}
.data-table td{
  vertical-align: top;
  line-height: 1.7;
}
.data-table .lbl{
  white-space: nowrap;
  padding-right: 4px;
  width: 200px;
  padding-left: 48px;
}
.data-table .sep{
  padding-right: 10px;
  width: 20px;
}
.data-table .val{
  white-space: normal;
  word-break: break-word;
  overflow-wrap: break-word;
}

/* TTD */
.ttd-wrapper{
  margin-top: 40px;
  text-align: right;
}
.ttd{
  display: inline-block;
  text-align: center;
  line-height: 1.7;
  min-width: 240px;
}
.ttd-tanggal{
  margin-bottom: 4px;
}
.ttd-jabatan{
  margin-bottom: 4px;
}
.ttd-space{
  height: 70px;
}

.ttd-nama{
  font-weight: bold;
  text-decoration: underline;
  white-space: nowrap;
}
.ttd-nip{
  font-size: 12pt;
}

/* PRINT */

@media print {
  .print-area{
    position: static !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: #fff !important;
  }

  html, body{
    height: auto !important;
    overflow: visible !important;
  }
}
</style>
