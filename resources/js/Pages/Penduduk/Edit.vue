<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { watch } from 'vue'

const props = defineProps({
  penduduk: Object,
})

const form = useForm({
  kode_keluarga:        props.penduduk.kode_keluarga        ?? '',
  nama_kepala_keluarga: props.penduduk.nama_kepala_keluarga ?? '',
  rt:                   props.penduduk.rt                   ?? '',
  rw:                   props.penduduk.rw                   ?? '',
  dusun:                props.penduduk.dusun                ?? '',
  alamat:               props.penduduk.alamat               ?? '',
  no_urut:              props.penduduk.no_urut              ?? '',

  nik:               props.penduduk.nik               ?? '',
  nama:              props.penduduk.nama              ?? '',
  jenis_kelamin:     props.penduduk.jenis_kelamin     ?? '',
  hubungan:          props.penduduk.hubungan          ?? '',
  tempat_lahir:      props.penduduk.tempat_lahir      ?? '',
  tanggal_lahir:     props.penduduk.tanggal_lahir     ?? '',
  usia:              props.penduduk.usia              ?? '',
  status_perkawinan: props.penduduk.status_perkawinan ?? '',
  agama:             props.penduduk.agama             ?? '',
  golongan_darah:    props.penduduk.golongan_darah    ?? '',
  kewarganegaraan:   props.penduduk.kewarganegaraan   ?? 'WNI',
  etnis:             props.penduduk.etnis             ?? '',
  pendidikan:        props.penduduk.pendidikan        ?? '',
  pekerjaan:         props.penduduk.pekerjaan         ?? '',
})

const hitungUsia = (tanggalLahir) => {
  if (!tanggalLahir) return ''
  const today = new Date()
  const birth = new Date(tanggalLahir)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age >= 0 ? age : ''
}

watch(
  () => form.tanggal_lahir,
  (value) => {
    form.usia = hitungUsia(value)
  }
)

const backToIndex = () => {
  router.visit(route('penduduk.index'))
}

const submit = () => {
  form.put(route('penduduk.update', props.penduduk.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Edit Penduduk" />

  <AppLayout>
    <div class="py-6">
      <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Data Penduduk</h1>
            <p class="mt-1 text-sm text-gray-600">
              Ubah data penduduk: <span class="font-semibold">{{ penduduk.nama }}</span>
            </p>
          </div>

          <button
            type="button"
            @click="backToIndex"
            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Kembali
          </button>
        </div>

        <div class="rounded-2xl border border-purple-100 bg-white p-6 shadow-sm">
          <form @submit.prevent="submit" class="space-y-6">

            <!-- Identitas Keluarga -->
            <div class="rounded-2xl border border-gray-200 p-4">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Identitas Keluarga</h2>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="text-xs font-semibold text-gray-700">No. KK</label>
                  <input
                    v-model="form.kode_keluarga"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Masukkan No. KK"
                  />
                  <p v-if="form.errors.kode_keluarga" class="mt-1 text-xs text-red-600">{{ form.errors.kode_keluarga }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Nama Kepala Keluarga</label>
                  <input
                    v-model="form.nama_kepala_keluarga"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Nama kepala keluarga"
                  />
                  <p v-if="form.errors.nama_kepala_keluarga" class="mt-1 text-xs text-red-600">{{ form.errors.nama_kepala_keluarga }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">RT</label>
                  <input
                    v-model="form.rt"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Contoh: 001"
                  />
                  <p v-if="form.errors.rt" class="mt-1 text-xs text-red-600">{{ form.errors.rt }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">RW</label>
                  <input
                    v-model="form.rw"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Contoh: 001"
                  />
                  <p v-if="form.errors.rw" class="mt-1 text-xs text-red-600">{{ form.errors.rw }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Dusun</label>
                  <input
                    v-model="form.dusun"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Masukkan dusun"
                  />
                  <p v-if="form.errors.dusun" class="mt-1 text-xs text-red-600">{{ form.errors.dusun }}</p>
                </div>

                <div class="sm:col-span-2">
                  <label class="text-xs font-semibold text-gray-700">Alamat</label>
                  <textarea
                    v-model="form.alamat"
                    rows="3"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Masukkan alamat"
                  ></textarea>
                  <p v-if="form.errors.alamat" class="mt-1 text-xs text-red-600">{{ form.errors.alamat }}</p>
                </div>
              </div>
            </div>

            <!-- Data Individu -->
            <div class="rounded-2xl border border-gray-200 p-4">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Data Individu</h2>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="text-xs font-semibold text-gray-700">NIK</label>
                  <input v-model="form.nik" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                  <p v-if="form.errors.nik" class="mt-1 text-xs text-red-600">{{ form.errors.nik }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Nama</label>
                  <input v-model="form.nama" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                  <p v-if="form.errors.nama" class="mt-1 text-xs text-red-600">{{ form.errors.nama }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">JK</label>
                  <select v-model="form.jenis_kelamin" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400">
                    <option value="">Pilih JK</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                  <p v-if="form.errors.jenis_kelamin" class="mt-1 text-xs text-red-600">{{ form.errors.jenis_kelamin }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Hubungan dengan Kepala Keluarga</label>
                  <select v-model="form.hubungan" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400">
                    <option value="">Pilih hubungan</option>
                    <option value="Kepala Keluarga">Kepala Keluarga</option>
                    <option value="Istri">Istri</option>
                    <option value="Suami">Suami</option>
                    <option value="Anak">Anak</option>
                    <option value="Menantu">Menantu</option>
                    <option value="Cucu">Cucu</option>
                    <option value="Orang Tua">Orang Tua</option>
                    <option value="Mertua">Mertua</option>
                    <option value="Famili Lain">Famili Lain</option>
                    <option value="Pembantu">Pembantu</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                  <p v-if="form.errors.hubungan" class="mt-1 text-xs text-red-600">{{ form.errors.hubungan }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                  <input v-model="form.tempat_lahir" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                  <input v-model="form.tanggal_lahir" type="date" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Usia</label>
                  <input :value="form.usia" type="text" readonly class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Status</label>
                  <select v-model="form.status_perkawinan" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400">
                    <option value="">Pilih status</option>
                    <option value="Belum Kawin">Belum Kawin</option>
                    <option value="Kawin">Kawin</option>
                    <option value="Cerai Hidup">Cerai Hidup</option>
                    <option value="Cerai Mati">Cerai Mati</option>
                  </select>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Agama</label>
                  <input v-model="form.agama" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Golongan Darah</label>
                  <select v-model="form.golongan_darah" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400">
                    <option value="">Pilih golongan darah</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                  </select>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Kewarganegaraan</label>
                  <input v-model="form.kewarganegaraan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Etnis/Suku</label>
                  <input v-model="form.etnis" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Pendidikan</label>
                  <input v-model="form.pendidikan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                  <input v-model="form.pekerjaan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400" />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="backToIndex"
                class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Batal
              </button>

              <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 disabled:opacity-50"
              >
                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
