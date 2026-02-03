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
const openFilePicker = () => {
  fileRef.value?.click()
}

const onFileSelected = () => {
  const file = fileRef.value?.files?.[0]
  if (!file) return
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
  if (!file) return alert('Pilih file CSV terlebih dahulu.')

  importing.value = true
  const fd = new FormData()
  fd.append('file', file)

  router.post(route('penduduk.import'), fd, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      importing.value = false
      if (fileRef.value) fileRef.value.value = ''
    },
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
        <!-- Flash -->
        <div
          v-if="flashSuccess"
          class="mb-4 p-3 rounded-lg bg-green-50 text-green-800 border border-green-200"
        >
          {{ flashSuccess }}
        </div>
        <div
          v-if="flashError"
          class="mb-4 p-3 rounded-lg bg-red-50 text-red-800 border border-red-200"
        >
          {{ flashError }}
        </div>

        <!-- Control panel -->
        <div class="bg-white shadow-sm rounded-2xl border p-4 mb-4">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
              <label class="text-xs text-gray-500">Pencarian</label>
              <input
                v-model="form.q"
                class="w-full mt-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="No KK / Kepala Keluarga / Alamat"
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
              accept=".csv,.txt"
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
              {{ importing ? 'Mengimpor...' : 'Import CSV' }}
            </button>
          </div>
        </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-sm rounded-2xl border overflow-hidden">
          <div class="overflow-auto">
            <table class="min-w-[1400px] w-full text-sm">
              <thead class="sticky top-0 bg-gray-50 border-b">
                <tr class="text-left text-gray-600">
                  <th class="px-4 py-3">No KK</th>
                  <th class="px-4 py-3">Kepala Keluarga</th>
                  <th class="px-4 py-3">Alamat</th>
                  <th class="px-4 py-3">RT</th>
                  <th class="px-4 py-3">RW</th>
                  <th class="px-4 py-3">Dusun</th>
                  <th class="px-4 py-3">Bulan</th>
                  <th class="px-4 py-3">Tahun</th>
                  <th class="px-4 py-3">Pengisi</th>
                  <th class="px-4 py-3">Pekerjaan</th>
                  <th class="px-4 py-3">Jabatan</th>
                  <th class="px-4 py-3">Sumber Data</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="p in penduduks.data"
                  :key="p.id"
                  class="border-b hover:bg-gray-50"
                >
                  <td class="px-4 py-3 font-mono">{{ p.kode_keluarga }}</td>
                  <td class="px-4 py-3">{{ p.nama_kepala_keluarga }}</td>
                  <td class="px-4 py-3">{{ p.alamat }}</td>
                  <td class="px-4 py-3">{{ p.rt }}</td>
                  <td class="px-4 py-3">{{ p.rw }}</td>
                  <td class="px-4 py-3">{{ p.nama_dusun }}</td>
                  <td class="px-4 py-3">{{ p.bulan }}</td>
                  <td class="px-4 py-3">{{ p.tahun }}</td>
                  <td class="px-4 py-3">{{ p.nama_pengisi }}</td>
                  <td class="px-4 py-3">{{ p.pekerjaan }}</td>
                  <td class="px-4 py-3">{{ p.jabatan }}</td>
                  <td class="px-4 py-3">{{ p.sumber_data }}</td>
                </tr>

                <tr v-if="penduduks.data.length === 0">
                  <td colspan="12" class="px-4 py-8 text-center text-gray-500">
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
