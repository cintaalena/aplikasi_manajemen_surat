<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'

const btnPurple =
  "rounded-xl px-4 py-2 text-sm font-semibold text-white " +
  "bg-gradient-to-r from-purple-600 to-fuchsia-500 " +
  "hover:from-purple-700 hover:to-fuchsia-600"

const loading = ref(true)
const error   = ref('')
const metrics = ref(null)

let timer = null

const fetchMetrics = async () => {
  try {
    error.value = ''
    const res = await fetch(route('dashboard.metrics'), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error('Gagal mengambil data dashboard.')
    metrics.value = await res.json()
    loading.value = false
  } catch (e) {
    loading.value = false
    error.value = e?.message ?? 'Terjadi kesalahan.'
  }
}

onMounted(async () => {
  await fetchMetrics()
  timer = setInterval(fetchMetrics, 15000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

// ── Helpers ──────────────────────────────────────────────────────────
// Hilangkan leading zero untuk tampilan: '001' → '1'
const fmt = (val) => String(parseInt(val, 10))

// ── Computed: kelompokkan semua jiwa per RW (fitur sebelumnya) ───────
const groupedGenderByRw = computed(() => {
  const rows      = metrics.value?.gender_per_rt_rw ?? []
  const subtotals = metrics.value?.subtotal_per_rw   ?? []

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

// ── Computed: kelompokkan KEPALA KELUARGA per RW ─────────────────────
const groupedKkByRw = computed(() => {
  const rows      = metrics.value?.kk_per_rt_rw       ?? []
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
</script>

<template>
  <Head title="Home" />

  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

        <!-- Judul + tombol refresh -->
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-800">Home (Monitoring)</h1>
          <button :class="btnPurple" type="button" @click="fetchMetrics">Refresh</button>
        </div>

        <div v-if="error" class="p-3 rounded-xl border border-red-200 bg-red-50 text-red-700">
          {{ error }}
        </div>

        <!-- ── Cards Surat ── -->
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

        <!-- ── Top surat 30 hari ── -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-800">Surat paling sering dibuat (30 hari terakhir)</h2>
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

        <!-- ── Ringkasan Penduduk ── -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-800">Penduduk Kelurahan Fatubesi</h2>
          <p class="text-sm text-gray-500">Update otomatis mengikuti perubahan data (polling 15 detik).</p>
          <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Jumlah RT</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.jumlah_rt ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Jumlah RW</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.jumlah_rw ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Jumlah Jiwa</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.jumlah_jiwa ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Total Laki-laki</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.total_laki_laki ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Total Perempuan</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.total_perempuan ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Jumlah Kepala Keluarga</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.jumlah_kepala_keluarga ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4 sm:col-span-2 xl:col-span-3">
              <div class="text-sm text-gray-500">Total KK</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.population?.total_kk ?? 0 }}</div>
              <div class="text-xs text-gray-500 mt-1">Total KK dihitung dari jumlah kode keluarga (No. KK) yang unik.</div>
            </div>
          </div>
        </div>

        <!-- ── Gender semua jiwa per RT/RW ── -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-800">Pengelompokan Penduduk Berdasarkan Jenis Kelamin per RT/RW</h2>
          <p class="text-sm text-gray-500">Jumlah laki-laki dan perempuan untuk setiap RT dalam setiap RW.</p>

          <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>
          <div v-else-if="groupedGenderByRw.length === 0" class="mt-4 text-gray-500 italic">Belum ada data penduduk.</div>
          <div v-else class="mt-4 space-y-6">
            <div v-for="group in groupedGenderByRw" :key="group.rw" class="rounded-xl border border-purple-50 overflow-hidden">
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
                      v-for="(rt, idx) in group.rts" :key="rt.rt"
                      :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                      class="border-t border-gray-100 hover:bg-purple-50 transition-colors"
                    >
                      <td class="px-4 py-3 font-medium text-gray-700">RT {{ fmt(rt.rt) }} / RW {{ fmt(rt.rw) }}</td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">{{ rt.laki_laki }}</span>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-100 text-pink-600 font-semibold px-2 py-0.5">{{ rt.perempuan }}</span>
                      </td>
                      <td class="px-4 py-3 text-center font-bold text-gray-800">{{ rt.total }}</td>
                    </tr>
                    <tr class="border-t-2 border-purple-200 bg-purple-50 font-bold">
                      <td class="px-4 py-3 text-purple-700">Total RW {{ fmt(group.rw) }}</td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-200 text-blue-800 font-bold px-2 py-0.5">{{ group.subtotal.laki_laki }}</span>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block min-w-[2.5rem] rounded-full bg-pink-200 text-pink-700 font-bold px-2 py-0.5">{{ group.subtotal.perempuan }}</span>
                      </td>
                      <td class="px-4 py-3 text-center text-purple-800 font-bold text-base">{{ group.subtotal.total }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- Grand total jiwa -->
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

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- SECTION BARU: Kepala Keluarga per RT/RW berdasarkan Gender KK -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
          <div class="flex items-start gap-3">
            <!-- Ikon rumah sebagai penanda visual -->
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
                Data diambil dari anggota keluarga dengan hubungan <strong>"Kepala Keluarga"</strong>
                (atau urutan pertama dalam KK).
              </p>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="mt-4 text-gray-500">Memuat data...</div>

          <!-- Kosong -->
          <div v-else-if="groupedKkByRw.length === 0" class="mt-4 text-gray-500 italic">
            Belum ada data kepala keluarga yang terdaftar.
          </div>

          <!-- Tabel per RW -->
          <div v-else class="mt-4 space-y-6">
            <div
              v-for="group in groupedKkByRw"
              :key="group.rw"
              class="rounded-xl border border-emerald-100 overflow-hidden"
            >
              <!-- ── Header RW ── -->
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

              <!-- ── Tabel RT dalam RW ── -->
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="bg-emerald-50 text-gray-600">
                      <th class="px-4 py-2 text-left font-semibold w-1/3">Lokasi</th>
                      <th class="px-4 py-2 text-center font-semibold text-blue-600">
                        &#9794; KK Laki-laki
                      </th>
                      <th class="px-4 py-2 text-center font-semibold text-pink-500">
                        &#9792; KK Perempuan
                      </th>
                      <th class="px-4 py-2 text-center font-semibold text-gray-700">
                        Total KK
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Baris per RT -->
                    <tr
                      v-for="(rt, idx) in group.rts"
                      :key="rt.rt"
                      :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                      class="border-t border-gray-100 hover:bg-emerald-50 transition-colors"
                    >
                      <td class="px-4 py-3 font-medium text-gray-700">
                        RT {{ fmt(rt.rt) }} / RW {{ fmt(rt.rw) }}
                      </td>

                      <!-- KK Laki-laki -->
                      <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                          <span class="inline-block min-w-[2.5rem] rounded-full bg-blue-100 text-blue-700 font-semibold px-2 py-0.5">
                            {{ rt.kk_laki_laki }}
                          </span>
                          <!-- progress mini -->
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

                      <!-- KK Perempuan -->
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

                      <!-- Total KK -->
                      <td class="px-4 py-3 text-center font-bold text-gray-800">
                        {{ rt.total_kk }}
                      </td>
                    </tr>

                    <!-- ── Baris Sub-total RW ── -->
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

            <!-- ── Grand Total Kepala Keluarga Seluruh Kelurahan ── -->
            <div class="rounded-xl border-2 border-emerald-400 bg-emerald-600 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <div class="text-white font-bold text-base">Grand Total KK Seluruh Kelurahan Fatubesi</div>
                <div class="text-emerald-200 text-xs mt-0.5">Kepala Keluarga laki-laki + perempuan dari semua RT/RW</div>
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
        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- END SECTION KEPALA KELUARGA                                   -->
        <!-- ══════════════════════════════════════════════════════════════ -->

        <!-- ── Agama ── -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm mt-6">
          <h2 class="text-lg font-semibold text-gray-800">Pengelompokan Berdasarkan Agama</h2>
          <p class="text-sm text-gray-500">Data jumlah penduduk berdasarkan agama yang terdaftar di Kelurahan Fatubesi.</p>
          <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Kristen</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.kristen ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Islam</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.islam ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Katholik</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.katholik ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Hindu</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.hindu ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Buddha</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.buddha ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Konghucu</div>
              <div class="text-3xl font-bold text-gray-900">{{ loading ? '...' : metrics?.agama?.konghucu ?? 0 }}</div>
            </div>
          </div>
        </div>

        <div class="text-xs text-gray-400">
          Terakhir update: {{ metrics?.meta?.generated_at ?? '-' }} (time column: {{ metrics?.meta?.time_column ?? '-' }})
        </div>

      </div>
    </div>
  </AppLayout>
</template>