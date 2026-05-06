<script setup>
/**
 * CameraCapture.vue
 * Langsung panggil getUserMedia saat mount → browser menampilkan popup izin native.
 * Foto diambil → emit File ke parent (tidak didownload).
 */
import { ref, computed, onUnmounted, onMounted } from 'vue'

defineProps({ label: { type: String, default: 'Dokumen' } })
const emit = defineEmits(['captured', 'close'])

// Deteksi apakah URL menggunakan 127.0.0.1 (kamera bisa gagal) vs localhost
const isOn127     = computed(() => location.hostname === '127.0.0.1')
const currentPort = computed(() => location.port || '80')
const goToLocalhost = () => {
  location.href = location.href.replace('127.0.0.1', 'localhost')
}

// Deteksi jenis browser untuk link pengaturan kamera
const isEdge   = computed(() => navigator.userAgent.includes('Edg/'))
const isChrome = computed(() => navigator.userAgent.includes('Chrome/') && !navigator.userAgent.includes('Edg/'))
const isFirefox = computed(() => navigator.userAgent.includes('Firefox/'))
const browserCameraSettingsUrl = computed(() => {
  if (isEdge.value)   return 'edge://settings/content/camera'
  if (isChrome.value) return 'chrome://settings/content/camera'
  return null
})
const openBrowserSettings = () => {
  if (browserCameraSettingsUrl.value) {
    window.open(browserCameraSettingsUrl.value)
  }
}

// 'opening' | 'live' | 'denied' | 'busy' | 'unavailable' | 'error'
const step        = ref('opening')
const errorMsg    = ref('')
const errorName   = ref('')
// 'browser' | 'system' | 'dismissed' — penyebab denied
const denialCause = ref('browser')
const videoRef    = ref(null)
const canvasRef   = ref(null)
const stream      = ref(null)
const cameraReady = ref(false)
const flash       = ref(false)
const capturing   = ref(false)

const stopCamera = () => {
  stream.value?.getTracks().forEach(t => t.stop())
  stream.value = null
  cameraReady.value = false
}

const openCamera = async () => {
  step.value = 'opening'
  errorMsg.value = ''
  if (!navigator.mediaDevices?.getUserMedia) {
    step.value = 'unavailable'
    return
  }
  try {
    stream.value = await navigator.mediaDevices.getUserMedia({ video: true })
    step.value = 'live'
    await new Promise(r => setTimeout(r, 50))
    if (videoRef.value) {
      videoRef.value.srcObject = stream.value
      videoRef.value.onloadedmetadata = () => {
        videoRef.value.play()
        cameraReady.value = true
      }
    }
  } catch (e) {
    stopCamera()
    errorName.value = e.name ?? 'UnknownError'
    if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
      denialCause.value = 'browser'
      errorMsg.value = e.message ?? ''
      try {
        const perm = await navigator.permissions.query({ name: 'camera' })
        errorMsg.value = `${e.message} [perm:${perm.state}]`
        if (perm.state === 'granted') {
          denialCause.value = 'system'
        } else if (perm.state === 'prompt') {
          denialCause.value = 'dismissed'
        } else {
          denialCause.value = 'browser'
        }
      } catch (_) { denialCause.value = 'browser' }
      step.value = 'denied'
    } else if (e.name === 'NotReadableError' || e.name === 'TrackStartError' || e.name === 'AbortError') {
      errorMsg.value = e.message ?? ''
      step.value = 'busy'
    } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError' || e.name === 'OverconstrainedError') {
      errorMsg.value = e.message ?? 'Kamera tidak ditemukan di perangkat ini.'
      step.value = 'unavailable'
    } else {
      errorMsg.value = e.message ?? 'Gagal membuka kamera.'
      step.value = 'error'
    }
  }
}

const capture = async () => {
  if (!videoRef.value || !canvasRef.value || !cameraReady.value || capturing.value) return
  capturing.value = true
  flash.value = true
  setTimeout(() => { flash.value = false }, 180)
  const canvas = canvasRef.value
  canvas.width  = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  canvas.getContext('2d').drawImage(videoRef.value, 0, 0)
  canvas.toBlob((blob) => {
    if (blob) {
      const ts   = new Date().toISOString().replace(/[T:.Z]/g, '-').slice(0, -1)
      const file = new File([blob], `foto-${ts}.jpg`, { type: 'image/jpeg' })
      stopCamera()
      emit('captured', file)
    }
    capturing.value = false
  }, 'image/jpeg', 0.92)
}

