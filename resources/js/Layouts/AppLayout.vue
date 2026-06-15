<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, watchEffect, onMounted, onUnmounted } from 'vue'
import { useAsset } from '@/composables/useAsset'

const page = usePage()
const { asset } = useAsset()

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

const userRole = computed(() => page.props.auth?.user?.role ?? 'staff')

const isTemplateActive = (slug) => {
  return currentUrl.value === `/template-surat/${slug}`
}

let audioCtx = null

const initAudioContext = () => {
  if (audioCtx) {
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {})
    return
  }
  try {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)()
  } catch (e) {}
}

const playNotificationSound = () => {
  try {
    if (!audioCtx) initAudioContext()
    if (!audioCtx) return
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {})
    const now = audioCtx.currentTime
    const osc1 = audioCtx.createOscillator()
    const gain1 = audioCtx.createGain()
    osc1.type = 'sine'
    osc1.frequency.value = 880
    gain1.gain.setValueAtTime(0.3, now)
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.45)
    osc1.connect(gain1)
    gain1.connect(audioCtx.destination)
    osc1.start(now)
    osc1.stop(now + 0.45)
    const osc2 = audioCtx.createOscillator()
    const gain2 = audioCtx.createGain()
    osc2.type = 'sine'
    osc2.frequency.value = 1318
    gain2.gain.setValueAtTime(0, now + 0.15)
    gain2.gain.setValueAtTime(0.22, now + 0.16)
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65)
    osc2.connect(gain2)
    gain2.connect(audioCtx.destination)
    osc2.start(now + 0.15)
    osc2.stop(now + 0.65)
  } catch (e) {}
}

const notifOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const bellRef = ref(null)
const panelStyle = ref({})

const updatePanelPos = () => {
  if (!bellRef.value) return
  const rect = bellRef.value.getBoundingClientRect()
  panelStyle.value = {
    position: 'fixed',
    top: (rect.bottom + 8) + 'px',
    left: Math.max(8, rect.right - 384) + 'px',
    width: '384px',
    zIndex: 9999,
  }
}

let pollInterval = null
let initialized  = false
const seenIds    = new Set()

