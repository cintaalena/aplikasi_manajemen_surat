<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { useAsset } from '@/composables/useAsset'

defineProps({
  canLogin: Boolean,
})

const { asset } = useAsset()
</script>

<template>
  <Head title="Kelurahan Fatubesi" />

  <div class="min-h-screen bg-white flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-5xl">
      <div class="grid overflow-hidden rounded-3xl shadow-lg border border-gray-100 lg:grid-cols-2">

        <!-- LEFT: Logo panel -->
        <div class="flex flex-col items-center justify-center p-10 bg-white border-b border-gray-100 lg:border-b-0 lg:border-r">
          <div class="flex items-center gap-3 self-start mb-10">
            <img :src="asset('images/logo_kalpataru.jpg')" alt="Kalpataru"
              class="h-9 w-9 object-contain mix-blend-multiply" />
            <div>
              <div class="text-sm font-semibold text-gray-900">Kelurahan Fatubesi</div>
              <div class="text-xs text-gray-500">Sistem Manajemen Persuratan</div>
            </div>
          </div>

          <img
            :src="asset('images/logo_kalpataru.jpg')"
            alt="Logo Kelurahan Fatubesi"
            class="w-52 h-52 sm:w-60 sm:h-60 object-contain mix-blend-multiply"
          />

          <p class="mt-8 text-sm text-gray-500 leading-relaxed text-center max-w-xs">
            Aplikasi internal untuk pencatatan surat masuk/keluar dan arsip yang lebih tertib, cepat, dan aman.
          </p>
        </div>

        <!-- RIGHT: Welcome + login -->
        <div class="flex flex-col items-center justify-center bg-white p-10 text-center">
          <img :src="asset('images/logo_kalpataru.jpg')" alt="KF"
            class="h-14 w-14 object-contain mix-blend-multiply mb-4" />
          <div class="text-sm text-gray-400">Hello!</div>
          <div class="text-2xl font-bold text-gray-900 mt-1">Selamat Datang</div>
          <p class="mt-2 text-xs text-gray-500 max-w-xs leading-relaxed">
            Kelurahan Fatubesi — Sistem Manajemen Persuratan berbasis digital.
          </p>

          <div class="mt-8 w-full max-w-xs">
            <Link
              v-if="canLogin"
              :href="route('login')"
              class="block w-full rounded-xl px-5 py-3 text-sm font-semibold text-white bg-green-700
                     hover:bg-green-800 active:scale-[0.99] transition text-center"
            >
              Masuk
            </Link>

            <div class="mt-4 text-xs text-gray-400">
              © {{ new Date().getFullYear() }} Kelurahan Fatubesi
            </div>
          </div>
        </div>

      </div>

      <div class="mt-6 text-center text-xs text-gray-400">
        Sistem Manajemen Persuratan Kelurahan Fatubesi
      </div>
    </div>
  </div>
</template>
