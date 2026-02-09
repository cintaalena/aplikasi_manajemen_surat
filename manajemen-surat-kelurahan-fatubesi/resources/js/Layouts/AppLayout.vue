<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, watchEffect } from 'vue'

const page = usePage()

const isCurrent = (name) => {
  try { 
    const result = route().current(name)
    return result
  } catch (e) { 
    console.error('Route helper error:', e)
    return false 
  }
}

const logout = () => {
  router.post(route('logout'))
}

const goToPenduduk = () => {
  try {
    console.log('Navigating to penduduk...')
    router.visit('/penduduk')
  } catch (e) {
    console.error('Navigation error:', e)
  }
}

const templates = [
  { label: 'Surat Keterangan Domisili', slug: 'keterangan-domisili' },
  { label: 'Surat Keterangan Kelahiran', slug: 'keterangan-kelahiran' },
  { label: 'Surat Keterangan Kematian', slug: 'keterangan-kematian' },
  { label: 'Surat Keterangan Pindah', slug: 'keterangan-pindah' },
]

// submenu auto-open kalau user ada di halaman template
const onTemplatePage = computed(() => {
  try {
    return route().current('surat-templates.index') || route().current('surat-templates.show')
  } catch {
    return false
  }
})

const onArsipPage = computed(() => {
  try {
    return route().current('arsip-surat.index')
  } catch {
    return false
  }
})


const templatesOpen = ref(false)
watchEffect(() => {
  if (onTemplatePage.value) templatesOpen.value = true
})

const currentUrl = computed(() => page.url || '')

const isTemplateActive = (slug) => {
  return currentUrl.value === `/template-surat/${slug}`
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-white via-white to-purple-50">
    <div class="mx-auto max-w-7xl px-4 py-6">
      <div class="grid gap-6 lg:grid-cols-12">
        <!-- Sidebar -->
<aside class="lg:col-span-3">
  <div class="rounded-2xl border border-purple-100 bg-white/80 backdrop-blur shadow-sm overflow-hidden">
    <div class="p-5 border-b border-purple-100">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-600 to-fuchsia-500 shadow-md shadow-purple-200">
          <span class="text-white font-bold">KF</span>
        </div>
        <div>
          <div class="text-sm font-semibold text-gray-900">
            {{ page.props.auth.user.name }}
          </div>
          <div class="text-xs text-gray-600">
            {{ page.props.auth.user.jabatan }}
          </div>
        </div>
      </div>
    </div>

    <nav class="p-3 space-y-2">
      <!-- HOME -->
      <Link
        :href="route('dashboard')"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
        :class="isCurrent('dashboard')
          ? 'bg-purple-50 text-purple-800 border-purple-200'
          : 'bg-white text-gray-700 border-transparent hover:bg-purple-50/60 hover:text-purple-900'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>
          Home
        </span>
        <span v-if="isCurrent('dashboard')" class="text-xs text-purple-700">Aktif</span>
      </Link>

            <!-- TEMPLATE SURAT (EXPANDABLE) -->
      <div class="rounded-xl border border-transparent bg-white">
        <button
          type="button"
          class="w-full flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
          :class="onTemplatePage
            ? 'bg-indigo-50 text-indigo-900 border-indigo-200'
            : 'bg-white text-gray-700 border-transparent hover:bg-indigo-50/60 hover:text-indigo-900'"
          @click="templatesOpen = !templatesOpen"
        >
          <span class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
            Template Surat ({{ templates.length }})
          </span>
          <span class="text-xs">
            {{ templatesOpen ? '—' : '+' }}
          </span>
        </button>

        <div v-show="templatesOpen" class="mt-2 pl-2 pr-1 pb-2">
          <div class="max-h-72 overflow-auto pr-1">
            <Link
              v-for="t in templates"
              :key="t.slug"
              :href="route('surat-templates.show', { slug: t.slug })"
              class="block rounded-xl px-4 py-2 text-sm transition border"
              :class="isTemplateActive(t.slug)
                ? 'bg-fuchsia-50 text-fuchsia-900 border-fuchsia-200'
                : 'bg-white text-gray-700 border-transparent hover:bg-fuchsia-50/60 hover:text-fuchsia-900'"
            >
              {{ t.label }}
            </Link>
          </div>

          <Link
            :href="route('surat-templates.index')"
            class="mt-2 block rounded-xl px-4 py-2 text-xs font-semibold text-indigo-900 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 transition"
          >
            Lihat Ringkasan Template
          </Link>
        </div>
      </div>

      <!-- ARSIP SURAT -->
      <Link
        :href="route('arsip-surat.index')"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
        :class="onArsipPage
          ? 'bg-amber-50 text-amber-900 border-amber-200'
          : 'bg-white text-gray-700 border-transparent hover:bg-amber-50/60 hover:text-amber-900'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
          Arsip Surat
        </span>
        <span v-if="onArsipPage" class="text-xs text-amber-700">Aktif</span>
      </Link>


      <!-- PENDUDUK -->
      <a
        href="/penduduk"
        @click.prevent="goToPenduduk"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border cursor-pointer"
        :class="isCurrent('penduduk.index')
          ? 'bg-emerald-50 text-emerald-900 border-emerald-200'
          : 'bg-white text-gray-700 border-transparent hover:bg-emerald-50/60 hover:text-emerald-900'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
          Database Penduduk
        </span>
        <span v-if="isCurrent('penduduk.index')" class="text-xs text-emerald-700">Aktif</span>
      </a>

      <!-- LOG OUT -->
      <button
        @click="logout"
        class="w-full flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border
              bg-red-50 text-red-700 border-red-200
              hover:bg-red-100 hover:text-red-800"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
          Log Out
        </span>
      </button>
    </nav>
  </div>
</aside>


        <!-- Main -->
        <main class="lg:col-span-9">
          <div class="rounded-2xl border border-purple-100 bg-white/80 backdrop-blur shadow-sm">
            <div class="p-6">
              <slot>
              </slot>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
