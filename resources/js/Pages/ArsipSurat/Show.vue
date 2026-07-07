<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import KematianTemplate from '@/Components/Surat/KematianTemplate.vue'
import PindahTemplate from '@/Components/Surat/PindahTemplate.vue'
import { computed, reactive, ref } from 'vue'

const props = defineProps({
  letter: Object,
})

// Label dokumen wajib per doc_key, harus sinkron dengan Show.vue (halaman buat surat)
// dan app/Support/LetterDocumentRequirements.php.
const REQUIRED_DOC_LABELS = {
  'keterangan-domisili': {
    suratPengantarRtRwDom: 'Surat Pengantar RT/RW',
    fotoKtpDomisili: 'Fotocopy KTP Pemohon',
  },
  'keterangan-kematian': {
    suratPengantarRtRw: 'Surat Pengantar RT/RW',
    suratKetKematian: 'Dokumen Keterangan Kematian (dari Dokter/Bidan atau Pernyataan Saksi)',
    fotoKtpAlmarhum: 'Fotocopy KTP Almarhum/Almarhumah',
    fotoKkAlmarhum: 'Fotocopy Kartu Keluarga Almarhum/Almarhumah',
    fotoKtpPemohon: 'Fotocopy KTP Pemohon (Pelapor)',
    suratPernyataanPelapor: 'Surat Pernyataan dari Pelapor (ditandatangani 2 saksi & RT)',
  },
  'keterangan-pindah': {
    suratPengantarRt: 'Surat Pengantar dari RT',
    fotoKtpPindah: 'Fotocopy KTP yang akan pindah',
    fotoKkPindah: 'Fotocopy Kartu Keluarga',
    suratKetPasFoto: 'Surat keterangan yang sudah ditempel pas foto',
    pasFotoPindah: 'Pas foto yang akan pindah',
  },
  'keterangan-kelahiran': {
    suratKetLahir: 'Surat Keterangan Lahir dari RS/Bidan/Puskesmas',
    fotoKkKelahiran: 'Kartu Keluarga (KK)',
    fotoKtpAyahIbu: 'Fotocopy KTP Ayah dan Ibu',
    fotoBukuNikah: 'Fotocopy Buku Nikah / Akta Perkawinan',
    fotoKtp2Saksi: 'Fotocopy KTP 2 Orang Saksi',
    suratPengantarRtRwLahir: 'Surat Pengantar RT/RW',
    sptjmDataKelahiran: 'SPTJM Kebenaran Data Kelahiran',
    suratPernyataanBelumAkta: 'Surat Pernyataan Belum Punya Akta',
    fotoIjazahOrtu: 'Fotocopy Ijazah Orang Tua',
    suratKetLahirLN: 'Surat Keterangan Lahir dari RS/Bidan/Puskesmas',
    fotoKkIbu: 'KK Asli dari Ibu',
    fotoKtpAyahIbuLN: 'Fotocopy KTP Ayah dan Ibu',
    fotoKtp2SaksiLN: 'KTP 2 Orang Saksi',
    suratPengantarRtRwLN: 'Surat Pengantar RT/RW',
    sptjmPengakuanAnak: 'SPTJM Pengakuan Anak dari Ayah Biologis',
  },
}

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

const form = computed(() => ({
  ...(props.letter.payload ?? {}),
  noSurat: props.letter.no_surat,
  judulSurat: props.letter.title,
}))

const signer = computed(() => props.letter.signer ?? props.letter.printed_by ?? null)

const slug = computed(() => props.letter.template_slug ?? '')
const isDomisili  = computed(() => slug.value === 'keterangan-domisili')
const isKelahiran = computed(() => slug.value === 'keterangan-kelahiran')
const isKematian  = computed(() => slug.value === 'keterangan-kematian')
const isPindah    = computed(() => slug.value === 'keterangan-pindah')

const documents = computed(() => props.letter.documents ?? [])

const missingDocs = computed(() =>
  (props.letter.missing_required_docs ?? []).map((key) => ({
    key,
    label: REQUIRED_DOC_LABELS[slug.value]?.[key] ?? key,
  }))
)

const uploadingKey = ref(null)
const uploadErrors = reactive({})

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

