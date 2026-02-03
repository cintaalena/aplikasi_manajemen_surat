<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  credential: String,
  email: String,
})

const copied = ref(false)

const copyCredential = async () => {
  if (!props.credential) return
  try {
    await navigator.clipboard.writeText(props.credential)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    alert('Gagal menyalin credential. Silakan salin manual.')
  }
}
</script>


<template>
  <GuestLayout>
    <Head title="Registrasi Berhasil" />

    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-6 py-4">
      <h2 class="text-lg font-semibold text-emerald-900">
        Registrasi Berhasil
      </h2>

      <p class="mt-2 text-sm text-emerald-800">
        Simpan credential berikut. Credential ini <b>hanya ditampilkan sekali</b>.
      </p>
    </div>

    <div v-if="props.credential" class="mt-4 rounded-xl border border-gray-200 bg-white px-6 py-4">
  <div class="text-sm text-gray-600">Credential Anda</div>

  <div class="mt-2 flex items-center justify-between gap-4">
    <div class="font-mono text-xl text-gray-900 tracking-wider">
      {{ props.credential }}
    </div>

    <button
      @click="copyCredential"
      class="rounded-lg border px-4 py-2 text-sm font-semibold transition
             bg-indigo-50 text-indigo-700 border-indigo-200"
    >
      {{ copied ? 'Tersalin ✓' : 'Copy Credential' }}
    </button>
  </div>

  <div class="mt-2 text-xs text-gray-500">
    Simpan credential ini. Credential hanya ditampilkan sekali.
  </div>
</div>

<div class="mt-6 text-sm text-gray-600">
  Email terdaftar: <span class="font-medium">{{ props.email }}</span>
</div>

    

    <div class="mt-8">
      <Link
        :href="route('login')"
        class="inline-flex items-center rounded-lg bg-purple-600 px-6 py-2 text-white font-semibold hover:bg-purple-700 transition"
      >
        Kembali ke Halaman Login
      </Link>
    </div>
  </GuestLayout>
</template>
