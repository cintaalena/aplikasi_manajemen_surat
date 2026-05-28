<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  q: String,
  letters: Object,
})

const query = ref(props.q ?? '')

const search = () => {
  router.get(route('arsip-surat.index'), { q: query.value }, { preserveState: true })
}
</script>

<template>
  <AppLayout>
    <div class="space-y-5">
      <div class="flex items-end justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-gray-900">Arsip Surat</h1>
          <p class="mt-1 text-sm text-gray-600">
            Cari arsip berdasarkan <b>Nomor Surat</b> atau <b>Judul Surat</b>.
          </p>
        </div>
      </div>

      <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">
        <div class="flex gap-2">
          <input
            v-model="query"
            class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
            placeholder="Contoh: 71/Kel.Ftbs.475/X/2025 atau 'Domisili'"
            @keyup.enter="search"
          />
          <button
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-fuchsia-500 hover:from-purple-700 hover:to-fuchsia-600"
            @click="search"
          >
            Cari
          </button>
        </div>
      </div>

      <div class="rounded-2xl border border-purple-100 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-purple-50 text-gray-700">
            <tr>
              <th class="p-3 text-left">Waktu Cetak</th>
              <th class="p-3 text-left">Nomor Surat</th>
              <th class="p-3 text-left">Judul</th>
              <th class="p-3 text-left">Template</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in letters.data" :key="row.id" class="border-t">
              <td class="p-3">{{ row.printed_at }}</td>
              <td class="p-3 font-semibold text-gray-900">{{ row.no_surat }}</td>
              <td class="p-3">{{ row.title }}</td>
              <td class="p-3">{{ row.template_slug }}</td>
            </tr>

            <tr v-if="!letters.data.length">
              <td class="p-3 text-gray-500" colspan="4">Belum ada arsip surat.</td>
            </tr>
          </tbody>
        </table>

        <div class="flex items-center justify-between p-3 border-t text-sm">
          <div class="text-gray-600">
            Total: {{ letters.total }}
          </div>

          <div class="flex gap-2">
            <Link
              v-if="letters.prev_page_url"
              :href="letters.prev_page_url"
              class="rounded-lg border px-3 py-1 hover:bg-gray-50"
            >
              Prev
            </Link>
            <Link
              v-if="letters.next_page_url"
              :href="letters.next_page_url"
              class="rounded-lg border px-3 py-1 hover:bg-gray-50"
            >
              Next
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
