<script setup>
import { Link } from '@inertiajs/vue3';
import { useAsset } from '@/composables/useAsset'
const { asset } = useAsset()
</script>

<template>
  <div class="min-h-screen bg-white relative overflow-hidden">
    <div class="absolute inset-0">
      <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-amber-200/60 blur-3xl opacity-70 animate-pulse"></div>
      <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-stone-200/60 blur-3xl opacity-60 animate-pulse"></div>
      <div class="absolute bottom-[-6rem] left-1/3 h-96 w-96 rounded-full bg-amber-100/80 blur-3xl opacity-60 animate-pulse"></div>
      <div class="absolute inset-0 bg-gradient-to-br from-white via-amber-50 to-stone-100"></div>
    </div>

    <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
      <div class="w-full max-w-md">
        <div class="mb-6 text-center">
          <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-100 border border-stone-300 overflow-hidden shadow-lg shadow-stone-200">
            <img :src="asset('images/logo_kalpataru.jpg')" alt="KF" class="h-11 w-11 object-contain" />
          </div>
          <h1 class="text-2xl font-semibold text-gray-900">Sistem Persuratan Kelurahan</h1>
          <p class="mt-1 text-sm text-gray-600">Masuk untuk mengelola surat dengan aman</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white/80 backdrop-blur shadow-xl shadow-stone-200/60">
          <div class="p-6">
            <slot />
          </div>

          <div class="flex items-center justify-between rounded-b-2xl border-t border-stone-100 bg-white/70 px-6 py-4 text-xs text-gray-600">
            <span>© {{ new Date().getFullYear() }} Kelurahan Fatubesi</span>
            <Link href="/" class="text-stone-700 hover:text-stone-900 font-medium">
              Beranda
            </Link>
          </div>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
          Kelurahan Fatubesi • Sistem Persuratan Digital
        </p>
      </div>
    </div>
  </div>
</template>