const fileInputRef = ref(null)

const openNativeCamera = () => {
  fileInputRef.value?.click()
}

const handleFileCapture = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  stopCamera()
  emit('captured', file)
}

const close = () => { stopCamera(); emit('close') }

onMounted(openCamera)
onUnmounted(stopCamera)
</script>

<template>
  <!-- Overlay solid penuh — bg-black tanpa opacity agar tidak transparan -->
  <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-gray-900 shadow-2xl overflow-hidden">

      <!-- Header selalu tampil -->
      <div class="flex items-center justify-between px-4 py-3 bg-gray-800">
        <div class="flex items-center gap-2 min-w-0">
          <!-- Indikator warna sesuai step -->
          <span class="h-2 w-2 rounded-full shrink-0 transition-colors"
            :class="{
              'bg-yellow-400 animate-pulse': step === 'checking' || step === 'opening',
              'bg-green-400  animate-pulse': step === 'live',
              'bg-red-400'                 : step === 'denied' || step === 'error',
              'bg-gray-400'               : step === 'prompt' || step === 'unavailable',
            }"
          ></span>
          <span class="text-xs font-semibold text-white truncate">Ambil Foto: {{ label }}</span>
        </div>
        <button type="button" @click="close"
          class="ml-2 shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-700 hover:text-white transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- ── Menunggu izin browser (popup native muncul di atas) ─────────── -->
      <div v-if="step === 'opening'" class="flex flex-col items-center gap-5 px-6 py-10 text-center bg-gray-900">
        <!-- Spinner -->
        <div class="relative flex h-16 w-16 items-center justify-center">
          <svg class="absolute h-16 w-16 animate-spin text-green-800" fill="none" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
          </svg>
          <svg class="absolute h-16 w-16 animate-spin text-green-400" fill="none" viewBox="0 0 24 24" style="animation-duration:.9s">
            <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-white">Meminta Izin Kamera</p>
          <p class="mt-1 text-sm text-gray-400">Izinkan akses kamera pada notifikasi di browser Anda</p>
        </div>
        <!-- Ilustrasi notifikasi browser Edge/Chrome -->
        <div class="w-full rounded-xl border border-yellow-500/50 bg-yellow-950 px-4 py-3 text-left">
          <p class="mb-2 text-xs font-bold text-yellow-400 uppercase tracking-wide">⬆ Notifikasi browser muncul di bagian atas</p>
          <div class="flex items-center gap-3 rounded-lg border border-gray-600 bg-gray-800 px-3 py-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1 text-sm text-gray-200">Izinkan <strong class="text-white">127.0.0.1</strong> menggunakan kamera?</span>
            <div class="flex gap-2 flex-shrink-0">
              <span class="rounded bg-blue-600 px-3 py-1 text-xs font-bold text-white">Izinkan</span>
              <span class="rounded bg-gray-600 px-3 py-1 text-xs text-gray-300">Blokir</span>
            </div>
          </div>
          <p class="mt-1.5 text-xs text-yellow-300 font-medium">Klik <strong>"Izinkan"</strong> untuk membuka kamera</p>
        </div>
      </div>

      <!-- ── Akses ditolak ─────────────────────────────────────────────────── -->
      <div v-else-if="step === 'denied'" class="flex flex-col gap-3 px-5 py-5 bg-gray-900">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-950 border border-red-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-bold text-white">Kamera Tidak Dapat Dibuka</p>
            <p class="text-xs text-gray-400">
              <span v-if="denialCause === 'system'">Izin browser ✓ — periksa pengaturan Windows</span>
              <span v-else-if="denialCause === 'dismissed'">Popup izin ditutup, coba lagi</span>
              <span v-else>Browser memblokir kamera — ikuti langkah di bawah</span>
            </p>
          </div>
        </div>

        <!-- ★ UTAMA: Reset izin kamera di browser (penyebab paling umum) -->
        <div v-if="denialCause === 'browser'" class="rounded-xl border-2 border-green-500/60 bg-green-950/60 p-3 space-y-2">
          <p class="text-xs font-bold text-green-300 flex items-center gap-1.5">
            <span class="text-base">🔑</span> Cara Reset Izin Kamera di Browser
          </p>
          <!-- Langkah address bar -->
          <div class="space-y-1.5">
            <div class="flex items-start gap-2">
              <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-green-700 text-[10px] font-bold text-white">1</span>
              <p class="text-xs text-gray-200">Klik ikon <strong class="text-white">🔒 (gembok/info)</strong> di <strong class="text-white">address bar</strong> browser, tepat sebelum URL</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-green-700 text-[10px] font-bold text-white">2</span>
              <p class="text-xs text-gray-200">Cari <strong class="text-white">Camera</strong> / <strong class="text-white">Kamera</strong> → ubah dari <span class="text-red-400 font-bold">Block</span> ke <span class="text-green-400 font-bold">Allow</span></p>
            </div>
            <div class="flex items-start gap-2">
              <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-green-700 text-[10px] font-bold text-white">3</span>
              <p class="text-xs text-gray-200">Klik <strong class="text-white">"Reload"</strong> / <strong class="text-white">"Muat Ulang"</strong> yang muncul, lalu coba kamera lagi</p>
            </div>
          </div>
          <!-- Ilustrasi visual -->
          <div class="mt-1 rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-xs text-gray-300">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-gray-400 font-mono text-[10px]">🔒 https://localhost/...</span>
            </div>
            <div class="flex items-center justify-between rounded bg-gray-700 px-2 py-1">
              <span>📷 Camera</span>
              <span class="text-green-400 font-bold">Allow ✓</span>
            </div>
          </div>
          <!-- Tombol buka pengaturan browser (Edge/Chrome) -->
          <button v-if="browserCameraSettingsUrl" type="button" @click="openBrowserSettings"
            class="w-full rounded-lg bg-green-700 py-2 text-xs font-bold text-white hover:bg-green-600 transition">
            Buka Pengaturan Kamera Browser →
          </button>
        </div>

        <!-- Penyebab: akses via 127.0.0.1 bukan localhost -->
        <div v-if="isOn127" class="rounded-xl border border-blue-600/50 bg-blue-950/60 p-3 space-y-2">
          <p class="text-xs font-bold text-blue-300 flex items-center gap-1.5">
            <span class="text-base">🔗</span> Penyebab utama: URL menggunakan <code class="bg-gray-800 px-1 rounded">127.0.0.1</code>
          </p>
          <p class="text-xs text-gray-300">Browser Edge memblokir kamera di <strong class="text-white">127.0.0.1</strong>. Gunakan <strong class="text-white">localhost</strong> sebagai gantinya.</p>
          <button type="button" @click="goToLocalhost"
            class="w-full rounded-lg bg-blue-600 py-2 text-xs font-bold text-white hover:bg-blue-700 transition">
            Buka di localhost:{{ currentPort }} →
          </button>
        </div>

        <!-- Windows Privacy Settings (hanya tampil jika penyebab = system) -->
        <div v-if="denialCause === 'system'" class="rounded-xl border border-orange-700/40 bg-orange-950/50 p-3 space-y-2">
          <p class="text-xs font-bold text-orange-300 flex items-center gap-1.5">
            <span class="text-base">🪟</span> Periksa Windows Privacy Settings
          </p>
          <p class="text-xs text-gray-400"><kbd class="rounded bg-gray-700 px-1 text-white font-bold">Win+I</kbd> → <strong class="text-white">Privacy &amp; security</strong> → <strong class="text-white">Camera</strong> → pastikan semua toggle <span class="text-green-400 font-bold">ON</span></p>
        </div>

        <!-- Kode error teknis -->
        <div class="rounded-lg border border-gray-700 bg-gray-950 px-3 py-2 font-mono text-[10px] text-gray-500">
          Error: <span class="text-yellow-400">{{ errorName }}</span><span v-if="errorMsg"> — {{ errorMsg }}</span>
        </div>

        <!-- ★ FALLBACK: Gunakan input file dengan capture — bypass semua permission -->
        <div class="rounded-xl border-2 border-green-500/60 bg-green-950/50 p-3 space-y-2">
          <p class="text-xs font-bold text-green-300">📷 Alternatif: Ambil Foto Langsung</p>
          <p class="text-xs text-gray-400">Klik tombol di bawah untuk membuka kamera atau pilih foto dari galeri tanpa membutuhkan izin browser.</p>
          <button type="button" @click="openNativeCamera"
            class="w-full rounded-lg bg-green-700 py-2.5 text-sm font-bold text-white hover:bg-green-800 transition">
            📸 Buka Kamera / Pilih Foto
          </button>
          <input ref="fileInputRef" type="file" accept="image/*" capture="environment" class="hidden" @change="handleFileCapture" />
        </div>

        <div class="flex w-full gap-3">
          <button type="button" @click="openCamera"
            class="flex-1 rounded-xl border border-gray-600 py-2.5 text-sm font-semibold text-gray-300 hover:border-gray-400 hover:text-white transition">
            Coba Lagi
          </button>
          <button type="button" @click="() => location.reload()"
            class="flex-1 rounded-xl bg-green-700 py-2.5 text-sm font-semibold text-white hover:bg-green-800 transition">
            Muat Ulang
          </button>
        </div>
      </div>

      <!-- ── Kamera dipakai app lain ────────────────────────────────────────── -->
      <div v-else-if="step === 'busy'" class="flex flex-col items-center gap-4 px-6 py-8 text-center bg-gray-900">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-yellow-950 border-2 border-yellow-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-white">Kamera Sedang Digunakan</p>
          <p class="mt-1.5 text-sm text-gray-400">Kamera sedang dipakai aplikasi lain (Zoom, Teams, dll). Tutup aplikasi tersebut lalu coba lagi.</p>
          <p v-if="errorName" class="mt-1 font-mono text-[10px] text-gray-600">{{ errorName }}<span v-if="errorMsg"> — {{ errorMsg }}</span></p>
        </div>
        <button type="button" @click="openCamera"
          class="w-full rounded-xl bg-green-700 py-2.5 text-sm font-semibold text-white hover:bg-green-800 transition">
          Coba Lagi
        </button>
      </div>

      <!-- ── Kamera tidak ditemukan ────────────────────────────────────────── -->
      <div v-else-if="step === 'unavailable'" class="flex flex-col items-center gap-5 px-6 py-10 text-center bg-gray-900">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-800 border-2 border-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-white">Kamera Tidak Tersedia</p>
          <p class="mt-1.5 text-sm text-gray-400">{{ errorMsg || 'Perangkat ini tidak memiliki kamera.' }}</p>
          <p v-if="errorName" class="mt-1 font-mono text-[10px] text-gray-600">{{ errorName }}</p>
        </div>
        <button type="button" @click="close"
          class="w-full rounded-xl bg-gray-700 py-2.5 text-sm font-semibold text-gray-200 hover:bg-gray-600 transition">
          Tutup
        </button>
      </div>

      <!-- ── Error umum ────────────────────────────────────────────────────── -->
      <div v-else-if="step === 'error'" class="flex flex-col items-center gap-5 px-6 py-10 text-center bg-gray-900">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-950 border-2 border-orange-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-white">Gagal Membuka Kamera</p>
          <p class="mt-1.5 text-sm text-gray-400">{{ errorMsg }}</p>
          <p v-if="errorName" class="mt-1 font-mono text-[10px] text-gray-600">{{ errorName }}</p>
        </div>
        <button type="button" @click="openCamera"
          class="w-full rounded-xl bg-green-700 py-2.5 text-sm font-semibold text-white hover:bg-green-800 transition">
          Coba Lagi
        </button>
      </div>

      <!-- ── Live — kamera aktif ───────────────────────────────────────────── -->
      <template v-else-if="step === 'live'">
        <div class="relative bg-black">
          <div v-if="flash" class="pointer-events-none absolute inset-0 z-10 bg-white/80"></div>
          <video
            ref="videoRef"
            class="w-full"
            autoplay playsinline muted
            style="max-height: 55vh; object-fit: cover;"
          ></video>
          <div class="pointer-events-none absolute inset-3 rounded-xl border-2 border-dashed border-white/30 flex items-center justify-center">
            <span class="rounded-full bg-black/50 px-3 py-1 text-xs text-white/80">Arahkan ke dokumen</span>
          </div>
        </div>

        <canvas ref="canvasRef" class="hidden"></canvas>

        <div class="flex items-center justify-center bg-gray-800 px-6 py-5">
          <button
            type="button"
            :disabled="!cameraReady || capturing"
            @click="capture"
            class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-xl transition hover:scale-105 active:scale-95 disabled:opacity-40"
            title="Ambil foto"
          >
            <span class="h-11 w-11 rounded-full border-[3px] border-gray-400"></span>
          </button>
        </div>

        <p class="bg-gray-900 pb-3 pt-1 text-center text-xs text-gray-500">
          Foto langsung masuk sebagai lampiran dokumen persyaratan
        </p>
      </template>

    </div>
  </div>
</template>
