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

const formatTanggalSurat = () => {
  try {
    const t = form?.tanggalSurat || null
    if (t) return tanggalIndo(t)
    const nowIso = new Date().toISOString().split('T')[0]
    return tanggalIndo(nowIso)
  } catch (e) {
    return ''
  }
}

const terbilang = (n) => {
  const map = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh']
  return map[n] || n
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

// Yang berwenang menandatangani surat: Lurah, Sekretaris Lurah, dan Kepala Seksi.
const SIGNER_JABATAN = ['lurah', 'sekretaris', 'kasie_pelayanan_masyarakat', 'kasie_pem_trantib_umum']
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
  <div class="print-area mt-4 rounded-xl border border-gray-200 bg-white p-6 text-[14px] leading-6">

    <table class="w-full text-center">
        <tbody>
            <tr>
            <td class="w-24 align-middle">
                <img :src="asset('images/logo_kop.png')" class="w-20 mx-auto" style="mix-blend-mode: multiply;" />
            </td>
            <td class="text-center">
                <div class="font-bold text-[16px] uppercase">
                Pemerintah Kota Kupang
                </div>
                <div class="font-bold text-[16px] uppercase">
                Kecamatan Kota Lama
                </div>
                <div class="font-bold text-[16px] uppercase">
                Kelurahan Fatubesi
                </div>
                <div class="text-[12px]">
                Jln. Sabu No., Fatubesi - Kupang 85226
                </div>
            </td>
            <td class="w-24"></td>
            </tr>
        </tbody>
    </table>

    <hr class="my-2 border-black" />

    <div class="text-center mt-4">
      <div class="font-bold uppercase underline text-[15px]">
        Surat Keterangan Pindah
      </div>
      <div>
        Nomor : {{ form.noSurat || form.nomor_surat }}
      </div>
    </div>

    <div class="mt-6">
      <p>
        Yang bertanda tangan di bawah ini Lurah Fatubesi menerangkan dengan sebenarnya bahwa:
      </p>

      <table class="mt-3">
        <tbody>
          <tr>
            <td class="pr-4">Nama</td>
            <td class="pr-2">:</td>
            <td>{{ toTitleCase(form.nama) }}</td>
          </tr>
          <tr>
            <td>Jenis Kelamin</td>
            <td>:</td>
            <td>{{ form.jenisKelamin }}</td>
          </tr>
          <tr>
            <td>Tempat/Tgl. Lahir</td>
            <td>:</td>
            <td class="whitespace-nowrap">
              {{ form.tempatLahir }}, {{ tanggalIndo(form.tanggalLahir) }}
            </td>
          </tr>
          <tr>
            <td>NIK</td>
            <td>:</td>
            <td>{{ form.nik }}</td>
          </tr>
          <tr>
            <td>Status Perkawinan</td>
            <td>:</td>
            <td>{{ form.statusPerkawinan }}</td>
          </tr>
          <tr>
            <td>Kewarganegaraan</td>
            <td>:</td>
            <td>{{ form.kewarganegaraan }}</td>
          </tr>
          <tr>
            <td>Agama</td>
            <td>:</td>
            <td>{{ form.agama }}</td>
          </tr>
          <tr>
            <td>Pekerjaan</td>
            <td>:</td>
            <td>{{ form.pekerjaan }}</td>
          </tr>
          <tr>
            <td>Alamat Asal</td>
            <td>:</td>
            <td>{{ form.alamatAsal }}</td>
          </tr>
        </tbody>
      </table>

      <table class="mt-4">
        <tbody>
          <tr>
            <td class="font-bold pr-4">Pindah ke</td>
            <td>:</td>
            <td></td>
          </tr>
          <tr>
            <td class="pl-6">Alamat</td>
            <td>:</td>
            <td>{{ form.alamatTujuan }}</td>
          </tr>
          <tr>
            <td class="pl-6">Desa/Kelurahan</td>
            <td>:</td>
            <td>{{ form.desaTujuan }}</td>
          </tr>
          <tr>
            <td class="pl-6">Kecamatan</td>
            <td>:</td>
            <td>{{ form.kecamatanTujuan }}</td>
          </tr>
          <tr>
            <td class="pl-6">Kab/Kota</td>
            <td>:</td>
            <td>{{ form.kabupatenTujuan }}</td>
          </tr>
          <tr>
            <td class="pl-6">Provinsi</td>
            <td>:</td>
            <td>{{ form.provinsiTujuan }}</td>
          </tr>
          <tr>
            <td>Pada Tanggal</td>
            <td>:</td>
            <td>{{ tanggalIndo(form.tanggalPindah) }}</td>
          </tr>
          <tr>
            <td>Alasan Pindah</td>
            <td>:</td>
            <td><i>{{ form.alasanPindah }}</i></td>
          </tr>
          <tr>
            <td>Pengikut</td>
            <td>:</td>
            <td>
              {{ form.pengikut?.length || 0 }}
              ({{ terbilang(form.pengikut?.length || 0) }}) Orang
            </td>
          </tr>
        </tbody>
      </table>

      <table class="mt-4 w-full border border-black border-collapse text-[13px]">
        <thead>
          <tr>
            <th class="border border-black px-2 py-1">No</th>
            <th class="border border-black px-2 py-1">Nama</th>
            <th class="border border-black px-2 py-1">NIK</th>
            <th class="border border-black px-2 py-1">Kelahiran</th>
            <th class="border border-black px-2 py-1">Hubungan Keluarga</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in (form.pengikut || [])" :key="index">
            <td class="border border-black text-center px-2 py-1">
              {{ index + 1 }}
            </td>
            <td class="border border-black px-2 py-1">
              {{ toTitleCase(item.nama) }}
            </td>
            <td class="border border-black px-2 py-1">
              {{ item.nik }}
            </td>
            <td class="border border-black px-2 py-1">
              {{ item.tempatLahir }}<span v-if="item.tempatLahir && item.tanggalLahir">, </span>{{ item.tanggalLahir ? tanggalIndo(item.tanggalLahir) : '' }}
            </td>
            <td class="border border-black px-2 py-1">
              {{ item.hubungan }}
            </td>
          </tr>
        </tbody>
      </table>

      <p class="mt-6 text-center">
        Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
      </p>

      <div class="mt-10 flex justify-between items-stretch">
        <div class="ttd" style="display:flex; flex-direction:column;">
          <div class="ttd-tanggal">Mengetahui,</div>
          <div class="ttd-jabatan">Camat Kota Lama</div>
          <div style="flex:1;"></div>
          <div>______________________</div>
          <div class="ttd-nip">&nbsp;</div>
        </div>

        <div class="ttd" style="display:flex; flex-direction:column;" v-if="authUser">
          <div class="ttd-tanggal">Kupang, {{ formatTanggalSurat() }}</div>
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
        <div class="ttd print:hidden" style="display:flex; flex-direction:column;" v-else>
          <p class="text-xs italic text-gray-400">Pilih penanda tangan (Lurah/Sekretaris/Kepala Seksi) di form sebelum mencetak.</p>
        </div>
      </div>

    </div>
  </div>
</template>

<style>
.ttd {
  width: 320px;
  text-align: center;
  line-height: 1.6;
}
.ttd-tanggal { margin-bottom: 4px; }
.ttd-jabatan { margin-bottom: 6px; }
.ttd-jabatan + .ttd-jabatan { margin-bottom: 0; }
.ttd-nama { font-weight: bold; text-decoration: underline; margin-bottom: 4px; white-space: nowrap; }
.ttd-nip { font-size: 12pt; }
</style>