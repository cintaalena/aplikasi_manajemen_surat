<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue'
import axios from 'axios'

const exportData = () => {
  window.location.href = '/dashboard/export-excel'
}

const activeTab = ref('surat')

const dashboardTabs = [
  { key: 'surat', label: 'Jumlah Surat yang Dibuat' },
  { key: 'penduduk', label: 'Penduduk Kelurahan Fatubesi' },
  { key: 'gender', label: 'Pengelompokan Berdasarkan Gender' },
  { key: 'kk', label: 'Pengelompokan Kepala Keluarga' },
  { key: 'usia', label: 'Pengelompokan Berdasarkan Usia' },
  { key: 'pekerjaan', label: 'Pengelompokan Berdasarkan Pekerjaan' },
  { key: 'pendidikan', label: 'Pengelompokan Berdasarkan Pendidikan' },
]

const activeTabLabel = computed(() => {
  return dashboardTabs.find(tab => tab.key === activeTab.value)?.label || ''
})

const loading = ref(false)
const error = ref('')
const metrics = ref({})

// ── Month picker ──────────────────────────────────────────────────────────────
const now = new Date()
const selectedYear  = ref(now.getFullYear())
const selectedMonth = ref(now.getMonth() + 1) // 1-12

const monthLoadingState = ref(false)
const monthDetail = ref(null) // data dari /dashboard/letters-by-month

// Daftar bulan [1..12]
const monthNames = [
  'Januari','Februari','Maret','April','Mei','Juni',
  'Juli','Agustus','September','Oktober','November','Desember',
]

// Daftar tahun yang tersedia (5 tahun ke belakang s.d. tahun ini)
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

const fetchLettersByMonth = async () => {
  try {
    monthLoadingState.value = true
    const res = await fetch(
      route('dashboard.letters-by-month') +
        `?year=${selectedYear.value}&month=${selectedMonth.value}`,
      { headers: { Accept: 'application/json' }, credentials: 'same-origin' }
    )
    if (!res.ok) throw new Error('Gagal mengambil data bulan.')
    monthDetail.value = await res.json()
  } catch (e) {
    monthDetail.value = null
  } finally {
    monthLoadingState.value = false
  }
}

watch([selectedYear, selectedMonth], () => {
  expandedWeek.value = null
  fetchLettersByMonth()
})
// ─────────────────────────────────────────────────────────────────────────────

// Accordion: nomor minggu yang sedang dibuka, null = semua tertutup
const expandedWeek = ref(null)
const toggleWeek = (weekNum) => {
  expandedWeek.value = expandedWeek.value === weekNum ? null : weekNum
}

let timer = null

const fetchMetrics = async () => {
  try {
    loading.value = true
    error.value = ''

    const res = await fetch(route('dashboard.metrics'), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    })

    if (!res.ok) throw new Error('Gagal mengambil data dashboard.')

    metrics.value = await res.json()
  } catch (e) {
    error.value = e?.message ?? 'Terjadi kesalahan.'
  } finally {
    loading.value = false
  }
}

const refreshData = async () => {
  await fetchMetrics()
}

