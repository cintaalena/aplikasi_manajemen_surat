<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import KematianTemplate from '@/Components/Surat/KematianTemplate.vue'
import PindahTemplate from '@/Components/Surat/PindahTemplate.vue'
import { computed, ref } from 'vue'

const props = defineProps({
  letter: Object,
})

const tanggalIndo = (yyyy_mm_dd) => {
  if (!yyyy_mm_dd) return ''
  let d
  if (typeof yyyy_mm_dd === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(yyyy_mm_dd)) {
    const [y, m, day] = yyyy_mm_dd.split('-').map(Number)
    d = new Date(y, m - 1, day)
  } else {
    d = new Date(yyyy_mm_dd)
  }
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

// Rekonstruksi objek form dari payload yang tersimpan di database
const form = computed(() => ({
  ...(props.letter.payload ?? {}),
  noSurat: props.letter.no_surat,
  judulSurat: props.letter.title,
}))

// Penanda tangan — data user yang mencetak surat
const signer = computed(() => props.letter.printed_by ?? null)

const slug = computed(() => props.letter.template_slug ?? '')
const isDomisili  = computed(() => slug.value === 'keterangan-domisili')
const isKelahiran = computed(() => slug.value === 'keterangan-kelahiran')
const isKematian  = computed(() => slug.value === 'keterangan-kematian')
const isPindah    = computed(() => slug.value === 'keterangan-pindah')

const documents = computed(() => props.letter.documents ?? [])

const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

// ── Dokumen Pendukung — accordion + modal viewer ──────────────────────
const dokOpen = ref(true)
const viewDoc = ref(null) // { url, doc_label, mime_type, original_name }

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
}
</script>

<template>
  <AppLayout>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
          <Link
            :href="route('arsip-surat.index')"
            class="mt-0.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition whitespace-nowrap"
          >
            ← Kembali
          </Link>
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ letter.title }}</h1>
            <p class="mt-0.5 text-xs text-gray-500 font-mono">{{ letter.no_surat }}</p>
            <p class="mt-0.5 text-xs text-gray-400">
              Dicetak: {{ formatDate(letter.printed_at) }}
              <span v-if="letter.printed_by"> · oleh <span class="font-medium text-gray-600">{{ letter.printed_by.name }}</span></span>
            </p>
          </div>
        </div>
        <button
          type="button"
          class="rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition whitespace-nowrap"
          onclick="window.print()"
        >
          Cetak / PDF
        </button>
      </div>

      <!-- Template Surat -->
      <DomisiliTemplate  v-if="isDomisili"  :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <KelahiranTemplate v-else-if="isKelahiran" :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <KematianTemplate  v-else-if="isKematian"  :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <PindahTemplate    v-else-if="isPindah"    :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />

      <div v-else class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-400 italic">
        Pratinjau tidak tersedia untuk surat ini.
      </div>

      <!-- ── Dokumen Pendukung Accordion ─────────────────────────────── -->
      <div class="print:hidden rounded-xl border border-amber-200 bg-white overflow-hidden">
        <!-- Header / toggle -->
        <button
          type="button"
          class="w-full flex items-center justify-between px-5 py-3 bg-amber-50 hover:bg-amber-100 transition"
          @click="dokOpen = !dokOpen"
        >
          <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-semibold text-amber-800">
              Dokumen Pendukung
              <span class="ml-1 rounded-full bg-amber-200 text-amber-800 text-xs px-2 py-0.5">{{ documents.length }}</span>
            </span>
          </div>
          <!-- chevron -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 text-amber-500 transition-transform duration-200"
            :class="{ 'rotate-180': dokOpen }"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Body -->
        <div v-show="dokOpen">
          <!-- Kosong -->
          <div v-if="documents.length === 0" class="px-5 py-6 text-center text-sm text-gray-400 italic">
            Tidak ada dokumen pendukung untuk surat ini.
          </div>

          <!-- Daftar dokumen -->
          <ul v-else class="divide-y divide-gray-100">
            <li
              v-for="(doc, idx) in documents"
              :key="doc.id"
              class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition"
            >
              <!-- Ikon tipe -->
              <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                   :class="isImage(doc.mime_type) ? 'bg-blue-50' : 'bg-red-50'">
                <!-- Ikon gambar -->
                <svg v-if="isImage(doc.mime_type)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <!-- Ikon PDF / file -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
              </div>

              <!-- Label + nama file -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ doc.doc_label }}</p>
                <p v-if="doc.original_name" class="text-xs text-gray-400 truncate">{{ doc.original_name }}</p>
              </div>

              <!-- Tombol Lihat -->
              <button
                type="button"
                class="flex-shrink-0 rounded-lg bg-gradient-to-r from-purple-600 to-fuchsia-500 px-3 py-1.5 text-xs font-semibold text-white hover:from-purple-700 hover:to-fuchsia-600 shadow-sm transition"
                @click="openViewer(doc)"
              >
                Lihat
              </button>
            </li>
          </ul>
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
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-sm font-semibold text-gray-800">{{ viewDoc.doc_label }}</span>
            </div>
            <div class="flex items-center gap-2">
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
                class="rounded-lg border border-gray-200 px-2 py-1.5 text-gray-500 hover:bg-gray-50 transition"
                @click="closeViewer"
                title="Tutup"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Konten -->
          <div class="flex-1 overflow-auto flex items-center justify-center p-4 bg-gray-50 min-h-0">
            <!-- Gambar -->
            <img
              v-if="isImage(viewDoc.mime_type)"
              :src="viewDoc.url"
              :alt="viewDoc.doc_label"
              class="max-w-full max-h-full object-contain rounded-lg shadow"
            />
            <!-- PDF via iframe -->
            <iframe
              v-else-if="isPdf(viewDoc.mime_type)"
              :src="viewDoc.url"
              class="w-full h-[70vh] rounded-lg border-0"
              title="PDF Viewer"
            />
            <!-- File lain -->
            <div v-else class="text-center space-y-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              <p class="text-sm text-gray-500">Pratinjau tidak tersedia</p>
              <a
                :href="viewDoc.url"
                target="_blank"
                class="inline-block rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition"
              >
                Buka / Unduh File
              </a>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
