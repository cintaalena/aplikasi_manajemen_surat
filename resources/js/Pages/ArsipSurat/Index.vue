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
</script>

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
            <tr
              v-for="(row, idx) in letters.data"
              :key="row.id"
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
                <a
                  v-if="!row.is_manual && row.template_slug"
                  :href="`/arsip-surat/${row.id}/pratinjau`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-block rounded-lg bg-purple-600 px-3 py-1 text-xs font-semibold text-white hover:bg-purple-700 transition whitespace-nowrap"
                >
                  Lihat Surat
                </a>
                <span v-else class="text-gray-300 text-xs">—</span>
              </td>
            </tr>

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
  </AppLayout>
</template>