async function handleMissingDocUpload(docKey, docLabel, event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    uploadErrors[docKey] = 'Ukuran file terlalu besar. Maksimal 5 MB.'
    return
  }

  uploadingKey.value = docKey
  uploadErrors[docKey] = ''

  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_key', docKey)
    fd.append('doc_label', docLabel)
    fd.append('letter_id', props.letter.id)

    const res = await fetch('/surat/dokumen/upload', {
      method: 'POST',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: fd,
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) {
      throw new Error(data?.message ?? `Upload gagal (${res.status})`)
    }

    router.reload({ only: ['letter'] })
  } catch (e) {
    uploadErrors[docKey] = e.message ?? 'Gagal upload dokumen.'
  } finally {
    uploadingKey.value = null
  }
}

const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const dokOpen = ref(true)
const viewDoc = ref(null)

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
          class="rounded-xl bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 shadow-sm transition whitespace-nowrap"
          onclick="window.print()"
        >
          Cetak / PDF
        </button>
      </div>

      <DomisiliTemplate  v-if="isDomisili"  :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <KelahiranTemplate v-else-if="isKelahiran" :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <KematianTemplate  v-else-if="isKematian"  :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />
      <PindahTemplate    v-else-if="isPindah"    :form="form" :tanggalIndo="tanggalIndo" :signer="signer" />

      <div v-else-if="letter.is_manual" class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-400 italic">
        Surat {{ letter.manual_type === 'keluar' ? 'keluar' : 'masuk' }} manual — tidak ada pratinjau, lihat berkas di bagian Dokumen Pendukung di bawah.
      </div>

      <div v-else class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-400 italic">
        Pratinjau tidak tersedia untuk surat ini.
      </div>

      <div class="print:hidden rounded-xl border border-amber-200 bg-white overflow-hidden">
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
            <span
              v-if="missingDocs.length > 0"
              class="inline-flex items-center gap-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5"
            >
              ⚠ {{ missingDocs.length }} belum lengkap
            </span>
          </div>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 text-amber-500 transition-transform duration-200"
            :class="{ 'rotate-180': dokOpen }"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <div v-show="dokOpen">
          <div v-if="missingDocs.length > 0" class="p-4 border-b border-amber-100 bg-red-50/50">
            <p class="text-xs font-semibold text-red-700 mb-3">
              Dokumen berikut belum diupload. Lengkapi agar arsip surat ini lengkap:
            </p>
            <div class="space-y-2">
              <div
                v-for="doc in missingDocs"
                :key="doc.key"
                class="flex items-center justify-between gap-2 rounded-lg bg-white border border-red-200 px-3 py-2"
              >
                <span class="text-xs font-medium text-gray-700">{{ doc.label }}</span>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <span v-if="uploadingKey === doc.key" class="text-xs text-amber-600 italic">Mengupload...</span>
                  <label v-else class="cursor-pointer rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition">
                    Upload
                    <input
                      type="file"
                      accept="image/jpeg,image/png,image/webp,application/pdf"
                      class="hidden"
                      @change="handleMissingDocUpload(doc.key, doc.label, $event)"
                    />
                  </label>
                </div>
              </div>
              <template v-for="doc in missingDocs" :key="`err-${doc.key}`">
                <p v-if="uploadErrors[doc.key]" class="text-xs text-red-600">{{ doc.label }}: {{ uploadErrors[doc.key] }}</p>
              </template>
            </div>
          </div>

          <div v-if="documents.length === 0" class="px-5 py-6 text-center text-sm text-gray-400 italic">
            Tidak ada dokumen pendukung untuk surat ini.
          </div>

          <div v-else class="p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
              <div
                v-for="(doc, idx) in documents"
                :key="doc.id"
                class="flex flex-col rounded-xl border border-amber-200 bg-white overflow-hidden shadow-sm"
              >
                <div class="px-2 py-1.5 bg-amber-50 border-b border-amber-100">
                  <p class="text-xs font-semibold text-amber-800 leading-tight truncate" :title="doc.doc_label">
                    {{ idx + 1 }}. {{ doc.doc_label }}
                  </p>
                </div>

                <div class="bg-gray-100 flex items-center justify-center cursor-pointer hover:bg-gray-200 transition" style="min-height: 140px;" @click="openViewer(doc)">
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
