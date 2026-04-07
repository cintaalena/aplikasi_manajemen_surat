<script setup>
const { form, tanggalIndo } = defineProps({
  form: { type: Object, required: true },
  tanggalIndo: { type: Function, required: true },
})

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

// optional: ubah angka jadi teks sederhana
const terbilang = (n) => {
  const map = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh']
  return map[n] || n
}
</script>

<template>
  <div class="print-area mt-4 rounded-xl border border-gray-200 bg-white p-6 text-[14px] leading-6">

    <!-- ================= KOP ================= -->
    <table class="w-full text-center">
        <tbody>
            <tr>
            <td class="w-20">
                <img src="/images/logo.png" class="w-16 mx-auto" />
            </td>
            <td>
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
            </tr>
        </tbody>
    </table>

    <hr class="my-2 border-black" />

    <!-- ================= JUDUL ================= -->
    <div class="text-center mt-4">
      <div class="font-bold uppercase underline text-[15px]">
        Surat Keterangan Pindah
      </div>
      <div>
        Nomor : {{ form.noSurat || form.nomor_surat }}
      </div>
    </div>

    <!-- ================= ISI ================= -->
    <div class="mt-6">
      <p>
        Yang bertanda tangan di bawah ini Lurah Fatubesi menerangkan dengan sebenarnya bahwa:
      </p>

      <!-- DATA UTAMA -->
      <table class="mt-3">
        <tbody>
          <tr>
            <td class="pr-4">Nama</td>
            <td class="pr-2">:</td>
            <td>{{ form.nama }}</td>
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

      <!-- PINDAH KE -->
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

      <!-- TABEL PENGIKUT -->
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
              {{ item.nama }}
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

      <!-- PENUTUP -->
      <p class="mt-6 text-center">
        Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
      </p>

      <!-- TTD -->
      <div class="mt-10 flex justify-between">
        <div class="text-center">
          Mengetahui,<br />
          Camat Kota Lama
          <br /><br /><br />
          ______________________
        </div>

        <div class="text-center">
          Kupang, {{ formatTanggalSurat() }}<br />
          An. Lurah Fatubesi<br />
          Kasi Pem & Trantibum
          <br /><br /><br />
          <b>Yerry Agustinus Balu, SH</b><br />
          NIP. 19840803 201001 1 006
        </div>
      </div>

    </div>
  </div>
</template>