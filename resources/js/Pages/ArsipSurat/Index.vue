<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  filters: Object,
  letters: Object,
  viewed_ids: Array,
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)
const flashError   = computed(() => page.props.flash?.error)

const userRole = computed(() => page.props.auth?.user?.role ?? 'staff')

const localViewedIds = ref(new Set(props.viewed_ids ?? []))

function isViewed(id) {
  return localViewedIds.value.has(id)
}

function markAsViewed(id) {
  if (localViewedIds.value.has(id)) return
  localViewedIds.value = new Set([...localViewedIds.value, id])
  router.post(route('arsip-surat.viewed', id), {}, {
    preserveScroll: true,
    preserveState: true,
    only: [],
  })
}

const form = ref({
  no_surat:  props.filters?.no_surat  ?? '',
  title:     props.filters?.title     ?? '',
  date_from: props.filters?.date_from ?? '',
  date_to:   props.filters?.date_to   ?? '',
})

const hasActiveFilter = computed(() =>
  Object.values(form.value).some(v => v !== '')
)

const search = () => {
  const params = {}
  Object.entries(form.value).forEach(([k, v]) => { if (v !== '') params[k] = v })
  router.get(route('arsip-surat.index'), params, { preserveState: true, replace: true })
}

const reset = () => {
  form.value = { no_surat: '', title: '', date_from: '', date_to: '' }
  router.get(route('arsip-surat.index'), {}, { preserveState: false })
}

const showManualForm = ref(false)

const manualForm = useForm({
  no_surat:    '',
  title:       '',
  manual_type: 'masuk',
  files:       [],
})

const openManualForm = (type) => {
  manualForm.manual_type = type
  showManualForm.value = true
}

// Dibuka otomatis kalau diarahkan dari halaman lain (mis. setelah menyelesaikan
// tugas disposisi) lewat query ?open=keluar, supaya pengguna tidak lupa mengisi.
onMounted(() => {
  const openType = new URLSearchParams(window.location.search).get('open')
  if (userRole.value !== 'lurah' && (openType === 'keluar' || openType === 'masuk')) {
    openManualForm(openType)
  }
})

const selectedFiles = ref([])
const uploadError   = ref('')

