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

const form = reactive({
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

  // Validasi client-side
  const maxSize = 10 * 1024 * 1024 // 10MB
  const allowedExtensions = ['csv', 'txt', 'xlsx', 'xls']
  const fileExtension = file.name.split('.').pop()?.toLowerCase()

  // Cek ukuran file
  if (file.size > maxSize) {
    uploadError.value = `❌ File terlalu besar! Ukuran: ${(file.size / 1024 / 1024).toFixed(2)}MB (maksimal 10MB)`
    if (fileRef.value) fileRef.value.value = ''
    return
  }

  // Cek ekstensi file
  if (!allowedExtensions.includes(fileExtension || '')) {
    uploadError.value = `❌ Format file tidak didukung! File: .${fileExtension} (harus .csv, .txt, .xlsx, atau .xls)`
    if (fileRef.value) fileRef.value.value = ''
    return
  }

  // Semua validasi OK, lanjut import
  importCsv()
}

let debounceId = null
watch(
  () => ({ ...form }),
  () => {
    clearTimeout(debounceId)
    debounceId = setTimeout(() => {
      router.get(route('penduduk.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      })
    }, 300)
  },
  { deep: true }
)

const resetFilters = () => {
  form.q = ''
  form.dusun = ''
  form.rt = ''
  form.rw = ''
  form.perPage = 20
}

const goPage = (url) => {
  if (!url) return
  router.visit(url, { preserveScroll: true, preserveState: true })
}

const exportCsv = () => {
  const params = new URLSearchParams({
    q: form.q ?? '',
    dusun: form.dusun ?? '',
    rt: form.rt ?? '',
    rw: form.rw ?? '',
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
      // Laravel validation errors
      if (errors.file) {
        uploadError.value = `❌ ${errors.file}`
      }
    }
  })
}

const btnPrimary =
  'rounded-xl px-4 py-2 text-sm font-semibold text-white ' +
  'bg-gradient-to-r from-purple-600 to-fuchsia-500 ' +
  'hover:from-purple-700 hover:to-fuchsia-600'
</script>

<template>
  <Head title="Database Penduduk" />

  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <div
          v-if="flashSuccess"
          class="mb-4 p-4 rounded-xl bg-green-50 text-green-800 border-2 border-green-200 shadow-sm"
        >
          <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="font-bold text-lg mb-1">Import Berhasil!</h3>
              <pre class="text-sm whitespace-pre-wrap font-sans">{{ flashSuccess }}</pre>
            </div>
          </div>
        </div>
        <div
          v-if="flashError"
          class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border-2 border-red-200 shadow-sm"
        >
          <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
              <h3 class="font-bold text-lg mb-1">Error Import!</h3>
              <pre class="text-sm whitespace-pre-wrap font-sans">{{ flashError }}</pre>
            </div>
          </div>
        </div>

        <!-- Control panel -->
        <div class="bg-white shadow-sm rounded-2xl border p-4 mb-4">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
              <label class="text-xs text-gray-500">Pencarian</label>
              <input
                v-model="form.q"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="NIK / Nama / No KK / Alamat"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">Dusun</label>
              <select
                v-model="form.dusun"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
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
                v-model="form.rt"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="contoh: 001"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">RW</label>
              <input
                v-model="form.rw"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="contoh: 001"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500">Per Halaman</label>
              <select
                v-model="form.perPage"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="30">30</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>

            <div class="mt-4 flex items-center justify-between gap-3">
            <!-- KIRI: Reset -->
            <div class="flex gap-2">
            <button
              type="button"
              class="rounded-xl px-4 py-2 text-sm font-semibold text-white 
              bg-gradient-to-r from-purple-600 to-fuchsia-500 
              hover:from-purple-700 hover:to-fuchsia-600"
              @click="resetFilters"
            >
              Reset Filter
            </button>
          </div>

          <!-- KANAN -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-xl px-4 py-2 text-sm font-semibold text-white 
              bg-gradient-to-r from-purple-600 to-fuchsia-500 
              hover:from-purple-700 hover:to-fuchsia-600"
              @click="exportCsv"
            >
              Export CSV
            </button>

            <!-- input file disembunyikan -->
            <input
              ref="fileRef"
              type="file"
              accept=".csv,.txt,.xlsx,.xls"
              class="hidden"
              @change="onFileSelected"
            />

            <button
              type="button"
              class="rounded-xl px-4 py-2 text-sm font-semibold text-white 
              bg-gradient-to-r from-purple-600 to-fuchsia-500 
              hover:from-purple-700 hover:to-fuchsia-600 disabled:opacity-60"
              :disabled="importing"
              @click="openFilePicker"
            >
              {{ importing ? 'Mengimpor...' : 'Import Excel/CSV' }}
            </button>
          </div>
        </div>

        <!-- Client-side Upload Error -->
        <div
          v-if="uploadError"
          class="mt-3 p-3 rounded-lg bg-yellow-50 text-yellow-800 border-2 border-yellow-200"
        >
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <pre class="text-sm whitespace-pre-wrap font-sans">{{ uploadError }}</pre>
          </div>
        </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-sm rounded-2xl border overflow-hidden">
          <div class="overflow-auto">
            <table class="min-w-[2400px] w-full text-sm">
              <thead class="sticky top-0 bg-gray-50 border-b">
                <tr class="text-left text-gray-600">
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
                  <td class="px-3 py-3 font-mono text-xs">{{ p.rt }}</td>
                  <td class="px-3 py-3 font-mono text-xs">{{ p.rw }}</td>
                  <td class="px-3 py-3">{{ p.dusun }}</td>
                  <td class="px-3 py-3 max-w-[200px] truncate" :title="p.alamat">{{ p.alamat }}</td>
                  <td class="px-3 py-3 font-mono text-xs">{{ p.kode_keluarga }}</td>
                  <td class="px-3 py-3">{{ p.nama_kepala_keluarga }}</td>
                  <td class="px-3 py-3 text-center">{{ p.no_urut }}</td>
                  <td class="px-3 py-3 font-mono text-xs">{{ p.nik }}</td>
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
                  <td colspan="21" class="px-4 py-8 text-center text-gray-500">
                    Data tidak ditemukan.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="p-4 flex items-center justify-between text-sm text-gray-600">
            <div>
              Menampilkan
              <span class="font-semibold">{{ penduduks.from ?? 0 }}</span>
              –
              <span class="font-semibold">{{ penduduks.to ?? 0 }}</span>
              dari
              <span class="font-semibold">{{ penduduks.total ?? 0 }}</span>
              data
            </div>

            <div class="flex gap-1 flex-wrap">
              <button
                v-for="(link, i) in penduduks.links"
                :key="i"
                class="px-3 py-1.5 rounded-lg border hover:bg-gray-50"
                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700' : ''"
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
