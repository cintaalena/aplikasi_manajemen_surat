<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
})
</script>

<template>
  <Head title="Kelurahan Fatubesi" />

  <div class="min-h-screen relative overflow-hidden bg-gradient-to-br from-purple-600 via-violet-600 to-fuchsia-600">
    <!-- soft glow -->
    <div class="absolute inset-0">
      <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-white/15 blur-3xl"></div>
      <div class="absolute bottom-[-6rem] right-[-6rem] h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-5xl">
        <div class="grid overflow-hidden rounded-3xl bg-white/10 backdrop-blur shadow-2xl shadow-black/20 lg:grid-cols-2">
          <!-- LEFT: Illustration panel -->
          <div class="relative p-8 sm:p-10 text-white">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15">
                <span class="font-bold">KF</span>
              </div>
              <div>
                <div class="text-sm font-semibold">Kelurahan Fatubesi</div>
                <div class="text-xs text-white/80">Sistem Manajemen Persuratan</div>
              </div>
            </div>

            <div class="mt-10">
              <!-- Inline illustration (SVG) -->
              <div class="mx-auto w-full max-w-md">
                <svg viewBox="0 0 640 360" class="w-full drop-shadow-xl">
                  <defs>
                    <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0" stop-color="white" stop-opacity="0.22" />
                      <stop offset="1" stop-color="white" stop-opacity="0.06" />
                    </linearGradient>
                    <radialGradient id="moon" cx="65%" cy="30%" r="50%">
                      <stop offset="0" stop-color="#fff59d" stop-opacity="1" />
                      <stop offset="1" stop-color="#fff59d" stop-opacity="0" />
                    </radialGradient>
                  </defs>

                  <!-- sky card -->
                  <rect x="30" y="30" width="580" height="260" rx="28" fill="url(#g1)" stroke="white" stroke-opacity="0.15"/>
                  <!-- glow -->
                  <circle cx="420" cy="110" r="85" fill="url(#moon)"/>
                  <!-- moon -->
                  <circle cx="430" cy="115" r="26" fill="#FFE082"/>
                  <!-- stars -->
                  <circle cx="120" cy="90" r="3" fill="white" opacity="0.8"/>
                  <circle cx="160" cy="70" r="2" fill="white" opacity="0.7"/>
                  <circle cx="520" cy="80" r="2" fill="white" opacity="0.7"/>
                  <circle cx="500" cy="140" r="2" fill="white" opacity="0.6"/>
                  <circle cx="250" cy="120" r="2" fill="white" opacity="0.7"/>
                  <!-- hills -->
                  <path d="M70 240 C160 170, 260 170, 340 240 C410 300, 520 300, 590 240 L590 290 L70 290 Z"
                        fill="white" opacity="0.16"/>
                  <path d="M70 250 C150 210, 230 205, 300 250 C360 290, 470 290, 590 250 L590 290 L70 290 Z"
                        fill="white" opacity="0.10"/>
                  <!-- small clouds -->
                  <g fill="white" opacity="0.16">
                    <circle cx="150" cy="155" r="16"/><circle cx="170" cy="155" r="12"/><circle cx="135" cy="160" r="10"/>
                    <circle cx="480" cy="165" r="14"/><circle cx="500" cy="165" r="10"/><circle cx="465" cy="170" r="9"/>
                  </g>
                </svg>
              </div>

              <p class="mt-6 text-sm text-white/85 leading-relaxed">
                Aplikasi internal untuk pencatatan surat masuk/keluar dan arsip yang lebih tertib, cepat, dan aman.
              </p>


            </div>
          </div>

          <!-- RIGHT: Simple welcome + login button -->
          <div class="flex flex-col items-center justify-center bg-white p-8 sm:p-10 text-center">
            <div class="text-sm text-gray-500">Hello!</div>
            <div class="text-2xl font-bold text-gray-900">Selamat Datang</div>

            <div class="mt-8 w-full max-w-xs">
              <Link
                v-if="canLogin"
                :href="route('login')"
                class="inline-flex w-full items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold text-white
                       bg-gradient-to-r from-purple-600 to-fuchsia-500
                       shadow-md shadow-purple-200
                       hover:from-purple-700 hover:to-fuchsia-600
                       focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2
                       active:scale-[0.99] transition"
              >
                Masuk
              </Link>

              <div class="mt-4 text-xs text-gray-500">
                © {{ new Date().getFullYear() }} Kelurahan Fatubesi
              </div>
            </div>
          </div>
        </div>

        <!-- small footer -->
        <div class="mt-6 text-center text-xs text-white/80">
          Sistem Manajemen Persuratan Kelurahan Fatubesi
        </div>
      </div>
    </div>
  </div>
</template>
