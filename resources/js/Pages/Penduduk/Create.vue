<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useFormArrowNav } from '@/composables/useFormArrowNav'

const form = useForm({
  is_kepala_keluarga: false,
  kode_keluarga: '',
  kepala_keluarga_kode: '',

  rt: '',
  rw: '',
  dusun: '',
  alamat: '',

  nik: '',
  nama: '',
  jenis_kelamin: '',
  hubungan: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  usia: '',
  status_perkawinan: '',
  agama: '',
  golongan_darah: '',
  kewarganegaraan: 'WNI',
  etnis: '',
  pendidikan: '',
  pekerjaan: '',
})

const headQuery = ref('')
const kepalaKeluargaResults = ref([])
const searchingKepala = ref(false)
const selectedKepala = ref(null)

let searchTimeout = null

const backToIndex = () => {
  router.visit(route('penduduk.index'))
}

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

watch(
  () => form.is_kepala_keluarga,
  (isKepala) => {
    form.clearErrors()

    if (isKepala) {
      form.kepala_keluarga_kode = ''
      form.hubungan = 'Kepala Keluarga'
      headQuery.value = ''
      kepalaKeluargaResults.value = []
      selectedKepala.value = null

      form.rt = ''
      form.rw = ''
      form.dusun = ''
      form.alamat = ''
    } else {
      form.kepala_keluarga_kode = ''
      form.hubungan = ''
      selectedKepala.value = null

      form.rt = ''
      form.rw = ''
      form.dusun = ''
      form.alamat = ''
    }
  },
  { immediate: true }
)

watch(headQuery, (value) => {
  if (form.is_kepala_keluarga) return

  clearTimeout(searchTimeout)

  if (!value || value.trim().length < 2) {
    kepalaKeluargaResults.value = []
    return
  }

  searchTimeout = setTimeout(async () => {
    searchingKepala.value = true

    try {
      const res = await fetch(route('penduduk.searchKepalaKeluarga') + `?q=${encodeURIComponent(value)}`, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      })

      if (!res.ok) throw new Error('Gagal mencari kepala keluarga')

      kepalaKeluargaResults.value = await res.json()
    } catch (error) {
      console.error(error)
      kepalaKeluargaResults.value = []
    } finally {
      searchingKepala.value = false
    }
  }, 300)
})

const pilihKepalaKeluarga = (item) => {
  selectedKepala.value = item
  form.kepala_keluarga_kode = item.kode_keluarga
  headQuery.value = item.nama_kepala_keluarga
  kepalaKeluargaResults.value = []
}

const previewNoUrut = computed(() => {
  if (form.is_kepala_keluarga) return 1
  if (selectedKepala.value?.next_no_urut) return selectedKepala.value.next_no_urut
  return ''
})

const submitPenduduk = () => {
  form.post(route('penduduk.store'), {
    preserveScroll: true,
  })
}

const toTitleCase = (str) => {
  if (!str) return str
  return String(str).replace(/\S+/g, (word) => word.charAt(0).toUpperCase() + word.slice(1))
}

const { handleFormArrowNav } = useFormArrowNav()
</script>

