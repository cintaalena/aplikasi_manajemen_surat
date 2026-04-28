<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  filters: Object,
  letters: Object,
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)
const flashError   = computed(() => page.props.flash?.error)

// ── Filter / Pencarian ──────────────────────────────────────────────
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

// ── Form tambah manual ──────────────────────────────────────────────
const showManualForm = ref(false)

const manualForm = useForm({
  no_surat: '',
  title:    '',
})

const submitManual = () => {
  manualForm.post(route('arsip-surat.store'), {
    preserveScroll: true,
    onSuccess: () => {
      manualForm.reset()
      showManualForm.value = false
    },
  })
}

// ── Format tanggal ──────────────────────────────────────────────────
const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
// ── Dokumen Pendukung — expandable row + modal viewer ─────────────
const expandedId = ref(null)
const viewDoc    = ref(null)

function toggleRow(id) {
  expandedId.value = expandedId.value === id ? null : id
}
function openViewer(doc) {
  viewDoc.value = doc
}
function closeViewer() {
  viewDoc.value = null
}
function isImage(mime) {
  return mime && mime.startsWith('image/')
}
function isPdf(mime) {
  return mime === 'application/pdf'
}</script>

<template>
  <AppLayout>
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-gray-900">Arsip Surat</h1>
          <p class="mt-1 text-sm text-gray-600">
            Cari arsip berdasarkan tanggal, nomor surat, atau judul.
          </p>
        </div>
        <button
          type="button"
          class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition whitespace-nowrap"
          @click="showManualForm = !showManualForm"
        >
          + Tambah Surat Masuk
        </button>
      </div>

      <!-- Flash messages -->
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

      <!-- Form tambah surat manual -->
      <div
        v-if="showManualForm"
        class="rounded-2xl border border-purple-200 bg-white p-5 shadow-sm space-y-4"
      >
        <h2 class="text-sm font-bold text-gray-800">Tambah Surat Masuk Manual</h2>
        <p class="text-xs text-gray-500">Untuk surat yang diterima dari luar (mis. dari kecamatan). Waktu masuk akan dicatat otomatis.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Nomor Surat <span class="text-red-500">*</span></label>
            <input
              v-model="manualForm.no_surat"
              type="text"
              placeholder="Contoh: 123/Kec.X/IV/2026"
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
            />
            <p v-if="manualForm.errors.no_surat" class="text-xs text-red-600">{{ manualForm.errors.no_surat }}</p>
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Judul Surat <span class="text-red-500">*</span></label>
            <input
              v-model="manualForm.title"
              type="text"
              placeholder="Contoh: Undangan Rapat Koordinasi"
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
            />
            <p v-if="manualForm.errors.title" class="text-xs text-red-600">{{ manualForm.errors.title }}</p>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <button
            type="button"
            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
            @click="showManualForm = false; manualForm.reset()"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="manualForm.processing"
            class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition disabled:opacity-60"
            @click="submitManual"
          >
            {{ manualForm.processing ? 'Menyimpan...' : 'Simpan ke Arsip' }}
          </button>
        </div>
      </div>

      <!-- Panel Filter -->
      <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

          <!-- Nomor Surat -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Nomor Surat</label>
            <input
              v-model="form.no_surat"
              type="text"
              placeholder="Contoh: 71/Kel.Ftbs..."
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
              @keyup.enter="search"
            />
          </div>

          <!-- Judul Surat -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Judul Surat</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Contoh: Keterangan Domisili..."
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
              @keyup.enter="search"
            />
          </div>

          <!-- Tanggal Dari -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Tanggal Dari</label>
            <input
              v-model="form.date_from"
              type="date"
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
            />
          </div>

          <!-- Tanggal Sampai -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Tanggal Sampai</label>
            <input
              v-model="form.date_to"
              type="date"
              class="rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-400"
            />
          </div>
        </div>

        <!-- Tombol cari / reset -->
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-fuchsia-500 hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition"
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

        <!-- Badge filter aktif -->
        <div v-if="hasActiveFilter" class="flex flex-wrap gap-2 pt-1">
          <span
            v-if="form.no_surat"
            class="inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 text-xs font-medium px-2.5 py-1"
          >
            No. Surat: {{ form.no_surat }}
            <button @click="form.no_surat = ''; search()" class="hover:text-purple-900">✕</button>
          </span>
          <span
            v-if="form.title"
            class="inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 text-xs font-medium px-2.5 py-1"
          >
            Judul: {{ form.title }}
            <button @click="form.title = ''; search()" class="hover:text-purple-900">✕</button>
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

      <!-- Tabel hasil -->
      <div class="rounded-2xl border border-purple-100 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-purple-50 text-gray-700">
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
              <!-- Baris utama arsip surat -->
              <tr
                :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                class="border-t border-gray-100 hover:bg-purple-50 transition-colors"
              >
                <td class="p-3 text-gray-600 whitespace-nowrap text-xs">
                  {{ formatDate(row.printed_at) }}
                </td>
                <td class="p-3 font-mono font-semibold text-gray-900 text-xs whitespace-nowrap">
                  {{ row.no_surat ?? '-' }}
                </td>
                <td class="p-3 text-gray-700 max-w-xs">
                  {{ row.title ?? '-' }}
                </td>
                <td class="p-3 text-gray-700 text-xs whitespace-nowrap">
                  {{ row.printed_by?.name ?? '-' }}
                </td>
                <td class="p-3">
                  <span
                    v-if="row.is_manual"
                    class="inline-block rounded-full bg-amber-100 text-amber-700 text-xs font-medium px-2.5 py-0.5 whitespace-nowrap"
                  >
                    Surat Masuk
                  </span>
                  <span
                    v-else
                    class="inline-block rounded-full bg-purple-100 text-purple-700 text-xs font-medium px-2.5 py-0.5 whitespace-nowrap"
                  >
                    Dicetak
                  </span>
                </td>
                <td class="p-3">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <a
                      v-if="!row.is_manual && row.template_slug"
                      :href="`/arsip-surat/${row.id}/pratinjau`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-block rounded-lg bg-gradient-to-r from-purple-600 to-fuchsia-500 px-3 py-1.5 text-xs font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition whitespace-nowrap"
                    >
                      Lihat Surat
                    </a>
                    <!-- Tombol Dokumen Pendukung -->
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
                  </div>
                </td>
              </tr>

              <!-- Expandable row: langsung di bawah baris surat ini -->
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

        <!-- Pagination -->
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

            <span class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-purple-700 font-semibold">
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

    <!-- ── Modal Viewer Dokumen ──────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="viewDoc"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
        @click.self="closeViewer"
      >
        <div class="relative bg-white rounded-2xl shadow-2xl flex flex-col max-w-4xl w-full max-h-[90vh]">
          <!-- Header modal -->
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
          <!-- Konten -->
          <div class="flex-1 overflow-auto flex items-center justify-center p-4 bg-gray-50 min-h-0">
            <img
              v-if="isImage(viewDoc.mime_type)"
              :src="viewDoc.url"
              :alt="viewDoc.doc_label"
              class="max-w-full max-h-full object-contain rounded-lg shadow"
            />
            <iframe
              v-else-if="isPdf(viewDoc.mime_type)"
              :src="viewDoc.url"
              class="w-full h-[70vh] rounded-lg border-0"
              title="PDF Viewer"
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
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
