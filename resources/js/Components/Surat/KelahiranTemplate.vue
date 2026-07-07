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

// Hanya Lurah dan Kepala Seksi yang berwenang menandatangani surat.
const SIGNER_JABATAN = ['lurah', 'kasie_pelayanan_masyarakat', 'kasie_pem_trantib_umum']
const authUser = computed(() => {
  if (props.signer) return props.signer
  const u = usePage().props.auth?.user ?? {}
  return SIGNER_JABATAN.includes(u.jabatan) ? u : null
})
const isLurah  = computed(() => authUser.value?.jabatan === 'lurah')
const ttdNama  = computed(() => authUser.value?.name ?? '')
const ttdNip   = computed(() => authUser.value?.nip  ?? '')
const ttdJabatanLabel = computed(() => jabatanLabel[authUser.value?.jabatan] ?? authUser.value?.jabatan ?? '')
</script>

<template>
  <div class="print-area mt-4 rounded-xl border border-gray-200 bg-white p-6">
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

    <div class="judul">
      <div class="judul-utama">SURAT KETERANGAN KELAHIRAN</div>
      <div class="judul-nomor">Nomor : {{ form.noSurat || '71/Kel.Ftbs.475/X/2025' }}</div>
    </div>

    <div class="paragraf">
      Yang bertanda tangan di bawah ini, Lurah Fatubesi dengan ini menerangkan bahwa::
    </div>

    <table class="data" cellspacing="0" cellpadding="0">
      <tbody>
        <tr>
          <td class="lbl">Nama</td><td class="sep">:</td>
          <td class="val">{{ toTitleCase(form.nama) || '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Jenis Kelamin</td><td class="sep">:</td>
          <td class="val">{{ form.jenisKelamin || '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Agama</td><td class="sep">:</td>
          <td class="val">{{ form.agama || '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Nama Ayah</td><td class="sep">:</td>
          <td class="val">{{ toTitleCase(form.namaAyah) || '________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Nama Ibu</td><td class="sep">:</td>
          <td class="val">{{ toTitleCase(form.namaIbu) || '________' }}</td>
        </tr>
         <tr>
          <td class="lbl">Pekerjaan</td><td class="sep">:</td>
          <td class="val">{{ form.pekerjaan || '________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Alamat</td><td class="sep">:</td>
          <td class="val">
            <span v-if="form.alamat || form.rt || form.rw || form.kelurahan || form.kecamatan">
              <span v-if="form.alamat">{{ form.alamat }}, </span>RT.{{ form.rt || '___' }}/RW.{{ form.rw || '___' }} Kelurahan {{ form.kelurahan || '______' }} Kec. {{ form.kecamatan || '______' }} Kota Kupang
            </span>
            <span v-else>RT.___/RW.___ Kelurahan ______ Kec. ______ Kota Kupang</span>
          </td>
        </tr>

        <tr><td colspan="3" class="spasi"></td></tr>

      </tbody>
    </table>

    <div class="paragraf paragraf-isi">
    Sesuai dengan laporan dari orang tuanya bahwa yang bersangkutan lahir pada::
    <table class="data" cellspacing="0" cellpadding="0">
        <tbody>
        <tr>
          <td class="lbl">Tanggal</td><td class="sep">:</td>
          <td class="val">{{ form.tanggalLahir ? tanggalIndo(form.tanggalLahir) : '____________________' }}</td>
        </tr>
        <tr>
          <td class="lbl">Di</td><td class="sep">:</td>
          <td class="val">{{ form.tempatLahir || '____________________' }}</td>
        </tr>
        </tbody>
    </table>
    </div>

    <div class="paragraf paragraf-isi">
      Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan
      sebagaimana mestinya.
    </div>

<div class="ttd-wrapper">
    <div class="ttd" v-if="authUser">
      <div class="ttd-tanggal">Kupang, {{ formatTanggalSurat() || '1 Oktober 2025' }}</div>
      <template v-if="isLurah">
        <div class="ttd-jabatan" style="margin-bottom: 65px;">Lurah Fatubesi,</div>
      </template>
      <template v-else>
        <div class="ttd-jabatan">An. Lurah Fatubesi,</div>
        <div class="ttd-jabatan" style="margin-bottom: 65px;">{{ ttdJabatanLabel }}</div>
      </template>
      <div class="ttd-nama">{{ ttdNama }}</div>
      <div class="ttd-nip" v-if="ttdNip">NIP. {{ ttdNip }}</div>
    </div>
    <div class="ttd print:hidden" v-else>
      <p class="text-xs italic text-gray-400">Pilih penanda tangan (Lurah/Kepala Seksi) di form sebelum mencetak.</p>
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

.kop{
  width: 100%;
  table-layout: fixed;
}
.kop-logo{
  width: 140px;
  vertical-align: middle;
  text-align: left;
}
.logo{
  width: 120px;
  height: auto;
  display: block;
  margin: 0;
  mix-blend-mode: multiply;
}
.kop-spacer{
  width: 140px;
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

.judul{ text-align:center; margin-top:14px; }
.judul-utama{
  font-weight: bold;
  text-decoration: underline;
  font-size: 13pt;
}
.judul-nomor{ margin-top:2px; }

.paragraf{ margin-top:14px; line-height:1.6; }

.data{ width:100%; table-layout:fixed; margin-top:14px; line-height:1.7; }
.lbl{ width:170px; padding-left:48px; vertical-align:top; white-space: nowrap; }
.sep{ width:12px; vertical-align:top; white-space: nowrap; }
.val{ vertical-align:top; white-space: normal; word-break: break-word; overflow-wrap: break-word; word-wrap: break-word; }
.subline{ margin-left:0; }
.spasi{ height:10px; }

.ttd-wrapper{
  width: 100%;
  display: flex;
  justify-content: flex-end;  
  margin-top: 40px;
}
.ttd{
  width: 320px;
  text-align: center;
  line-height: 1.6;
}
.ttd-tanggal{
  margin-bottom: 6px;
}
.ttd-jabatan{
  margin-bottom: 6px; 
}

.ttd-jabatan + .ttd-jabatan{
  margin-bottom: 65px;
}

.ttd-nama{
  font-weight: bold;
  text-decoration: underline;
  margin-bottom: 4px;
  white-space: nowrap;
}
.ttd-nip{
  font-size: 12pt;
}

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
