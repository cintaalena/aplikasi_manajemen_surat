<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import KematianTemplate from '@/Components/Surat/KematianTemplate.vue'
import PindahTemplate from '@/Components/Surat/PindahTemplate.vue'
import { computed } from 'vue'

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

const formatDate = (raw) => {
  if (!raw) return '-'
  const d = new Date(raw)
  if (isNaN(d)) return raw
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
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
    </div>
  </AppLayout>
</template>