const formatBytes = (bytes) => {
  if (!bytes) return ''
  if (bytes < 1024)        return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const ALLOWED_MIMES = ['image/jpeg','image/png','image/webp','application/pdf']
const MAX_BYTES     = 5 * 1024 * 1024

const handleFileChange = (e) => {
  const files = Array.from(e.target.files || [])
  e.target.value = ''
  uploadError.value = ''

  for (const file of files) {
    if (!ALLOWED_MIMES.includes(file.type)) {
      uploadError.value = `File "${file.name}" tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF.`
      continue
    }
    if (file.size > MAX_BYTES) {
      uploadError.value = `File "${file.name}" terlalu besar. Maksimal 5 MB.`
      continue
    }
    selectedFiles.value.push({ name: file.name, size: file.size, mime: file.type })
    manualForm.files.push(file)
  }
}

const removeFile = (idx) => {
  selectedFiles.value.splice(idx, 1)
  manualForm.files.splice(idx, 1)
}

const submitManual = () => {
  manualForm.post(route('arsip-surat.store'), {
    preserveScroll: true,
    onSuccess: () => {
      manualForm.reset()
      selectedFiles.value = []
      uploadError.value   = ''
      showManualForm.value = false
    },
  })
}

const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
const expandedId    = ref(null)
const viewDoc       = ref(null)
const viewDocError  = ref(false)

function toggleRow(id) {
  expandedId.value = expandedId.value === id ? null : id
  markAsViewed(id)
}
function openViewer(doc) {
  viewDoc.value      = doc
  viewDocError.value = false
}
function closeViewer() {
  viewDoc.value      = null
  viewDocError.value = false
}
function onViewDocError() {
  viewDocError.value = true
}
function isImage(mime) {
  return mime && mime.startsWith('image/')
}
function isPdf(mime) {
  return mime === 'application/pdf'
}

const showDisposisiModal = ref(false)
const disposisiLetterId  = ref(null)
const disposisiLetterTitle = ref('')
const staffList          = ref([])
const staffLoading       = ref(false)

const disposisiForm = useForm({
  to_user_id: '',
  catatan:    '',
})

async function openDisposisi(row) {
  disposisiLetterId.value   = row.id
  disposisiLetterTitle.value = row.title
  disposisiForm.reset()
  showDisposisiModal.value = true
  if (staffList.value.length === 0) {
    staffLoading.value = true
    try {
      const res = await fetch(route('disposisi.staff-list'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      })
      if (res.ok) staffList.value = await res.json()
    } finally {
      staffLoading.value = false
    }
  }
}

function closeDisposisi() {
  showDisposisiModal.value = false
  disposisiForm.reset()
}

function submitDisposisi() {
  disposisiForm.post(route('disposisi.store', { letter: disposisiLetterId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      closeDisposisi()
    },
  })
}</script>

<template>
  <AppLayout>
    <div class="space-y-5">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-gray-900">Arsip Surat</h1>
          <p class="mt-1 text-sm text-gray-600">
            Cari arsip berdasarkan tanggal, nomor surat, atau judul.
          </p>
        </div>
        <div v-if="userRole !== 'lurah'" class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-xl bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 shadow-sm transition whitespace-nowrap"
            @click="showManualForm && manualForm.manual_type === 'masuk' ? (showManualForm = false) : openManualForm('masuk')"
          >
            + Tambah Surat Masuk
          </button>
          <button
            type="button"
            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 shadow-sm transition whitespace-nowrap"
            @click="showManualForm && manualForm.manual_type === 'keluar' ? (showManualForm = false) : openManualForm('keluar')"
          >
            + Tambah Surat Keluar
          </button>
        </div>
      </div>

      <div
        v-if="flashSuccess"
        class="rounded-xl border-2 border-green-200 bg-green-50 p-3 text-green-800 text-sm"
      >
        {{ flashSuccess }}
      </div>
      <div
        v-if="flashError"
        class="rounded-xl border-2 border-red-200 bg-red-50 p-3 text-red-800 text-sm"
      >
        {{ flashError }}
      </div>

      <div
        v-if="showManualForm && userRole !== 'lurah'"
        class="rounded-2xl border p-5 shadow-sm space-y-4 bg-white"
        :class="manualForm.manual_type === 'keluar' ? 'border-blue-200' : 'border-green-200'"
      >
        <h2 class="text-sm font-bold text-gray-800">
          Tambah {{ manualForm.manual_type === 'keluar' ? 'Surat Keluar' : 'Surat Masuk' }} Manual
        </h2>
        <p class="text-xs text-gray-500">
          <template v-if="manualForm.manual_type === 'keluar'">
            Untuk surat yang dikirim ke instansi/pihak di luar kelurahan. Waktu keluar akan dicatat otomatis.
          </template>
          <template v-else>
            Untuk surat yang diterima dari luar (mis. dari kecamatan). Waktu masuk akan dicatat otomatis.
          </template>
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Nomor Surat <span class="text-red-500">*</span></label>
            <input
              v-model="manualForm.no_surat"
              type="text"
              placeholder="Contoh: 123/Kec.X/IV/2026"
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
            />
            <p v-if="manualForm.errors.no_surat" class="text-xs text-red-600">{{ manualForm.errors.no_surat }}</p>
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Judul Surat <span class="text-red-500">*</span></label>
            <input
              v-model="manualForm.title"
              type="text"
              placeholder="Contoh: Undangan Rapat Koordinasi"
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
            />
            <p v-if="manualForm.errors.title" class="text-xs text-red-600">{{ manualForm.errors.title }}</p>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-xs font-semibold text-gray-600">
            Upload Berkas Surat
            <span class="text-red-500">*</span>
            <span class="ml-1 font-normal text-gray-400">(Wajib — JPG, PNG, WEBP, PDF · maks 5 MB/file)</span>
          </label>

          <label
            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-green-200 bg-green-50/40 px-4 py-5 text-center transition hover:border-green-400 hover:bg-green-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span class="text-sm font-medium text-green-600">Klik atau seret file ke sini</span>
            <span class="text-xs text-gray-400">Bisa pilih lebih dari satu file</span>
            <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="sr-only" @change="handleFileChange" />
          </label>

          <p v-if="selectedFiles.length === 0" class="text-xs text-red-600 font-medium">⚠ Wajib upload minimal 1 file berkas surat sebelum menyimpan.</p>

          <p v-if="uploadError" class="text-xs text-red-600">{{ uploadError }}</p>

          <div v-if="selectedFiles.length" class="space-y-2">
            <div
              v-for="(file, idx) in selectedFiles"
              :key="idx"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm"
            >
              <span class="text-lg shrink-0">
                <template v-if="file.mime === 'application/pdf'">📄</template>
                <template v-else-if="file.mime?.startsWith('image/')">🖼️</template>
                <template v-else>📎</template>
              </span>

              <div class="flex-1 min-w-0">
                <p class="truncate font-medium text-gray-800 text-xs" :title="file.name">{{ file.name }}</p>
                <p class="text-xs text-gray-400">{{ formatBytes(file.size) }}</p>
              </div>

              <button
                type="button"
                class="shrink-0 rounded-lg p-1 text-gray-400 hover:bg-red-50 hover:text-red-500 transition"
                @click="removeFile(idx)"
                title="Hapus file ini"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <button
            type="button"
            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
            @click="showManualForm = false; manualForm.reset(); selectedFiles = []; uploadError = ''"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="manualForm.processing || selectedFiles.length === 0"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
            :class="manualForm.manual_type === 'keluar' ? 'bg-blue-700 hover:bg-blue-800' : 'bg-green-700 hover:bg-green-800'"
            :title="selectedFiles.length === 0 ? 'Upload file terlebih dahulu' : ''"
            @click="submitManual"
          >
            {{ manualForm.processing ? 'Menyimpan...' : 'Simpan ke Arsip' }}
          </button>
        </div>
      </div>

      <div class="rounded-2xl border border-green-100 bg-white p-4 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Nomor Surat</label>
            <input
              v-model="form.no_surat"
              type="text"
              placeholder="Contoh: 71/Kel.Ftbs..."
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
              @keyup.enter="search"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Judul Surat</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Contoh: Keterangan Domisili..."
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
              @keyup.enter="search"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Tanggal Dari</label>
            <input
              v-model="form.date_from"
              type="date"
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Tanggal Sampai</label>
            <input
              v-model="form.date_to"
              type="date"
              class="rounded-xl border-gray-200 text-sm focus:border-green-400 focus:ring-green-400"
            />
          </div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-green-700 hover:bg-green-800 shadow-sm transition"
            @click="search"
          >
            Cari
          </button>
          <button
            v-if="hasActiveFilter"
            type="button"
            class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
            @click="reset"
            title="Reset semua filter"
          >
            Reset
          </button>
        </div>

        <div v-if="hasActiveFilter" class="flex flex-wrap gap-2 pt-1">
          <span
            v-if="form.no_surat"
            class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 text-xs font-medium px-2.5 py-1"
          >
            No. Surat: {{ form.no_surat }}
            <button @click="form.no_surat = ''; search()" class="hover:text-green-900">✕</button>
          </span>
          <span
            v-if="form.title"
            class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 text-xs font-medium px-2.5 py-1"
          >
            Judul: {{ form.title }}
            <button @click="form.title = ''; search()" class="hover:text-green-900">✕</button>
          </span>
          <span
            v-if="form.date_from"
            class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 text-xs font-medium px-2.5 py-1"
          >
            Dari: {{ form.date_from }}
            <button @click="form.date_from = ''; search()" class="hover:text-green-900">✕</button>
          </span>
          <span
            v-if="form.date_to"
            class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 text-xs font-medium px-2.5 py-1"
          >
            Sampai: {{ form.date_to }}
            <button @click="form.date_to = ''; search()" class="hover:text-green-900">✕</button>
          </span>
        </div>
      </div>

      <div class="rounded-2xl border border-green-100 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[880px] text-sm">
          <thead class="bg-green-50 text-gray-700">
            <tr>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Waktu</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Nomor Surat</th>
              <th class="p-3 text-left font-semibold">Judul</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Dibuat Oleh</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Sumber</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(row, idx) in letters.data" :key="row.id">
              <tr
                :class="[
                  !isViewed(row.id)
                    ? 'bg-blue-50 border-l-4 border-l-blue-400'
                    : (idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'),
                ]"
                class="border-t border-gray-100 hover:bg-green-50 transition-colors"
              >
                <td class="p-3 text-gray-600 whitespace-nowrap text-xs">
                  {{ formatDate(row.printed_at) }}
                </td>
                <td class="p-3 font-mono font-semibold text-gray-900 text-xs whitespace-nowrap">
                  {{ row.no_surat ?? '-' }}
                </td>
                <td class="p-3 text-gray-700 max-w-xs">
                  <div class="flex items-center gap-2">
                    <span>{{ row.title ?? '-' }}</span>
                    <span
                      v-if="!isViewed(row.id)"
                      class="inline-block rounded-full bg-blue-500 text-white text-xs font-bold px-2 py-0.5 leading-tight whitespace-nowrap flex-shrink-0"
                    >
                      Baru
                    </span>
                    <Link
                      v-if="row.missing_required_docs && row.missing_required_docs.length > 0"
                      :href="`/arsip-surat/${row.id}`"
                      class="inline-flex items-center justify-center rounded-full bg-red-100 text-red-600 h-5 w-5 text-xs font-bold flex-shrink-0 hover:bg-red-200 transition"
                      :title="`Dokumen pendukung belum lengkap (${row.missing_required_docs.length} kurang). Klik untuk melengkapi.`"
                    >
                      !
                    </Link>
                  </div>
                </td>
                <td class="p-3 text-gray-700 text-xs whitespace-nowrap">
                  {{ row.printed_by?.name ?? '-' }}
                </td>
                <td class="p-3">
                  <span
                    v-if="row.is_manual && row.manual_type === 'keluar'"
                    class="inline-block rounded-full bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-0.5 whitespace-nowrap"
                  >
                    Surat Keluar
                  </span>
                  <span
                    v-else-if="row.is_manual"
                    class="inline-block rounded-full bg-amber-100 text-amber-700 text-xs font-medium px-2.5 py-0.5 whitespace-nowrap"
                  >
                    Surat Masuk
                  </span>
                  <span
                    v-else
                    class="inline-block rounded-full bg-green-100 text-green-700 text-xs font-medium px-2.5 py-0.5 whitespace-nowrap"
                  >
                    Dicetak
                  </span>
                </td>
                <td class="p-3 whitespace-nowrap">
                  <div class="flex items-center gap-1.5 flex-nowrap">
                    <a
                      v-if="!row.is_manual && row.template_slug"
                      :href="`/arsip-surat/${row.id}/pratinjau`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-block rounded-lg bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-800 shadow-sm transition whitespace-nowrap"
                      @click="markAsViewed(row.id)"
                    >
                      Lihat Surat
                    </a>
                    <button
                      v-if="row.documents && row.documents.length > 0"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition whitespace-nowrap"
                      @click="toggleRow(row.id)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                      Dokumen
                      <span class="rounded-full bg-amber-200 px-1.5">{{ row.documents.length }}</span>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-3 w-3 transition-transform duration-150"
                        :class="{ 'rotate-180': expandedId === row.id }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                      ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <span v-else-if="row.is_manual" class="text-gray-300 text-xs">—</span>
                    <span
                      v-if="userRole === 'admin' && row.dispositions && row.dispositions.length > 0"
                      class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-800 whitespace-nowrap"
                      :title="row.dispositions.map(d => `Ke: ${d.to_user?.name ?? '-'} | Status: ${d.status}${d.catatan ? ' | Catatan: ' + d.catatan : ''}`).join('\n')"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                      Didisposisikan
                      <span class="rounded-full bg-purple-200 px-1.5">{{ row.dispositions.length }}</span>
                    </span>
                    <button
                      v-if="userRole === 'lurah'"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 hover:bg-blue-100 transition whitespace-nowrap"
                      @click="openDisposisi(row)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                      Disposisi
                    </button>
                  </div>
                </td>
              </tr>

              <tr
                v-if="expandedId === row.id && row.documents && row.documents.length > 0"
                class="border-t border-amber-100"
              >
                <td colspan="6" class="px-5 py-4 bg-amber-50">
                  <p class="text-xs font-semibold text-amber-700 mb-3">Dokumen Pendukung Surat</p>
                  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div
                      v-for="(doc, dIdx) in row.documents"
                      :key="doc.id"
                      class="flex flex-col rounded-xl border border-amber-200 bg-white overflow-hidden shadow-sm"
                    >
                      <div class="px-2 py-1.5 bg-amber-50 border-b border-amber-100">
                        <p class="text-xs font-semibold text-amber-800 leading-tight truncate" :title="doc.doc_label">
                          {{ dIdx + 1 }}. {{ doc.doc_label }}
                        </p>
                      </div>
                      <div class="bg-gray-100 flex items-center justify-center" style="min-height: 140px;">
                        <template v-if="isImage(doc.mime_type)">
                          <img
                            :src="doc.url"
                            :alt="doc.doc_label"
                            class="w-full object-contain"
                            style="max-height: 200px;"
                            loading="lazy"
                          />
                        </template>
                        <template v-else-if="isPdf(doc.mime_type)">
                          <div class="flex flex-col items-center justify-center gap-2 py-4 text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-bold text-red-500">PDF</span>
                          </div>
                        </template>
                        <template v-else>
                          <div class="flex flex-col items-center justify-center gap-1 py-4 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-xs">File</span>
                          </div>
                        </template>
                      </div>
                      <div class="px-2 py-2 bg-white flex items-center justify-between gap-1">
                        <p class="text-xs text-gray-400 truncate flex-1" :title="doc.original_name">{{ doc.original_name ?? '—' }}</p>
                        <a
                          :href="doc.url"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="flex-shrink-0 rounded-lg bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600 transition"
                        >
                          Buka
                        </a>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!letters.data.length">
              <td class="p-4 text-gray-500 italic" colspan="6">
                Tidak ada arsip surat yang sesuai dengan filter.
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm">
          <div class="text-gray-500">
            Menampilkan
            <span class="font-semibold text-gray-800">{{ letters.from ?? 0 }}</span>–<span class="font-semibold text-gray-800">{{ letters.to ?? 0 }}</span>
            dari
            <span class="font-semibold text-gray-800">{{ letters.total }}</span>
            surat
          </div>

          <div class="flex gap-2">
            <Link
              v-if="letters.prev_page_url"
              :href="letters.prev_page_url"
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition"
            >
              ← Prev
            </Link>
            <span
              v-else
              class="rounded-lg border border-gray-100 px-3 py-1.5 text-gray-300 cursor-not-allowed"
            >
              ← Prev
            </span>

            <span class="rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-green-700 font-semibold">
              {{ letters.current_page }}
            </span>

            <Link
              v-if="letters.next_page_url"
              :href="letters.next_page_url"
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition"
            >
              Next →
            </Link>
            <span
              v-else
              class="rounded-lg border border-gray-100 px-3 py-1.5 text-gray-300 cursor-not-allowed"
            >
              Next →
            </span>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="viewDoc"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
        @click.self="closeViewer"
      >
        <div class="relative bg-white rounded-2xl shadow-2xl flex flex-col max-w-4xl w-full max-h-[90vh]">
          <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 flex-shrink-0">
            <span class="text-sm font-semibold text-gray-800 truncate pr-4">{{ viewDoc.doc_label }}</span>
            <div class="flex items-center gap-2 flex-shrink-0">
              <a
                :href="viewDoc.url"
                target="_blank"
                download
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition"
              >
                Unduh
              </a>
              <button
                type="button"
                class="rounded-lg border border-gray-200 p-1.5 text-gray-500 hover:bg-gray-50 transition"
                @click="closeViewer"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="flex-1 overflow-auto flex items-center justify-center p-4 bg-gray-50 min-h-0">
            <div v-if="viewDocError" class="text-center space-y-3 py-8">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <p class="text-sm font-medium text-gray-700">File tidak ditemukan di server</p>
              <p class="text-xs text-gray-400">File mungkin belum diunggah ulang atau telah dihapus.</p>
            </div>
            <template v-else>
              <img
                v-if="isImage(viewDoc.mime_type)"
                :src="viewDoc.url"
                :alt="viewDoc.doc_label"
                class="max-w-full max-h-full object-contain rounded-lg shadow"
                @error="onViewDocError"
              />
              <iframe
                v-else-if="isPdf(viewDoc.mime_type)"
                :src="viewDoc.url"
                class="w-full h-[70vh] rounded-lg border-0"
                title="PDF Viewer"
                @error="onViewDocError"
              />
              <div v-else class="text-center space-y-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm text-gray-500">Pratinjau tidak tersedia</p>
                <a :href="viewDoc.url" target="_blank" class="inline-block rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition">
                  Buka / Unduh File
                </a>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

  <Teleport to="body">
    <div v-if="showDisposisiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40" @click="closeDisposisi"></div>
      <div class="relative w-full max-w-md rounded-2xl border border-blue-100 bg-white shadow-2xl z-10">
        <div class="flex items-center justify-between border-b border-blue-50 px-5 py-4">
          <h2 class="text-base font-bold text-gray-900">Disposisi Surat</h2>
          <button type="button" @click="closeDisposisi" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="px-5 py-4 space-y-4">
          <p class="text-sm text-gray-600">
            Disposisikan surat <span class="font-semibold text-gray-900">"{{ disposisiLetterTitle }}"</span> kepada:
          </p>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Tujuan Disposisi <span class="text-red-500">*</span></label>
            <div v-if="staffLoading" class="text-sm text-gray-400 italic">Memuat daftar staff...</div>
            <select
              v-else
              v-model="disposisiForm.to_user_id"
              class="rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-400"
            >
              <option value="">-- Pilih Staff --</option>
              <option v-for="s in staffList" :key="s.id" :value="s.id">
                {{ s.name }}{{ s.jabatan ? ' — ' + s.jabatan : '' }}
              </option>
            </select>
            <p v-if="disposisiForm.errors.to_user_id" class="text-xs text-red-600">{{ disposisiForm.errors.to_user_id }}</p>
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Catatan <span class="text-gray-400 font-normal">(opsional)</span></label>
            <textarea
              v-model="disposisiForm.catatan"
              rows="3"
              placeholder="Contoh: Tolong tindak lanjuti segera..."
              class="rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-400 resize-none"
            ></textarea>
            <p v-if="disposisiForm.errors.catatan" class="text-xs text-red-600">{{ disposisiForm.errors.catatan }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-blue-50 px-5 py-3">
          <button
            type="button"
            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
            @click="closeDisposisi"
          >Batal</button>
          <button
            type="button"
            :disabled="disposisiForm.processing || !disposisiForm.to_user_id"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
            @click="submitDisposisi"
          >
            {{ disposisiForm.processing ? 'Mengirim...' : 'Kirim Disposisi' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  </AppLayout>
</template>
