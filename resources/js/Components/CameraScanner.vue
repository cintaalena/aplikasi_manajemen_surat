<script setup>
import { ref, onUnmounted } from 'vue'

const emit = defineEmits(['close'])

const videoRef      = ref(null)
const canvasRef     = ref(null)
const stream        = ref(null)
const capturedCount = ref(0)
const error         = ref('')
const cameraReady   = ref(false)
const flash         = ref(false)

const startCamera = async () => {
  error.value = ''
  try {
    stream.value = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: 'environment',
        width:  { ideal: 1920 },
        height: { ideal: 1080 },
      },
      audio: false,
    })
    if (videoRef.value) {
      videoRef.value.srcObject = stream.value
      videoRef.value.onloadedmetadata = () => {
        videoRef.value.play()
        cameraReady.value = true
      }
    }
  } catch (e) {
    if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
      error.value = 'Izin kamera ditolak. Klik ikon kunci di address bar browser dan izinkan akses kamera, lalu muat ulang halaman.'
    } else if (e.name === 'NotFoundError') {
      error.value = 'Kamera tidak ditemukan di perangkat ini.'
    } else {
      error.value = 'Tidak dapat mengakses kamera: ' + e.message
    }
  }
}

const capture = () => {
  if (!videoRef.value || !canvasRef.value || !cameraReady.value) return

  const video  = videoRef.value
  const canvas = canvasRef.value
  canvas.width  = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0)

  capturedCount.value++
  const ts       = new Date().toISOString().replace(/[T:.Z]/g, '-').replace(/-$/,'')
  const filename = `scan-surat-${ts}-${capturedCount.value}.jpg`

  canvas.toBlob((blob) => {
    if (!blob) return
    const url = URL.createObjectURL(blob)
    const a   = document.createElement('a')
    a.href     = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }, 'image/jpeg', 0.92)

  flash.value = true
  setTimeout(() => { flash.value = false }, 200)
}

const close = () => {
  if (stream.value) {
    stream.value.getTracks().forEach(t => t.stop())
    stream.value = null
  }
  cameraReady.value = false
  emit('close')
}

onUnmounted(() => {
  if (stream.value) {
    stream.value.getTracks().forEach(t => t.stop())
  }
})

startCamera()
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
    <div class="relative w-full max-w-2xl rounded-2xl bg-gray-900 shadow-2xl overflow-hidden">

      <div class="flex items-center justify-between px-4 py-3 bg-gray-800">
        <div class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full" :class="cameraReady ? 'bg-green-400 animate-pulse' : 'bg-red-400'"></span>
          <span class="text-sm font-semibold text-white">Scan Dokumen Surat</span>
        </div>
        <div class="flex items-center gap-3">
          <span v-if="capturedCount > 0" class="rounded-full bg-green-700 px-3 py-0.5 text-xs font-bold text-white">
            {{ capturedCount }} foto tersimpan
          </span>
          <button type="button" @click="close" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-700 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="error" class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-sm text-red-300">{{ error }}</p>
        <button type="button" @click="startCamera" class="mt-4 rounded-xl bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 transition">
          Coba Lagi
        </button>
      </div>

      <div v-else class="relative bg-black">
        <div
          v-if="flash"
          class="pointer-events-none absolute inset-0 z-10 bg-white transition-opacity"
          style="opacity:0.7"
        ></div>

        <video
          ref="videoRef"
          class="w-full"
          autoplay
          playsinline
          muted
          style="max-height: 60vh; object-fit: cover;"
        ></video>

        <div class="pointer-events-none absolute inset-4 rounded-xl border-2 border-dashed border-white/40 flex items-center justify-center">
          <span class="rounded-full bg-black/40 px-3 py-1 text-xs text-white/70">Tempatkan dokumen dalam bingkai</span>
        </div>
      </div>

      <canvas ref="canvasRef" class="hidden"></canvas>

      <div v-if="!error" class="flex items-center justify-center gap-6 bg-gray-800 px-6 py-5">
        <button
          type="button"
          :disabled="!cameraReady"
          @click="capture"
          class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg transition hover:scale-105 active:scale-95 disabled:opacity-40"
          title="Ambil foto (disimpan otomatis ke komputer)"
        >
          <span class="h-12 w-12 rounded-full border-4 border-gray-300 bg-white"></span>
        </button>
      </div>

      <div class="bg-gray-900 px-4 py-2 text-center text-xs text-gray-500">
        Foto tersimpan otomatis ke folder Unduhan komputer Anda — gunakan saat upload surat masuk
      </div>
    </div>
  </div>
</template>