<template>
  <Head title="Tambah Penduduk" />

  <AppLayout>
    <div class="py-6">
      <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Data Penduduk</h1>
            <p class="mt-1 text-sm text-gray-600">
              Isi data penduduk. Jika bukan kepala keluarga, pilih kepala keluarga yang sudah ada.
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

        <div class="rounded-2xl border border-green-100 bg-white p-6 shadow-sm">
          <form @submit.prevent="submitPenduduk" @keydown="handleFormArrowNav" class="space-y-6">
            <div class="rounded-2xl border border-gray-200 p-4">
              <label class="flex items-center gap-3">
                <input
                  v-model="form.is_kepala_keluarga"
                  type="checkbox"
                  class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                />
                <span class="text-sm font-semibold text-gray-800">
                  Data ini adalah Kepala Keluarga
                </span>
              </label>
            </div>

            <div v-if="form.is_kepala_keluarga" class="rounded-2xl border border-gray-200 p-4">
  <h2 class="mb-4 text-lg font-semibold text-gray-900">Identitas Keluarga</h2>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label class="text-xs font-semibold text-gray-700">No. KK</label>
      <input
        v-model="form.kode_keluarga"
        type="text"
        class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
        placeholder="Masukkan No. KK"
      />
      <p v-if="form.errors.kode_keluarga" class="mt-1 text-xs text-red-600">
        {{ form.errors.kode_keluarga }}
      </p>
    </div>

    <div>
      <label class="text-xs font-semibold text-gray-700">RT</label>
      <input
        v-model="form.rt"
        type="text"
        class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
        placeholder="Contoh: 001"
      />
      <p v-if="form.errors.rt" class="mt-1 text-xs text-red-600">
        {{ form.errors.rt }}
      </p>
    </div>

    <div>
      <label class="text-xs font-semibold text-gray-700">RW</label>
      <input
        v-model="form.rw"
        type="text"
        class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
        placeholder="Contoh: 001"
      />
      <p v-if="form.errors.rw" class="mt-1 text-xs text-red-600">
        {{ form.errors.rw }}
      </p>
    </div>

        <div>
          <label class="text-xs font-semibold text-gray-700">Dusun</label>
          <input
            v-model="form.dusun"
            type="text"
            class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
            placeholder="Masukkan dusun"
            @blur="form.dusun = toTitleCase(form.dusun)"
          />
          <p v-if="form.errors.dusun" class="mt-1 text-xs text-red-600">
            {{ form.errors.dusun }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label class="text-xs font-semibold text-gray-700">Alamat</label>
          <textarea
            v-model="form.alamat"
            rows="3"
            class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
            placeholder="Masukkan alamat"
            @blur="form.alamat = toTitleCase(form.alamat)"
          ></textarea>
          <p v-if="form.errors.alamat" class="mt-1 text-xs text-red-600">
            {{ form.errors.alamat }}
          </p>
        </div>
      </div>
    </div>

            <div v-else class="rounded-2xl border border-gray-200 p-4">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Pilih Kepala Keluarga</h2>

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama Kepala Keluarga</label>
                <input
                  v-model="headQuery"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400"
                  placeholder="Ketik nama kepala keluarga / No. KK"
                  autocomplete="off"
                />

                <p v-if="form.errors.kepala_keluarga_kode" class="mt-1 text-xs text-red-600">
                  {{ form.errors.kepala_keluarga_kode }}
                </p>

                <div v-if="searchingKepala" class="mt-2 text-xs text-gray-500">
                  Mencari kepala keluarga...
                </div>

                <div
                  v-if="kepalaKeluargaResults.length"
                  class="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in kepalaKeluargaResults"
                    :key="item.kode_keluarga"
                    type="button"
                    @click="pilihKepalaKeluarga(item)"
                    class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-green-50"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama_kepala_keluarga }}</div>
                    <div class="text-xs text-gray-600">
                      No. KK: {{ item.kode_keluarga }} • RT/RW: {{ item.rt }}/{{ item.rw }} • Dusun: {{ item.dusun || '-' }}
                    </div>
                    <div class="text-xs text-gray-500">{{ item.alamat || '-' }}</div>
                  </button>
                </div>
              </div>

              <div v-if="selectedKepala" class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
                <div><span class="font-semibold">Kepala Keluarga:</span> {{ selectedKepala.nama_kepala_keluarga }}</div>
                <div><span class="font-semibold">No. KK:</span> {{ selectedKepala.kode_keluarga }}</div>
                <div><span class="font-semibold">RT/RW:</span> {{ selectedKepala.rt }}/{{ selectedKepala.rw }}</div>
                <div><span class="font-semibold">Dusun:</span> {{ selectedKepala.dusun || '-' }}</div>
                <div><span class="font-semibold">Alamat:</span> {{ selectedKepala.alamat || '-' }}</div>
                <div><span class="font-semibold">No. Urut Baru:</span> {{ previewNoUrut }}</div>
              </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-4">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Data Individu</h2>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="text-xs font-semibold text-gray-700">NIK</label>
                  <input v-model="form.nik" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" />
                  <p v-if="form.errors.nik" class="mt-1 text-xs text-red-600">{{ form.errors.nik }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Nama</label>
                  <input v-model="form.nama" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.nama = toTitleCase(form.nama)" />
                  <p v-if="form.errors.nama" class="mt-1 text-xs text-red-600">{{ form.errors.nama }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">JK</label>
                  <select v-model="form.jenis_kelamin" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400">
                    <option value="">Pilih JK</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                  <p v-if="form.errors.jenis_kelamin" class="mt-1 text-xs text-red-600">{{ form.errors.jenis_kelamin }}</p>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Hubungan dengan Kepala Keluarga</label>
                  <select
                    v-model="form.hubungan"
                    :disabled="form.is_kepala_keluarga"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400 disabled:bg-gray-50"
                  >
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
                  <input v-model="form.tempat_lahir" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.tempat_lahir = toTitleCase(form.tempat_lahir)" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                  <input v-model="form.tanggal_lahir" type="date" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Usia</label>
                  <input :value="form.usia" type="text" readonly class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Status</label>
                  <select v-model="form.status_perkawinan" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400">
                    <option value="">Pilih status</option>
                    <option value="Belum Kawin">Belum Kawin</option>
                    <option value="Kawin">Kawin</option>
                    <option value="Cerai Hidup">Cerai Hidup</option>
                    <option value="Cerai Mati">Cerai Mati</option>
                  </select>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Agama</label>
                  <input v-model="form.agama" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.agama = toTitleCase(form.agama)" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Golongan Darah</label>
                  <select v-model="form.golongan_darah" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400">
                    <option value="">Pilih golongan darah</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                  </select>
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Kewarganegaraan</label>
                  <input v-model="form.kewarganegaraan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.kewarganegaraan = toTitleCase(form.kewarganegaraan)" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Etnis/Suku</label>
                  <input v-model="form.etnis" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.etnis = toTitleCase(form.etnis)" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Pendidikan</label>
                  <input v-model="form.pendidikan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.pendidikan = toTitleCase(form.pendidikan)" />
                </div>

                <div>
                  <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                  <input v-model="form.pekerjaan" type="text" class="mt-1 w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-400" @blur="form.pekerjaan = toTitleCase(form.pekerjaan)" />
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
                class="rounded-xl bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-50"
              >
                {{ form.processing ? 'Menyimpan...' : 'Simpan Data Penduduk' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>