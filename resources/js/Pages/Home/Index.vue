<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'

const btnPurple =
  "rounded-xl px-4 py-2 text-sm font-semibold text-white " +
  "bg-gradient-to-r from-purple-600 to-fuchsia-500 " +
  "hover:from-purple-700 hover:to-fuchsia-600"

const loading = ref(true)
const error = ref('')
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
  // polling "real-time"
  timer = setInterval(fetchMetrics, 15000) // 15 detik
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <Head title="Home" />

  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-800">Home (Monitoring)</h1>
          <button :class="btnPurple" type="button" @click="fetchMetrics">
            Refresh
          </button>
        </div>

        <div v-if="error" class="p-3 rounded-xl border border-red-200 bg-red-50 text-red-700">
          {{ error }}
        </div>

        <!-- Cards -->
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

        <!-- Top surat 30 hari -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">Surat paling sering dibuat (30 hari terakhir)</h2>
              <p class="text-sm text-gray-500">Top 10 berdasarkan template</p>
            </div>
          </div>

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

        <!-- Penduduk -->
        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-800">Penduduk Kelurahan Fatubesi</h2>
          <p class="text-sm text-gray-500">
            Update otomatis mengikuti perubahan data (polling 15 detik).
          </p>

          <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Total KK</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.population?.total_kk ?? 0 }}
              </div>
            </div>

            <div class="rounded-xl border border-gray-100 p-4">
              <div class="text-sm text-gray-500">Estimasi total penduduk</div>
              <div class="text-3xl font-bold text-gray-900">
                {{ loading ? '...' : metrics?.population?.total_penduduk ?? 0 }}
              </div>
              <div class="text-xs text-gray-500 mt-1">
                (sementara dihitung dari total KK × 4; nanti kita ganti ke data individu)
              </div>
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
