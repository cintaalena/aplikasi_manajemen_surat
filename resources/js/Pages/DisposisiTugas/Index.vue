<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  dispositions: Object,
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success)
const flashError   = computed(() => page.props.flash?.error)

const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function markDiterima(id) {
  router.patch(route('disposisi-tugas.diterima', { disposisi: id }), {}, {
    preserveScroll: true,
  })
}

function markSelesai(id) {
  router.patch(route('disposisi-tugas.selesai', { disposisi: id }), {}, {
    preserveScroll: true,
  })
}

const expandedId = ref(null)
const viewDoc    = ref(null)

function toggleRow(id) {
  expandedId.value = expandedId.value === id ? null : id
}
function openViewer(doc) { viewDoc.value = doc }
function closeViewer()   { viewDoc.value = null }
function isImage(mime)   { return mime && mime.startsWith('image/') }
function isPdf(mime)     { return mime === 'application/pdf' }
</script>

<template>
  <AppLayout>
    <div class="space-y-5">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Disposisi Tugas</h1>
        <p class="mt-1 text-sm text-gray-600">
          Daftar surat yang didisposisikan oleh Lurah kepada Anda. Konfirmasi penerimaan, lalu tekan <strong>Selesai</strong> saat tugas selesai dikerjakan.
        </p>
      </div>

      <div v-if="flashSuccess" class="rounded-xl border-2 border-green-200 bg-green-50 p-3 text-green-800 text-sm">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" class="rounded-xl border-2 border-red-200 bg-red-50 p-3 text-red-800 text-sm">
        {{ flashError }}
      </div>

      <div class="rounded-2xl border border-blue-100 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
          <thead class="bg-blue-50 text-gray-700">
            <tr>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Tanggal Disposisi</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Nomor Surat</th>
              <th class="p-3 text-left font-semibold">Judul Surat</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Dari</th>
              <th class="p-3 text-left font-semibold">Catatan</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Status</th>
              <th class="p-3 text-left font-semibold whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(item, idx) in dispositions.data" :key="item.id">
              <tr
                :class="[
                  item.status === 'pending'   ? 'bg-blue-50/60 border-l-4 border-l-blue-400' :
                  item.status === 'diterima'  ? 'bg-yellow-50/60 border-l-4 border-l-yellow-400' :
                  (idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'),
                ]"
                class="border-t border-gray-100 hover:bg-blue-50/30 transition-colors"
              >
                <td class="p-3 text-gray-600 whitespace-nowrap text-xs">
                  {{ formatDate(item.created_at) }}
                </td>

                <td class="p-3 font-mono font-semibold text-gray-900 text-xs whitespace-nowrap">
                  {{ item.letter?.no_surat ?? '-' }}
                </td>

                <td class="p-3 text-gray-700 max-w-xs">
                  <div class="flex items-center gap-2">
                    <span>{{ item.letter?.title ?? '-' }}</span>
                    <span
                      v-if="item.status === 'pending'"
                      class="inline-block rounded-full bg-blue-500 text-white text-xs font-bold px-2 py-0.5 leading-tight whitespace-nowrap flex-shrink-0"
                    >Baru</span>
                    <span
                      v-else-if="item.status === 'diterima'"
                      class="inline-block rounded-full bg-yellow-400 text-white text-xs font-bold px-2 py-0.5 leading-tight whitespace-nowrap flex-shrink-0"
                    >Diterima</span>
                  </div>
                </td>

                <td class="p-3 text-gray-700 text-xs whitespace-nowrap">
                  {{ item.from_user?.name ?? '-' }}
                  <span v-if="item.from_user?.jabatan" class="text-gray-400">({{ item.from_user.jabatan }})</span>
                </td>

                <td class="p-3 text-gray-600 text-xs max-w-xs">
                  <span v-if="item.catatan" class="italic">{{ item.catatan }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>

                <td class="p-3 whitespace-nowrap">
                  <span
                    v-if="item.status === 'pending'"
                    class="inline-block rounded-full bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5"
                  >Menunggu</span>
                  <span
                    v-else-if="item.status === 'diterima'"
                    class="inline-block rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5"
                  >Diterima</span>
                  <span
                    v-else
                    class="inline-block rounded-full bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5"
                  >Selesai</span>
                </td>

                <td class="p-3">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <a
                      v-if="item.letter && !item.letter.is_manual && item.letter.template_slug"
                      :href="`/arsip-surat/${item.letter.id}/pratinjau`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-block rounded-lg bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-800 shadow-sm transition whitespace-nowrap"
                    >
                      Lihat Surat
                    </a>

                    <button
                      v-if="item.letter?.documents?.length"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition whitespace-nowrap"
                      @click="toggleRow(item.id)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      Dokumen
                      <span class="rounded-full bg-amber-200 px-1.5">{{ item.letter.documents.length }}</span>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-3 w-3 transition-transform duration-150"
                        :class="{ 'rotate-180': expandedId === item.id }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                      ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <button
                      v-if="item.status === 'pending'"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 shadow-sm transition whitespace-nowrap"
                      :title="'Konfirmasi bahwa tugas ini sudah diterima'"
                      @click="markDiterima(item.id)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                      </svg>
                      Konfirmasi Diterima
                    </button>

                    <button
                      v-if="item.status === 'diterima'"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700 shadow-sm transition whitespace-nowrap"
                      :title="'Tandai tugas ini sudah selesai dikerjakan'"
                      @click="markSelesai(item.id)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      Selesai
                    </button>

                    <span v-if="item.status === 'selesai' && !item.letter?.documents?.length && (item.letter?.is_manual || !item.letter?.template_slug)" class="text-gray-300 text-xs">—</span>
                  </div>
                </td>
              </tr>

              <tr
                v-if="expandedId === item.id && item.letter?.documents?.length"
                class="border-t border-amber-100"
              >
                <td colspan="7" class="px-5 py-4 bg-amber-50">
                  <p class="text-xs font-semibold text-amber-700 mb-3">Dokumen Surat yang Didisposisikan</p>
                  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div
                      v-for="(doc, dIdx) in item.letter.documents"
                      :key="doc.id"
                      class="flex flex-col rounded-xl border border-amber-200 bg-white overflow-hidden shadow-sm"
                    >
                      <div class="px-2 py-1.5 bg-amber-50 border-b border-amber-100">
                        <p class="text-xs font-semibold text-amber-800 leading-tight truncate" :title="doc.doc_label">
                          {{ dIdx + 1 }}. {{ doc.doc_label }}
                        </p>
                      </div>
                      <div
                        class="bg-gray-100 flex items-center justify-center cursor-pointer hover:opacity-90 transition"
                        style="min-height: 140px;"
                        @click="openViewer(doc)"
                      >
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

            <tr v-if="!dispositions.data.length">
              <td class="p-6 text-gray-500 italic text-center" colspan="7">
                Belum ada tugas disposisi yang diberikan kepada Anda.
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm">
          <div class="text-gray-500">
            Menampilkan
            <span class="font-semibold text-gray-800">{{ dispositions.from ?? 0 }}</span>–<span class="font-semibold text-gray-800">{{ dispositions.to ?? 0 }}</span>
            dari
            <span class="font-semibold text-gray-800">{{ dispositions.total }}</span>
            tugas
          </div>

          <div class="flex gap-2">
            <a
              v-if="dispositions.prev_page_url"
              :href="dispositions.prev_page_url"
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition"
            >← Prev</a>
            <span v-else class="rounded-lg border border-gray-100 px-3 py-1.5 text-gray-300 cursor-not-allowed">← Prev</span>

            <span class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-blue-700 font-semibold">
              {{ dispositions.current_page }}
            </span>

            <a
              v-if="dispositions.next_page_url"
              :href="dispositions.next_page_url"
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition"
            >Next →</a>
            <span v-else class="rounded-lg border border-gray-100 px-3 py-1.5 text-gray-300 cursor-not-allowed">Next →</span>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="viewDoc" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70" @click="closeViewer"></div>
        <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl overflow-hidden z-10 flex flex-col" style="max-height: 90vh;">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 shrink-0">
            <div>
              <p class="text-sm font-semibold text-gray-900">{{ viewDoc.doc_label }}</p>
              <p class="text-xs text-gray-400">{{ viewDoc.original_name }}</p>
            </div>
            <button type="button" @click="closeViewer" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="flex-1 overflow-auto bg-gray-900 flex items-center justify-center p-4">
            <img
              v-if="isImage(viewDoc.mime_type)"
              :src="viewDoc.url"
              :alt="viewDoc.doc_label"
              class="max-w-full max-h-full object-contain rounded"
            />
            <iframe
              v-else-if="isPdf(viewDoc.mime_type)"
              :src="viewDoc.url"
              class="w-full rounded bg-white"
              style="height: 70vh;"
            ></iframe>
            <div v-else class="flex flex-col items-center gap-3 text-white">
              <p class="text-sm">Pratinjau tidak tersedia</p>
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
