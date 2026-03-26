<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const templates = [
  { label: 'Surat Keterangan Domisili', slug: 'keterangan-domisili' },
  { label: 'Surat Keterangan Kelahiran', slug: 'keterangan-kelahiran' },
  { label: 'Surat Keterangan Kematian', slug: 'keterangan-kematian' },
  { label: 'Surat Keterangan Pindah', slug: 'keterangan-pindah' },
]
</script>

<template>
  <AppLayout>
    <h1 class="text-xl font-bold text-gray-900">Template Surat</h1>
    <p class="mt-2 text-sm text-gray-600">Pilih template surat yang ingin Anda buat.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Link
        v-for="item in templates"
        :key="item.slug"
        :href="route('surat-templates.show', item.slug)"
        class="block rounded-xl border border-purple-100 bg-white p-5 shadow-sm transition hover:border-purple-300 hover:shadow-md"
      >
        <div class="text-base font-semibold text-gray-900">{{ item.label }}</div>
        <div class="mt-1 text-sm text-gray-500">Klik untuk membuat surat</div>
      </Link>
    </div>
  </AppLayout>
</template>