onMounted(async () => {
  await fetchMetrics()
  await fetchLettersByMonth()
  timer = setInterval(fetchMetrics, 15000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

// Helpers
const fmt = (val) => String(parseInt(val, 10))
const num = (val) => new Intl.NumberFormat('id-ID').format(Number(val || 0))

// Kelompokkan semua jiwa per RW
const groupedGenderByRw = computed(() => {
  const rows = metrics.value?.gender_per_rt_rw ?? []
  const subtotals = metrics.value?.subtotal_per_rw ?? []

  const subtotalMap = {}
  subtotals.forEach(s => { subtotalMap[s.rw] = s })

  const groups = {}
  rows.forEach(row => {
    if (!groups[row.rw]) {
      groups[row.rw] = {
        rw: row.rw,
        rts: [],
        subtotal: subtotalMap[row.rw] ?? { laki_laki: 0, perempuan: 0, total: 0 },
      }
    }
    groups[row.rw].rts.push(row)
  })

  return Object.values(groups).sort((a, b) => parseInt(a.rw) - parseInt(b.rw))
})

// Kelompokkan kepala keluarga per RW
const groupedKkByRw = computed(() => {
  const rows = metrics.value?.kk_per_rt_rw ?? []
  const subtotals = metrics.value?.kk_subtotal_per_rw ?? []

  const subtotalMap = {}
  subtotals.forEach(s => { subtotalMap[s.rw] = s })

  const groups = {}
  rows.forEach(row => {
    if (!groups[row.rw]) {
      groups[row.rw] = {
        rw: row.rw,
        rts: [],
        subtotal: subtotalMap[row.rw] ?? { kk_laki_laki: 0, kk_perempuan: 0, total_kk: 0 },
      }
    }
    groups[row.rw].rts.push(row)
  })

  return Object.values(groups).sort((a, b) => parseInt(a.rw) - parseInt(b.rw))
})

const ageGroupRows = computed(() => {
  return metrics.value?.age_groups?.rows ?? []
})

const ageGroupTotals = computed(() => {
  return metrics.value?.age_groups?.totals ?? {
    laki_laki: 0,
    perempuan: 0,
    jumlah: 0,
    sex_rasio: 0,
  }
})

const jobGroupRows = computed(() => {
  return metrics.value?.job_groups?.rows ?? []
})

const jobGroupTotals = computed(() => {
  return metrics.value?.job_groups?.totals ?? {
    laki_laki: 0,
    perempuan: 0,
    jumlah: 0,
  }
})

const educationGroupRows = computed(() => {
  return metrics.value?.education_groups?.rows ?? []
})

const educationGroupTotals = computed(() => {
  return metrics.value?.education_groups?.totals ?? {
    laki_laki: 0,
    perempuan: 0,
    jumlah: 0,
  }
})
</script>

<template>
  <Head title="Home" />

  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

        <div class="flex items-center justify-between gap-3">
          <h1 class="text-2xl font-bold text-gray-800">Home (Monitoring)</h1>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="refreshData"
              :disabled="loading"
              class="inline-flex items-center gap-2 rounded-xl border border-purple-200 bg-white px-4 py-2.5 text-sm font-semibold text-purple-700 shadow-sm transition hover:bg-purple-50 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 4a8 8 0 0 1 7.75 6h-2.08A6 6 0 1 0 18 13h-3l4-4 4 4h-3a8 8 0 1 1-8-9Z"/>
              </svg>
              {{ loading ? 'Memuat...' : 'Refresh' }}
            </button>

            <button
              type="button"
              @click="exportData"
              class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 16a1 1 0 0 1-.707-.293l-4-4 1.414-1.414L11 12.586V3h2v9.586l2.293-2.293 1.414 1.414-4 4A1 1 0 0 1 12 16ZM5 19h14v2H5v-2Z"/>
              </svg>
              Export Data
            </button>
          </div>
        </div>

        <div v-if="error" class="p-3 rounded-xl border border-red-200 bg-red-50 text-red-700">
          {{ error }}
        </div>

        <!-- Menu Tab -->
        <div class="overflow-x-auto">
          <div class="flex min-w-max gap-2 rounded-2xl border border-amber-200 bg-white p-2 shadow-sm">
            <button
              v-for="tab in dashboardTabs"
              :key="tab.key"
              type="button"
              @click="activeTab = tab.key"
              class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-medium transition"
              :class="
                activeTab === tab.key
                  ? 'bg-amber-500 text-white shadow'
                  : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
              "
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Judul tab aktif -->
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ activeTabLabel }}
          </h2>
        </div>

        <!-- ========================================================= -->
        <!-- TAB: JUMLAH SURAT -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'surat'" class="space-y-6">

          <!-- Cards Ringkasan Cepat -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
              <div class="text-sm text-gray-500">Surat hari ini</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.letters?.today ?? 0 }}
              </div>
            </div>

            <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
              <div class="text-sm text-gray-500">Surat minggu ini</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.letters?.week ?? 0 }}
              </div>
            </div>

            <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
              <div class="text-sm text-gray-500">Surat bulan ini</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.letters?.month ?? 0 }}
              </div>
            </div>

            <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
              <div class="text-sm text-gray-500">Surat tahun ini</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.letters?.year ?? 0 }}
              </div>
            </div>
          </div>

          <!-- Tabel Ringkasan 12 Bulan Terakhir -->
          <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800">Ringkasan 12 Bulan Terakhir</h2>
            <p class="text-sm text-gray-500 mb-3">Klik baris bulan untuk melihat detail surat bulan tersebut.</p>

            <div v-if="loading" class="text-gray-500">Memuat...</div>

            <div v-else class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="bg-purple-50 text-gray-600">
                    <th class="px-4 py-2 text-left font-semibold">Bulan</th>
                    <th class="px-4 py-2 text-center font-semibold">Jumlah Surat</th>
                    <th class="px-4 py-2 text-center font-semibold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(row, idx) in (metrics?.letters_monthly_12 ?? [])"
                    :key="idx"
                    :class="[
                      idx % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                      selectedYear === row.year && selectedMonth === row.month
                        ? 'ring-2 ring-inset ring-purple-400'
                        : '',
                    ]"
                    class="border-t border-gray-100 hover:bg-purple-50 transition-colors cursor-pointer"
                    @click="selectedYear = row.year; selectedMonth = row.month"
                  >
                    <td class="px-4 py-3 font-medium text-gray-700">{{ row.month_label }}</td>
                    <td class="px-4 py-3 text-center">
                      <span class="inline-block min-w-[2.5rem] rounded-full bg-purple-100 text-purple-700 font-bold px-3 py-0.5">
                        {{ row.total }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <button
                        type="button"
                        class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700 hover:bg-purple-100 transition"
                        @click.stop="selectedYear = row.year; selectedMonth = row.month"
                      >
                        Lihat Detail
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Panel Detail Bulan Dipilih -->
          <div class="rounded-2xl border border-amber-200 bg-white p-4 shadow-sm space-y-4">
            <!-- Header + Pilih Bulan -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-gray-800">Detail Surat per Bulan</h2>
                <p class="text-sm text-gray-500">Pilih tahun dan bulan untuk melihat rincian.</p>
              </div>

              <!-- Selector tahun & bulan -->
              <div class="flex items-center gap-2 flex-wrap">
                <select
                  v-model.number="selectedYear"
                  class="rounded-xl border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:outline-none"
                >
                  <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                </select>

                <select
                  v-model.number="selectedMonth"
                  class="rounded-xl border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:outline-none"
                >
                  <option v-for="(name, idx) in monthNames" :key="idx+1" :value="idx+1">
                    {{ name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Loading state -->
            <div v-if="monthLoadingState" class="text-gray-500 text-sm">Memuat data bulan...</div>

            <!-- No data -->
            <div v-else-if="!monthDetail" class="text-gray-400 italic text-sm">Data tidak tersedia.</div>

            <template v-else>
              <!-- Total & judul bulan -->
              <div class="flex items-center gap-4 rounded-xl bg-amber-50 border border-amber-200 p-4">
                <div>
                  <div class="text-sm text-gray-500">Total Surat</div>
                  <div class="text-4xl font-bold text-amber-600">{{ monthDetail.total }}</div>
                </div>
                <div class="text-lg font-semibold text-gray-700">{{ monthDetail.month_label }}</div>
              </div>

              <!-- Per Minggu dalam Bulan — Accordion -->
              <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Rincian per Minggu</h3>
                <div class="space-y-2">
                  <div
                    v-for="w in monthDetail.by_week"
                    :key="w.week"
                    class="rounded-xl border border-gray-200 overflow-hidden"
                  >
                    <!-- Header minggu — klik untuk expand -->
                    <button
                      type="button"
                      class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                      @click="toggleWeek(w.week)"
                    >
                      <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-800">Minggu {{ w.week }}</span>
                        <span class="text-xs text-gray-500">{{ w.start }} s.d. {{ w.end }}</span>
                        <!-- Badge ringkasan template -->
                        <span
                          v-for="ts in w.template_summary"
                          :key="ts.template_slug"
                          class="hidden sm:inline-block rounded-full bg-purple-100 text-purple-700 text-xs font-medium px-2 py-0.5"
                        >
                          {{ ts.label }}: {{ ts.total }}
                        </span>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <span class="rounded-full bg-amber-100 text-amber-700 font-bold text-sm px-3 py-0.5">
                          {{ w.total }} surat
                        </span>
                        <!-- Chevron icon -->
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-4 w-4 text-gray-400 transition-transform"
                          :class="expandedWeek === w.week ? 'rotate-180' : ''"
                          viewBox="0 0 24 24" fill="currentColor"
                        >
                          <path d="M7 10l5 5 5-5z"/>
                        </svg>
                      </div>
                    </button>

                    <!-- Body accordion: daftar surat -->
                    <div v-if="expandedWeek === w.week">
                      <div v-if="w.letters.length === 0" class="px-4 py-3 text-sm text-gray-400 italic">
                        Tidak ada surat dibuat minggu ini.
                      </div>

                      <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                          <thead>
                            <tr class="bg-amber-50 text-gray-600 text-xs">
                              <th class="px-4 py-2 text-left font-semibold">#</th>
                              <th class="px-4 py-2 text-left font-semibold">No. Surat</th>
                              <th class="px-4 py-2 text-left font-semibold">Jenis Surat</th>
                              <th class="px-4 py-2 text-left font-semibold">Judul</th>
                              <th class="px-4 py-2 text-left font-semibold">Tanggal</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr
                              v-for="(letter, li) in w.letters"
                              :key="letter.id"
                              :class="li % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                              class="border-t border-gray-100"
                            >
                              <td class="px-4 py-2 text-gray-500 text-xs">{{ li + 1 }}</td>
                              <td class="px-4 py-2 font-mono text-xs text-gray-700 whitespace-nowrap">
                                {{ letter.no_surat }}
                              </td>
                              <td class="px-4 py-2">
                                <span class="rounded-full bg-purple-100 text-purple-700 text-xs font-medium px-2 py-0.5 whitespace-nowrap">
                                  {{ letter.label }}
                                </span>
                              </td>
                              <td class="px-4 py-2 text-gray-700 text-xs max-w-xs truncate">
                                {{ letter.title }}
                              </td>
                              <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">
                                {{ letter.tanggal }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Per Template -->
              <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Rincian per Jenis Surat</h3>
                <div class="space-y-2">
                  <div
                    v-for="(t, idx) in monthDetail.by_template"
                    :key="idx"
                    class="flex items-center justify-between rounded-xl border border-gray-100 bg-white p-3"
                  >
                    <div class="min-w-0">
                      <div class="font-semibold text-gray-800 truncate">{{ t.label }}</div>
                      <div class="text-xs text-gray-400 truncate">{{ t.template_slug }}</div>
                    </div>
                    <span class="ml-4 rounded-full bg-purple-100 text-purple-700 font-bold px-3 py-0.5 text-sm">
                      {{ t.total }}
                    </span>
                  </div>

                  <div v-if="monthDetail.by_template.length === 0" class="text-gray-400 italic text-sm">
                    Tidak ada surat dibuat pada bulan ini.
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Top Surat 30 hari terakhir -->
          <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800">
              Surat paling sering dibuat (30 hari terakhir)
            </h2>
            <p class="text-sm text-gray-500">Top 10 berdasarkan template</p>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat...</div>

            <div v-else class="mt-4 space-y-2">
              <div
                v-for="(t, idx) in (metrics?.top_templates_30d ?? [])"
                :key="idx"
                class="flex items-center justify-between rounded-xl border border-gray-100 p-3"
              >
                <div class="min-w-0">
                  <div class="font-semibold text-gray-800 truncate">{{ t.label }}</div>
                  <div class="text-xs text-gray-500 truncate">{{ t.template_slug }}</div>
                </div>
                <div class="font-bold text-gray-900">{{ t.total }}</div>
              </div>

              <div v-if="(metrics?.top_templates_30d ?? []).length === 0" class="text-gray-500">
                Belum ada data surat 30 hari terakhir.
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB: PENDUDUK -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'penduduk'" class="space-y-6">

          <!-- Ringkasan Penduduk -->
          <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800">Penduduk Kelurahan Fatubesi</h2>
            <p class="text-sm text-gray-500">
              Update otomatis mengikuti perubahan data (polling 15 detik).
            </p>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Jumlah RT</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.jumlah_rt ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Jumlah RW</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.jumlah_rw ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Jumlah Jiwa</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.jumlah_jiwa ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Total Laki-laki</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.total_laki_laki ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Total Perempuan</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.total_perempuan ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Jumlah Kepala Keluarga</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.jumlah_kepala_keluarga ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4 sm:col-span-2 xl:col-span-3">
                <div class="text-sm text-gray-500">Total KK</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.population?.total_kk ?? 0 }}
                </div>
              </div>
            </div>
          </div>

          <!-- Agama -->
          <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800">Pengelompokan Berdasarkan Agama</h2>
            <p class="text-sm text-gray-500">
              Data jumlah penduduk berdasarkan agama yang terdaftar di Kelurahan Fatubesi.
            </p>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Kristen</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.kristen ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Islam</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.islam ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Katholik</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.katholik ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Hindu</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.hindu ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Buddha</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.buddha ?? 0 }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-sm text-gray-500">Konghucu</div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ loading ? '...' : metrics?.agama?.konghucu ?? 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB: GENDER -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'gender'" class="space-y-6">
          <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800">
              Pengelompokan Penduduk Berdasarkan Jenis Kelamin per RT/RW
            </h2>
            <p class="text-sm text-gray-500">
              Jumlah laki-laki dan perempuan untuk setiap RT dalam setiap RW.
            </p>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

            <div v-else-if="groupedGenderByRw.length === 0" class="mt-4 text-gray-500 italic">
              Belum ada data penduduk.
            </div>

            <div v-else class="mt-4 space-y-6">
              <div
                v-for="group in groupedGenderByRw"
                :key="group.rw"
                class="rounded-xl border border-purple-50 overflow-hidden"
              >
                <div class="bg-purple-600 px-4 py-2 flex items-center justify-between">
                  <span class="text-white font-bold">RW {{ fmt(group.rw) }}</span>
                  <div class="flex gap-4 text-sm text-purple-100">
                    <span>&#9794; {{ group.subtotal.laki_laki }}</span>
                    <span>&#9792; {{ group.subtotal.perempuan }}</span>
                    <span class="text-white font-semibold">Total: {{ group.subtotal.total }}</span>
                  </div>
                </div>

                <div class="overflow-x-auto">
                  <table class="min-w-full text-sm">
                    <thead>
                      <tr class="bg-purple-50 text-gray-600">
                        <th class="px-4 py-2 text-left font-semibold">Lokasi</th>
                        <th class="px-4 py-2 text-center font-semibold text-blue-600">&#9794; Laki-laki</th>
                        <th class="px-4 py-2 text-center font-semibold text-pink-500">&#9792; Perempuan</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700">Jumlah</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr
                        v-for="(rt, idx) in group.rts"
                        :key="rt.rt"
                        :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                        class="border-t border-gray-100 hover:bg-purple-50 transition-colors"
                      >
                        <td class="px-4 py-3 font-medium text-gray-700">
                          RT {{ fmt(rt.rt) }} / RW {{ fmt(rt.rw) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                            {{ rt.laki_laki }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">
                            {{ rt.perempuan }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                          {{ rt.total }}
                        </td>
                      </tr>

                      <tr class="border-t-2 border-purple-200 bg-purple-50 font-bold">
                        <td class="px-4 py-3 text-purple-700">Total RW {{ fmt(group.rw) }}</td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-200 text-blue-800 font-bold px-2 py-0.5">
                            {{ group.subtotal.laki_laki }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-200 text-pink-700 font-bold px-2 py-0.5">
                            {{ group.subtotal.perempuan }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center text-purple-800 font-bold text-base">
                          {{ group.subtotal.total }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="rounded-xl border-2 border-purple-300 bg-purple-600 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <span class="text-white font-bold">Grand Total Seluruh RT/RW</span>
                <div class="flex gap-6 text-sm font-semibold">
                  <div class="flex flex-col items-center">
                    <span class="text-purple-200 text-xs">&#9794; Laki-laki</span>
                    <span class="text-white text-xl font-bold">{{ metrics?.population?.total_laki_laki ?? 0 }}</span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-purple-200 text-xs">&#9792; Perempuan</span>
                    <span class="text-white text-xl font-bold">{{ metrics?.population?.total_perempuan ?? 0 }}</span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-purple-200 text-xs">Total Jiwa</span>
                    <span class="text-white text-xl font-bold">{{ metrics?.population?.jumlah_jiwa ?? 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB: KEPALA KELUARGA -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'kk'" class="space-y-6">
          <div class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
              </div>

              <div>
                <h2 class="text-lg font-semibold text-gray-800">
                  Pengelompokan Kepala Keluarga Berdasarkan Jenis Kelamin per RT/RW
                </h2>
                <p class="text-sm text-gray-500">
                  Jumlah Kepala Keluarga laki-laki dan perempuan di setiap RT dalam setiap RW.
                </p>
              </div>
            </div>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

            <div v-else-if="groupedKkByRw.length === 0" class="mt-4 text-gray-500 italic">
              Belum ada data kepala keluarga yang terdaftar.
            </div>

            <div v-else class="mt-4 space-y-6">
              <div
                v-for="group in groupedKkByRw"
                :key="group.rw"
                class="rounded-xl border border-emerald-100 overflow-hidden"
              >
                <div class="bg-emerald-600 px-4 py-2 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-200" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    <span class="text-white font-bold text-base">RW {{ fmt(group.rw) }}</span>
                  </div>

                  <div class="flex gap-4 text-sm text-emerald-100">
                    <span>&#9794; KK Laki-laki: <strong class="text-white">{{ group.subtotal.kk_laki_laki }}</strong></span>
                    <span>&#9792; KK Perempuan: <strong class="text-white">{{ group.subtotal.kk_perempuan }}</strong></span>
                    <span class="text-white font-semibold">Total KK: {{ group.subtotal.total_kk }}</span>
                  </div>
                </div>

                <div class="overflow-x-auto">
                  <table class="min-w-full text-sm">
                    <thead>
                      <tr class="bg-emerald-50 text-gray-600">
                        <th class="px-4 py-2 text-left font-semibold w-1/3">Lokasi</th>
                        <th class="px-4 py-2 text-center font-semibold text-blue-600">&#9794; KK Laki-laki</th>
                        <th class="px-4 py-2 text-center font-semibold text-pink-500">&#9792; KK Perempuan</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700">Total KK</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr
                        v-for="(rt, idx) in group.rts"
                        :key="rt.rt"
                        :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                        class="border-t border-gray-100 hover:bg-emerald-50 transition-colors"
                      >
                        <td class="px-4 py-3 font-medium text-gray-700">
                          RT {{ fmt(rt.rt) }} / RW {{ fmt(rt.rw) }}
                        </td>

                        <td class="px-4 py-3 text-center">
                          <div class="flex items-center justify-center gap-1.5">
                            <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                              {{ rt.kk_laki_laki }}
                            </span>
                            <div class="hidden sm:block w-16 bg-gray-100 rounded-full h-1.5" v-if="rt.total_kk > 0">
                              <div
                                class="bg-blue-400 h-1.5 rounded-full"
                                :style="{ width: ((rt.kk_laki_laki / rt.total_kk) * 100).toFixed(1) + '%' }"
                              ></div>
                            </div>
                            <span class="hidden sm:inline text-xs text-gray-400" v-if="rt.total_kk > 0">
                              {{ ((rt.kk_laki_laki / rt.total_kk) * 100).toFixed(0) }}%
                            </span>
                          </div>
                        </td>

                        <td class="px-4 py-3 text-center">
                          <div class="flex items-center justify-center gap-1.5">
                            <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">
                              {{ rt.kk_perempuan }}
                            </span>
                            <div class="hidden sm:block w-16 bg-gray-100 rounded-full h-1.5" v-if="rt.total_kk > 0">
                              <div
                                class="bg-pink-400 h-1.5 rounded-full"
                                :style="{ width: ((rt.kk_perempuan / rt.total_kk) * 100).toFixed(1) + '%' }"
                              ></div>
                            </div>
                            <span class="hidden sm:inline text-xs text-gray-400" v-if="rt.total_kk > 0">
                              {{ ((rt.kk_perempuan / rt.total_kk) * 100).toFixed(0) }}%
                            </span>
                          </div>
                        </td>

                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                          {{ rt.total_kk }}
                        </td>
                      </tr>

                      <tr class="border-t-2 border-emerald-300 bg-emerald-50 font-bold">
                        <td class="px-4 py-3 text-emerald-700">
                          Total RW {{ fmt(group.rw) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-200 text-blue-800 font-bold px-2 py-0.5">
                            {{ group.subtotal.kk_laki_laki }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-200 text-pink-700 font-bold px-2 py-0.5">
                            {{ group.subtotal.kk_perempuan }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center text-emerald-800 font-bold text-base">
                          {{ group.subtotal.total_kk }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="rounded-xl border-2 border-emerald-400 bg-emerald-600 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                  <div class="text-white font-bold text-base">
                    Grand Total KK Seluruh Kelurahan Fatubesi
                  </div>
                  <div class="text-emerald-200 text-xs mt-0.5">
                    Kepala Keluarga laki-laki + perempuan dari semua RT/RW
                  </div>
                </div>

                <div class="flex gap-6 text-sm font-semibold">
                  <div class="flex flex-col items-center bg-emerald-700 rounded-xl px-4 py-2">
                    <span class="text-emerald-200 text-xs">&#9794; KK Laki-laki</span>
                    <span class="text-white text-2xl font-bold">
                      {{ metrics?.kk_grand_total?.kk_laki_laki ?? 0 }}
                    </span>
                  </div>

                  <div class="flex flex-col items-center bg-emerald-700 rounded-xl px-4 py-2">
                    <span class="text-emerald-200 text-xs">&#9792; KK Perempuan</span>
                    <span class="text-white text-2xl font-bold">
                      {{ metrics?.kk_grand_total?.kk_perempuan ?? 0 }}
                    </span>
                  </div>

                  <div class="flex flex-col items-center bg-white/20 rounded-xl px-4 py-2">
                    <span class="text-emerald-100 text-xs">Total KK</span>
                    <span class="text-white text-2xl font-bold">
                      {{ metrics?.kk_grand_total?.total_kk ?? 0 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB: USIA -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'usia'" class="space-y-6">
          <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5zm0 2c-3.866 0-7 2.239-7 5v1h14v-1c0-2.761-3.134-5-7-5z"/>
                </svg>
              </div>

              <div>
                <h2 class="text-lg font-semibold text-gray-800">
                  Pengelompokan Penduduk Berdasarkan Kelompok Umur
                </h2>
                <p class="text-sm text-gray-500">
                  Data dihitung dari tanggal lahir penduduk, lalu dikelompokkan berdasarkan rentang umur.
                  Sex rasio dihitung dengan rumus <strong>(Laki-laki / Perempuan) × 100</strong>.
                </p>
              </div>
            </div>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

            <div v-else-if="ageGroupRows.length === 0" class="mt-4 text-gray-500 italic">
              Belum ada data kelompok umur.
            </div>

            <div v-else class="mt-4 overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200">
                <thead>
                  <tr class="bg-amber-100 text-gray-700">
                    <th rowspan="2" class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Kelompok Umur (Tahun)
                    </th>
                    <th colspan="2" class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Jumlah Penduduk
                    </th>
                    <th rowspan="2" class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Jumlah
                    </th>
                    <th rowspan="2" class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Sex Rasio
                    </th>
                  </tr>
                  <tr class="bg-amber-50 text-gray-700">
                    <th class="border border-gray-300 px-4 py-2 text-center font-semibold text-blue-600">
                      Laki-laki
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center font-semibold text-pink-500">
                      Perempuan
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(row, idx) in ageGroupRows"
                    :key="row.kelompok_umur"
                    :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                  >
                    <td class="border border-gray-200 px-4 py-3 text-center font-medium text-gray-700">
                      {{ row.kelompok_umur }}
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                        {{ num(row.laki_laki) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">
                        {{ num(row.perempuan) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center font-bold text-gray-800">
                      {{ num(row.jumlah) }}
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center font-semibold text-gray-700">
                      {{ num(row.sex_rasio) }}
                    </td>
                  </tr>

                  <tr class="bg-amber-200 font-bold text-gray-800">
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      JUMLAH
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(ageGroupTotals.laki_laki) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(ageGroupTotals.perempuan) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(ageGroupTotals.jumlah) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(ageGroupTotals.sex_rasio) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

                <!-- ========================================================= -->
        <!-- TAB: PEKERJAAN -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'pekerjaan'" class="space-y-6">
          <div class="rounded-2xl border border-cyan-100 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex-shrink-0 w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-cyan-600" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 2a1 1 0 0 0-1 1v2H7a3 3 0 0 0-3 3v2h16V8a3 3 0 0 0-3-3h-2V3a1 1 0 1 0-2 0v2h-2V3a1 1 0 0 0-1-1zM4 12v7a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-7H4z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-semibold text-gray-800">
                  Pengelompokan Penduduk Berdasarkan Pekerjaan / Profesi
                </h2>
                <p class="text-sm text-gray-500">
                  Data pekerjaan ditampilkan secara dinamis mengikuti isi data penduduk.
                  Jika ada pekerjaan baru pada data penduduk, maka pekerjaan tersebut otomatis muncul pada tabel.
                  Data kosong akan dikelompokkan ke <strong>Belum Bekerja</strong>.
                </p>
              </div>
            </div>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

            <div v-else-if="jobGroupRows.length === 0" class="mt-4 text-gray-500 italic">
              Belum ada data pekerjaan.
            </div>

            <div v-else class="mt-4 overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200">
                <thead>
                  <tr class="bg-cyan-100 text-gray-700">
                    <th class="border border-gray-300 px-4 py-3 text-left font-semibold">
                      Pekerjaan / Profesi
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-blue-600">
                      Laki-laki
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-pink-500">
                      Perempuan
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Jumlah
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(row, idx) in jobGroupRows"
                    :key="row.pekerjaan"
                    :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                  >
                    <td class="border border-gray-200 px-4 py-3 font-medium text-gray-700">
                      {{ row.pekerjaan }}
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                        {{ num(row.laki_laki) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">
                        {{ num(row.perempuan) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center font-bold text-gray-800">
                      {{ num(row.jumlah) }}
                    </td>
                  </tr>

                  <tr class="bg-cyan-200 font-bold text-gray-800">
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      TOTAL
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(jobGroupTotals.laki_laki) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(jobGroupTotals.perempuan) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(jobGroupTotals.jumlah) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

                <!-- ========================================================= -->
        <!-- TAB: PENDIDIKAN -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'pendidikan'" class="space-y-6">
          <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3zm-7 9.18V17l7 4 7-4v-4.82l-7 3.82-7-3.82z"/>
                </svg>
              </div>

              <div>
                <h2 class="text-lg font-semibold text-gray-800">
                  Pengelompokan Penduduk Berdasarkan Tingkat Pendidikan Terakhir
                </h2>
                <p class="text-sm text-gray-500">
                  Data diambil dari kolom pendidikan pada database penduduk, lalu dikelompokkan ke dalam kategori pendidikan terakhir.
                </p>
              </div>
            </div>

            <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

            <div v-else-if="educationGroupRows.length === 0" class="mt-4 text-gray-500 italic">
              Belum ada data pendidikan.
            </div>

            <div v-else class="mt-4 overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200">
                <thead>
                  <tr class="bg-indigo-100 text-gray-700">
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Tingkat Pendidikan
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-blue-600">
                      Laki-laki
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-pink-500">
                      Perempuan
                    </th>
                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold">
                      Jumlah
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(row, idx) in educationGroupRows"
                    :key="row.pendidikan"
                    :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                  >
                    <td class="border border-gray-200 px-4 py-3 font-medium text-gray-700">
                      {{ row.pendidikan }}
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                        {{ num(row.laki_laki) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center">
                      <span class="inline-block min-w-[3rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">
                        {{ num(row.perempuan) }}
                      </span>
                    </td>
                    <td class="border border-gray-200 px-4 py-3 text-center font-bold text-gray-800">
                      {{ num(row.jumlah) }}
                    </td>
                  </tr>

                  <tr class="bg-indigo-200 font-bold text-gray-800">
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      TOTAL
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(educationGroupTotals.laki_laki) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(educationGroupTotals.perempuan) }}
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-center">
                      {{ num(educationGroupTotals.jumlah) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Footer info -->
        <div class="text-xs text-gray-400">
          Terakhir update: {{ metrics?.meta?.generated_at ?? '-' }} (time column: {{ metrics?.meta?.time_column ?? '-' }})
        </div>

      </div>
    </div>
  </AppLayout>
</template>