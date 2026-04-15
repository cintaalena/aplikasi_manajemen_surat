<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  penduduks: Object,
  filters: Object,
  dusunOptions: Array,
})

const page = usePage()

const flashSuccess = computed(() => page.props.flash?.success)
const flashError = computed(() => page.props.flash?.error)

const filtersForm = reactive({
  q: props.filters?.q ?? '',
  dusun: props.filters?.dusun ?? '',
  rt: props.filters?.rt ?? '',
  rw: props.filters?.rw ?? '',
  perPage: props.filters?.perPage ?? 20,
})

const importing = ref(false)
const fileRef = ref(null)
const uploadError = ref('')

const openFilePicker = () => {
  uploadError.value = ''
  fileRef.value?.click()
}

const onFileSelected = () => {
  const file = fileRef.value?.files?.[0]
  if (!file) return

  uploadError.value = ''

  const maxSize = 10 * 1024 * 1024
  const allowedExtensions = ['csv', 'txt', 'xlsx', 'xls']
  const fileExtension = file.name.split('.').pop()?.toLowerCase()

  if (file.size > maxSize) {
    uploadError.value = `❌ File terlalu besar! Ukuran: ${(file.size / 1024 / 1024).toFixed(2)}MB (maksimal 10MB)`
    if (fileRef.value) fileRef.value.value = ''
    return
  }

  if (!allowedExtensions.includes(fileExtension || '')) {
    uploadError.value = `❌ Format file tidak didukung! File: .${fileExtension} (harus .csv, .txt, .xlsx, atau .xls)`
    if (fileRef.value) fileRef.value.value = ''
    return
  }

  importCsv()
}

let debounceId = null
watch(
  () => ({ ...filtersForm }),
  () => {
    clearTimeout(debounceId)
    debounceId = setTimeout(() => {
      router.get(route('penduduk.index'), filtersForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      })
    }, 300)
  },
  { deep: true }
)

const resetFilters = () => {
  filtersForm.q = ''
  filtersForm.dusun = ''
  filtersForm.rt = ''
  filtersForm.rw = ''
  filtersForm.perPage = 20
}

const goPage = (url) => {
  if (!url) return
  router.visit(url, { preserveScroll: true, preserveState: true })
}

const exportCsv = () => {
  const params = new URLSearchParams({
    q: filtersForm.q ?? '',
    dusun: filtersForm.dusun ?? '',
    rt: filtersForm.rt ?? '',
    rw: filtersForm.rw ?? '',
  }).toString()

  window.location.href = route('penduduk.export') + (params ? `?${params}` : '')
}

const importCsv = () => {
  const file = fileRef.value?.files?.[0]
  if (!file) {
    uploadError.value = '❌ Pilih file terlebih dahulu'
    return
  }

  importing.value = true
  uploadError.value = ''

  const fd = new FormData()
  fd.append('file', file)

  router.post(route('penduduk.import'), fd, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      importing.value = false
      if (fileRef.value) fileRef.value.value = ''
    },
    onError: (errors) => {
      if (errors.file) {
        uploadError.value = `❌ ${errors.file}`
      }
    },
  })
}
</script>

