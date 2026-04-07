<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen bg-white relative overflow-hidden">
    <!-- background gradient + blobs -->
    <div class="absolute inset-0">
      <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-purple-200 blur-3xl opacity-70 animate-pulse"></div>
      <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-fuchsia-200 blur-3xl opacity-60 animate-pulse"></div>
      <div class="absolute bottom-[-6rem] left-1/3 h-96 w-96 rounded-full bg-indigo-200 blur-3xl opacity-60 animate-pulse"></div>
      <div class="absolute inset-0 bg-gradient-to-br from-white via-white to-purple-50"></div>
    </div>

    <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
      <div class="w-full max-w-md">
        <!-- Header / Brand -->
        <div class="mb-6 text-center">
          <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-600 to-fuchsia-500 shadow-lg shadow-purple-200">
            <span class="text-white text-xl font-bold">KF</span>
          </div>
          <h1 class="text-2xl font-semibold text-gray-900">Sistem Persuratan Kelurahan</h1>
          <p class="mt-1 text-sm text-gray-600">Masuk untuk mengelola surat dengan aman</p>
        </div>

        <!-- Card -->
        <div class="rounded-2xl border border-purple-100 bg-white/80 backdrop-blur shadow-xl shadow-purple-100">
          <div class="p-6">
            <slot />
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between rounded-b-2xl border-t border-purple-100 bg-white/70 px-6 py-4 text-xs text-gray-600">
            <span>© {{ new Date().getFullYear() }} Kelurahan Fatubesi</span>
            <Link href="/" class="text-purple-700 hover:text-purple-900 font-medium">
              Beranda
            </Link>
          </div>
        </div>

        <!-- Tiny hint -->
        <p class="mt-6 text-center text-xs text-gray-500">
          Nuansa ungu-putih • UI dinamis • Inertia + Vue
        </p>
      </div>
    </div>
  </div>
</template>