const fetchNotifications = async () => {
  if (userRole.value !== 'lurah' && userRole.value !== 'staff') return
  try {
    const res = await fetch(route('notifications.index'), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    if (!res.ok) return
    const data = await res.json()

    if (data.has_sound_ping) {
      playNotificationSound()
    } else if (initialized) {
      const newNotifs = data.notifications.filter(n => !seenIds.has(n.id))
      if (newNotifs.length > 0) {
        playNotificationSound()
        const newUnread = newNotifs.filter(n => !n.is_read).length
        if (newUnread > 0) unreadCount.value += newUnread
        notifications.value = [...newNotifs, ...notifications.value].slice(0, 20)
      }
    } else {
      notifications.value = data.notifications
      unreadCount.value   = data.unread_count
    }

    data.notifications.forEach(n => seenIds.add(n.id))
    initialized = true
  } catch (e) {}
}

const toggleNotif = () => {
  notifOpen.value = !notifOpen.value
  if (notifOpen.value) {
    updatePanelPos()
    if (unreadCount.value > 0) {
      markAllRead()
    }
  }
}

const markAllRead = async () => {
  notifications.value = notifications.value.map(n => ({ ...n, is_read: true }))
  unreadCount.value   = 0
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  try {
    await fetch(route('notifications.mark-all-read'), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
  } catch (e) {}
}

const markRead = async (notif) => {
  if (notif.is_read) return
  notif.is_read     = true
  unreadCount.value = Math.max(0, unreadCount.value - 1)
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  fetch(route('notifications.mark-read', { notification: notif.id }), {
    method: 'PATCH',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  }).catch(() => {})
}

const openNotif = (notif) => {
  notifOpen.value = false
  markAllRead()
  const url = userRole.value === 'staff'
    ? '/disposisi-tugas'
    : '/arsip-surat'
  window.location.href = url
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  if (userRole.value === 'lurah' || userRole.value === 'staff') {
    document.addEventListener('click', initAudioContext, { once: true })

    await fetchNotifications()

    pollInterval = setInterval(fetchNotifications, 3000)
  }
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-stone-50 via-amber-50 to-white">
    <div class="mx-auto max-w-7xl px-4 py-6">
      <div class="grid gap-6 lg:grid-cols-12">
<aside class="lg:col-span-3">
  <div class="rounded-2xl border border-green-100 bg-white/80 backdrop-blur shadow-sm overflow-hidden">
    <div class="p-4 border-b border-green-100">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 border border-green-200 overflow-hidden shadow shadow-green-100">
          <img :src="asset('images/logo_kalpataru.jpg')" alt="KF" class="h-8 w-8 object-contain" /></div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <span
              class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
              :class="{
                'bg-green-100 text-green-700': userRole === 'lurah',
                'bg-amber-100 text-amber-700': userRole === 'staff',
                'bg-red-100 text-red-700': userRole === 'admin',
              }"
            >{{ userRole }}</span>
          </div>
          <div class="mt-0.5 truncate text-sm font-semibold text-gray-900" :title="page.props.auth.user.name">
            {{ page.props.auth.user.name }}
          </div>
        </div>

        <div v-if="userRole === 'lurah' || userRole === 'staff'" class="shrink-0">
          <button
            ref="bellRef"
            type="button"
            @click="toggleNotif"
            class="relative flex h-9 w-9 items-center justify-center rounded-xl border transition"
            :class="notifOpen ? 'bg-green-100 border-green-300 text-green-700' : 'bg-white border-green-100 text-gray-500 hover:bg-green-50 hover:text-green-700'"
            title="Notifikasi"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
              v-if="unreadCount > 0"
              class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
            >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
          </button>
        </div>
      </div>
    </div>

    <nav class="p-3 space-y-2">
      <Link
        :href="route('dashboard')"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
        :style="isCurrent('dashboard')
          ? 'background:#e0f2fe;color:#0369a1;border-color:#7dd3fc'
          : 'background:#fff;color:#374151;border-color:transparent'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background-color:#0ea5e9"></span>
          Home
        </span>
        <span v-if="isCurrent('dashboard')" class="text-xs" style="color:#0369a1">Aktif</span>
      </Link>

      <div v-if="userRole !== 'lurah'" class="rounded-xl border border-transparent bg-white">
        <button
          type="button"
          class="w-full flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
          :style="onTemplatePage
            ? 'background:#ede9fe;color:#4c1d95;border-color:#c4b5fd'
            : 'background:#fff;color:#374151;border-color:transparent'"
          @click="templatesOpen = !templatesOpen"
        >
          <span class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background-color:#8b5cf6"></span>
            Template Surat ({{ templates.length }})
          </span>
          <span class="text-xs">
            {{ templatesOpen ? 'â€”' : '+' }}
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
                ? 'bg-violet-50 text-violet-900 border-violet-200'
                : 'bg-white text-gray-700 border-transparent hover:bg-violet-50/60 hover:text-violet-900'"
            >
              {{ t.label }}
            </Link>
          </div>

          <Link
            :href="route('surat-templates.index')"
            class="mt-2 block rounded-xl px-4 py-2 text-xs font-semibold text-violet-900 bg-violet-50 border border-violet-200 hover:bg-violet-100 transition"
          >
            Lihat Ringkasan Template
          </Link>
        </div>
      </div>

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

      <Link
        v-if="userRole === 'staff'"
        :href="route('disposisi-tugas.index')"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
        :class="isCurrent('disposisi-tugas.index')
          ? 'bg-blue-50 text-blue-900 border-blue-200'
          : 'bg-white text-gray-700 border-transparent hover:bg-blue-50/60 hover:text-blue-900'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
          Disposisi Tugas
        </span>
        <span
          v-if="unreadCount > 0 && !isCurrent('disposisi-tugas.index')"
          class="flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
        >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
        <span v-else-if="isCurrent('disposisi-tugas.index')" class="text-xs text-blue-700">Aktif</span>
      </Link>

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

      <Link
        v-if="userRole === 'admin'"
        :href="route('admin.pengguna.index')"
        class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition border"
        :class="isCurrent('admin.pengguna.index')
          ? 'bg-rose-50 text-rose-900 border-rose-200'
          : 'bg-white text-gray-700 border-transparent hover:bg-rose-50/60 hover:text-rose-900'"
      >
        <span class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
          Manajemen Pengguna
        </span>
        <span v-if="isCurrent('admin.pengguna.index')" class="text-xs text-rose-700">Aktif</span>
      </Link>

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

        <main class="lg:col-span-9">
          <div class="rounded-2xl border border-green-100 bg-white/80 backdrop-blur shadow-sm">
            <div class="p-6">
              <slot>
              </slot>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="notifOpen" :style="panelStyle" class="rounded-2xl border border-green-100 bg-white shadow-2xl">
      <div class="flex items-center justify-between border-b border-green-50 px-4 py-3">
        <span class="text-sm font-semibold text-gray-800">{{ userRole === 'staff' ? 'Notifikasi Disposisi' : 'Notifikasi Arsip' }}</span>
        <button
          v-if="unreadCount > 0"
          type="button"
          @click="markAllRead"
          class="text-xs font-medium text-green-600 hover:text-green-800 transition"
        >Tandai semua dibaca</button>
      </div>

      <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
        <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
          Tidak ada notifikasi.
        </div>
        <button
          v-for="notif in notifications"
          :key="notif.id"
          type="button"
          @click="openNotif(notif)"
          class="w-full text-left px-4 py-3 transition hover:bg-green-50/60 flex items-start gap-3"
          :class="{ 'bg-green-50/40': !notif.is_read }"
        >
          <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="notif.is_read ? 'bg-gray-200' : 'bg-green-500'"></span>
          <div class="flex-1">
            <p class="text-sm leading-snug break-words" :class="notif.is_read ? 'text-gray-500' : 'text-gray-800 font-medium'">
              {{ notif.message }}
            </p>
            <p class="mt-0.5 text-xs text-gray-400">{{ formatDate(notif.created_at) }}</p>
          </div>
        </button>
      </div>

      <div class="border-t border-green-50 px-4 py-2 text-center">
        <Link
          v-if="userRole === 'staff'"
          :href="route('disposisi-tugas.index')"
          @click="notifOpen = false"
          class="text-xs font-medium text-blue-600 hover:text-blue-800 transition"
        >Lihat Disposisi Tugas â†’</Link>
        <Link
          v-else
          :href="route('arsip-surat.index')"
          @click="notifOpen = false"
          class="text-xs font-medium text-green-600 hover:text-green-800 transition"
        >Lihat Arsip Surat â†’</Link>
      </div>
    </div>

    <div v-if="notifOpen" class="fixed inset-0" style="z-index: 9998;" @click="notifOpen = false"></div>
  </Teleport>
</template>