<template>
  <Head title="Database Penduduk" />

  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
          v-if="flashSuccess"
          class="mb-4 rounded-xl border-2 border-green-200 bg-green-50 p-4 text-green-800 shadow-sm"
        >
          <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-6 w-6 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="mb-1 text-lg font-bold">Berhasil!</h3>
              <pre class="font-sans text-sm whitespace-pre-wrap">{{ flashSuccess }}</pre>
            </div>
          </div>
        </div>

        <div
          v-if="flashError"
          class="mb-4 rounded-xl border-2 border-red-200 bg-red-50 p-4 text-red-800 shadow-sm"
        >
          <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-6 w-6 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="mb-1 text-lg font-bold">Error!</h3>
              <pre class="font-sans text-sm whitespace-pre-wrap">{{ flashError }}</pre>
            </div>
          </div>
        </div>

        <div class="mb-4 rounded-2xl border bg-white p-4 shadow-sm">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
              <label class="text-xs text-gray-500">Pencarian</label>
              <input
                v-model="filtersForm.q"
                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="NIK / Nama / No KK / Alamat"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">Dusun</label>
              <select
                v-model="filtersForm.dusun"
                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Semua</option>
                <option v-for="d in dusunOptions" :key="d" :value="d">
                  {{ d }}
                </option>
              </select>
            </div>

            <div>
              <label class="text-xs text-gray-500">RT</label>
              <input
                v-model="filtersForm.rt"
                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="contoh: 001"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">RW</label>
              <input
                v-model="filtersForm.rw"
                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="contoh: 001"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">Per Halaman</label>
              <select
                v-model="filtersForm.perPage"
                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="30">30</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600"
                @click="resetFilters"
              >
                Reset Filter
              </button>

              <button
                type="button"
                class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600"
                @click="router.visit(route('penduduk.create'))"
              >
                + Tambah Penduduk
              </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600"
                @click="exportCsv"
              >
                Export CSV
              </button>

              <input
                ref="fileRef"
                type="file"
                accept=".csv,.txt,.xlsx,.xls"
                class="hidden"
                @change="onFileSelected"
              />

              <button
                type="button"
                class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 disabled:opacity-60"
                :disabled="importing"
                @click="openFilePicker"
              >
                {{ importing ? 'Mengimpor...' : 'Import Excel/CSV' }}
              </button>
            </div>
          </div>

          <div
            v-if="uploadError"
            class="mt-3 rounded-lg border-2 border-yellow-200 bg-yellow-50 p-3 text-yellow-800"
          >
            <div class="flex items-start gap-2">
              <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              <pre class="font-sans text-sm whitespace-pre-wrap">{{ uploadError }}</pre>
            </div>
          </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
          <div class="overflow-auto">
            <table class="min-w-[2400px] w-full text-sm">
              <thead class="sticky top-0 border-b bg-gray-50">
                <tr class="text-left text-gray-600">
                  <th class="px-3 py-3">Aksi</th>
                  <th class="px-3 py-3">RT</th>
                  <th class="px-3 py-3">RW</th>
                  <th class="px-3 py-3">Dusun</th>
                  <th class="px-3 py-3">Alamat</th>
                  <th class="px-3 py-3">No KK</th>
                  <th class="px-3 py-3">Kepala Keluarga</th>
                  <th class="px-3 py-3">No.</th>
                  <th class="px-3 py-3">NIK</th>
                  <th class="px-3 py-3">Nama</th>
                  <th class="px-3 py-3">JK</th>
                  <th class="px-3 py-3">Hubungan</th>
                  <th class="px-3 py-3">Tempat Lahir</th>
                  <th class="px-3 py-3">Tanggal Lahir</th>
                  <th class="px-3 py-3">Usia</th>
                  <th class="px-3 py-3">Status</th>
                  <th class="px-3 py-3">Agama</th>
                  <th class="px-3 py-3">Gol. Darah</th>
                  <th class="px-3 py-3">Kewarganegaraan</th>
                  <th class="px-3 py-3">Etnis/Suku</th>
                  <th class="px-3 py-3">Pendidikan</th>
                  <th class="px-3 py-3">Pekerjaan</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="p in penduduks.data"
                  :key="p.id"
                  class="border-b hover:bg-gray-50"
                >
                  <td class="px-3 py-3">
                    <button
                      type="button"
                      class="rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200 transition"
                      @click="router.visit(route('penduduk.edit', p.id))"
                    >
                      Edit
                    </button>
                  </td>
                  <td class="px-3 py-3 text-xs font-mono">{{ p.rt }}</td>
                  <td class="px-3 py-3 text-xs font-mono">{{ p.rw }}</td>
                  <td class="px-3 py-3">{{ p.dusun }}</td>
                  <td class="max-w-[200px] truncate px-3 py-3" :title="p.alamat">{{ p.alamat }}</td>
                  <td class="px-3 py-3 text-xs font-mono">{{ p.kode_keluarga }}</td>
                  <td class="px-3 py-3">{{ p.nama_kepala_keluarga }}</td>
                  <td class="px-3 py-3 text-center">{{ p.no_urut }}</td>
                  <td class="px-3 py-3 text-xs font-mono">{{ p.nik }}</td>
                  <td class="px-3 py-3 font-semibold">{{ p.nama }}</td>
                  <td class="px-3 py-3 text-center">{{ p.jenis_kelamin }}</td>
                  <td class="px-3 py-3">{{ p.hubungan }}</td>
                  <td class="px-3 py-3">{{ p.tempat_lahir }}</td>
                  <td class="px-3 py-3 text-xs">{{ p.tanggal_lahir }}</td>
                  <td class="px-3 py-3 text-center">{{ p.usia }}</td>
                  <td class="px-3 py-3">{{ p.status_perkawinan }}</td>
                  <td class="px-3 py-3">{{ p.agama }}</td>
                  <td class="px-3 py-3 text-center">{{ p.golongan_darah }}</td>
                  <td class="px-3 py-3">{{ p.kewarganegaraan }}</td>
                  <td class="px-3 py-3">{{ p.etnis }}</td>
                  <td class="px-3 py-3">{{ p.pendidikan }}</td>
                  <td class="px-3 py-3">{{ p.pekerjaan }}</td>
                </tr>

                <tr v-if="penduduks.data.length === 0">
                  <td colspan="22" class="px-4 py-8 text-center text-gray-500">
                    Data tidak ditemukan.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between p-4 text-sm text-gray-600">
            <div>
              Menampilkan
              <span class="font-semibold">{{ penduduks.from ?? 0 }}</span>
              –
              <span class="font-semibold">{{ penduduks.to ?? 0 }}</span>
              dari
              <span class="font-semibold">{{ penduduks.total ?? 0 }}</span>
              data
            </div>

            <div class="flex flex-wrap gap-1">
              <button
                v-for="(link, i) in penduduks.links"
                :key="i"
                class="rounded-lg border px-3 py-1.5 hover:bg-gray-50"
                :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white hover:bg-indigo-700' : ''"
                :disabled="!link.url"
                v-html="link.label"
                @click="goPage(link.url)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>