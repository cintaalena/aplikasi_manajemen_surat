<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import KematianTemplate from '@/Components/Surat/KematianTemplate.vue'
import PindahTemplate from '@/Components/Surat/PindahTemplate.vue'
import { computed, reactive, ref, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ slug: String })
const isDomisili = computed(() => props.slug === 'keterangan-domisili')
const isKelahiran = computed(() => props.slug === 'keterangan-kelahiran')
const isKematian = computed(() => props.slug === 'keterangan-kematian')
const isPindah = computed(() => props.slug === 'keterangan-pindah')

const showPreview = ref(false)
const printMode = ref(false)
const isPrinting = ref(false)

// Preview scale — fit 794px A4 content into the container
const previewContainerRef = ref(null)
const previewInnerRef = ref(null)
const previewScale = ref(1)
const previewScaledHeight = ref(0)
let previewResizeObserver = null

const recalcPreviewScale = () => {
  if (!previewContainerRef.value || !previewInnerRef.value) return
  const padding = 40 // p-5 both sides
  const containerWidth = previewContainerRef.value.clientWidth - padding
  const scale = Math.min(1, containerWidth / 794)
  previewScale.value = scale
  nextTick(() => {
    if (previewInnerRef.value) {
      previewScaledHeight.value = previewInnerRef.value.offsetHeight * scale
    }
  })
}

watch(showPreview, (val) => {
  if (val) {
    nextTick(() => {
      recalcPreviewScale()
      if (previewContainerRef.value) {
        previewResizeObserver = new ResizeObserver(recalcPreviewScale)
        previewResizeObserver.observe(previewContainerRef.value)
      }
    })
  } else {
    previewResizeObserver?.disconnect()
    previewResizeObserver = null
  }
})

const form = reactive({
  judulSurat: 'Surat Keterangan Domisili',
  noSurat: '',
  tanggalSurat: new Date().toISOString().slice(0, 10),

  nama: '',
  nik: '',
  tempatLahir: '',
  tanggalLahir: '',
  jenisKelamin: '',
  pekerjaan: '',
  agama: '',
  namaAyah: '',
  namaIbu: '',
  alamat: '',
  alamatAsal: '',
  alamatAsalJalan: '',
  alamatAsalRt: '',
  alamatAsalRw: '',
  alamatAsalKelurahan: '',
  alamatAsalKecamatan: '',
  alamatDomisili: '',
  rt: '',
  rw: '',
  kelurahan: '',
  kecamatan: '',
  sebabKematian: '',
  tanggalMeninggal: '',
  tempatMeninggal: '',
  umur: '',
  statusPerkawinan: '',
  kewarganegaraan: 'Indonesia',
  alamatTujuan: '',
  desaTujuan: '',
  kecamatanTujuan: '',
  kabupatenTujuan: '',
  provinsiTujuan: '',
  tanggalPindah: '',
  alasanPindah: '',
  pengikut: [],
})

const tanggalIndo = (yyyy_mm_dd) => {
  if (!yyyy_mm_dd) return ''
  let d
  if (typeof yyyy_mm_dd === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(yyyy_mm_dd)) {
    const [y, m, day] = yyyy_mm_dd.split('-').map(Number)
    d = new Date(y, m - 1, day)
  } else {
    d = new Date(yyyy_mm_dd)
  }
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

const generateNoSurat = ({ urut, indexCode, monthRoman, year }) => {
  const seq = String(urut ?? '').replace(/\D/g, '') || '---'
  return `${seq}/Kel.Ftbs.${indexCode}/${monthRoman}/${year}`
}


const apiBase = '/api/letter-counters'

const getCounter = async (templateSlug) => {
  try {
    const res = await fetch(`${apiBase}/${templateSlug}`, { 
      headers: { 
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    if (!res.ok) {
      const errorText = await res.text()
      console.error('Counter API Error:', res.status, errorText)
      throw new Error(`Gagal mengambil counter: ${res.status}`)
    }
    return await res.json() // {count, monthRoman, year}
  } catch (error) {
    console.error('getCounter error:', error)
    throw error
  }
}

const incrementCounter = async (templateSlug) => {
  const res = await fetch(`${apiBase}/${templateSlug}/increment`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
  })
  if (!res.ok) throw new Error('Gagal increment counter')
  return await res.json() // {count, monthRoman, year}
}

const addPengikut = () => {
  form.pengikut.push({
    nama: '',
    nik: '',
    tempatLahir: '',
    tanggalLahir: '',
    hubungan: '',
  })
}

const removePengikut = (index) => {
  form.pengikut.splice(index, 1)
}

const indexGroups = ref([])        // [{key,label,items:[{code,name}]}]
const selectedGroupKey = ref('')
const selectedIndexCode = ref('')
const isLoadingIndexes = ref(true)
const indexLoadError = ref(null)

const loadIndexGroups = async () => {
  isLoadingIndexes.value = true
  indexLoadError.value = null
  try {
    console.log('🔄 Loading index groups from API...')
    const res = await fetch('/api/letter-index-groups', { 
      headers: { 
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    if (!res.ok) {
      const errorText = await res.text()
      console.error('❌ Index Groups API Error:', res.status, errorText)
      throw new Error(`Gagal mengambil kategori nomor index: ${res.status}`)
    }
    const data = await res.json()
    console.log('✅ Index Groups loaded successfully:', data)
    console.log('📊 Total categories:', data.length)
    indexGroups.value = data
    isLoadingIndexes.value = false
    
    if (data.length === 0) {
      console.warn('⚠️ No index groups found in response')
    }
  } catch (error) {
    console.error('❌ loadIndexGroups error:', error)
    indexLoadError.value = error.message
    isLoadingIndexes.value = false
  }
}

const filteredIndexItems = computed(() => {
  const g = indexGroups.value.find(x => x.key === selectedGroupKey.value)
  const items = g?.items ?? []
  console.log('Filtered items for', selectedGroupKey.value, ':', items)
  return items
})

// ketika kategori berubah -> set index pertama pada kategori itu
watch(selectedGroupKey, (newKey) => {
  console.log('Category changed to:', newKey)
  if (!newKey) {
    selectedIndexCode.value = ''
    return
  }
  const g = indexGroups.value.find(x => x.key === newKey)
  // Reset ke empty string agar user memilih sendiri
  selectedIndexCode.value = ''
  console.log('Index code reset, please select an index')
})


const lastCounterSnapshot = ref(null) // simpan result getCounter() terakhir biar mudah re-render

const setNoSuratFromCounter = (counter) => {
  if (!counter || !selectedIndexCode.value) {
    form.noSurat = ''
    return
  }
  // Tampilkan nomor BERIKUTNYA: count + 1 (nomor yang akan dipakai saat Cetak)
  const urut = (counter.count ?? 0) + 1
  const indexCode = selectedIndexCode.value
  form.noSurat = generateNoSurat({
    urut: String(urut),
    indexCode,
    monthRoman: counter.monthRoman,
    year: counter.year,
  })
}

// ketika index berubah -> re-render nomor surat memakai counter terakhir (tanpa fetch berulang)
watch(selectedIndexCode, (newCode) => {
  console.log('Index code changed to:', newCode)
  if (lastCounterSnapshot.value) {
    setNoSuratFromCounter(lastCounterSnapshot.value)
  }
})

// init: load kategori dulu, lalu ambil counter terakhir (nomor final terakhir)
onMounted(async () => {
  console.log('=== Initializing form for slug:', props.slug, '===')
  
  try {
    // Set judul surat berdasarkan slug
    if (isDomisili.value) {
      form.judulSurat = 'Surat Keterangan Domisili'
    } else if (isKelahiran.value) {
      form.judulSurat = 'Surat Keterangan Kelahiran'
    } else if (isKematian.value) {
      form.judulSurat = 'Surat Keterangan Kematian'
    } else if (isPindah.value) {
      form.judulSurat = 'Surat Keterangan Pindah'
    }
    console.log('Letter title set to:', form.judulSurat)

    // Load index groups
    console.log('Loading index groups...')
    await loadIndexGroups()
    console.log('Index groups loaded. Waiting for user to select category and index.')

    // Tunggu reactive update selesai
    await nextTick()

    // Ambil counter untuk template ini (counter terpisah per slug)
    console.log('Fetching counter...')
    const counter = await getCounter(props.slug)
    console.log('Counter fetched:', counter)
    lastCounterSnapshot.value = counter
    
    // Nomor surat akan di-generate otomatis ketika user memilih index code
    console.log('=== Initialization complete. Please select category and index. ===')
  } catch (e) {
    console.error('Error initializing letter form:', e)
    // Jangan tampilkan alert, biarkan user tetap bisa mengisi form
  }
})

// reset setelah print selesai — tampilkan konfirmasi sebelum finalize
const showPrintConfirm = ref(false)

const handleAfterPrint = () => {
  printMode.value = false
  showPrintConfirm.value = true
}

onMounted(() => window.addEventListener('afterprint', handleAfterPrint))
onBeforeUnmount(() => {
  window.removeEventListener('afterprint', handleAfterPrint)
  previewResizeObserver?.disconnect()
})

const finalizeLetter = async (templateSlug) => {
  if (!selectedIndexCode.value) {
    throw new Error('Silakan pilih kategori dan nomor index terlebih dahulu!')
  }

  const res = await fetch(`/surat/${templateSlug}/finalize`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    body: JSON.stringify({
      title: form.judulSurat,
      index_code: selectedIndexCode.value,
      payload: { ...form },
    }),
  })

  if (!res.ok) {
    let errMsg = `Server error ${res.status}`
    try {
      const errData = await res.json()
      errMsg = errData.message || errData.error || JSON.stringify(errData)
    } catch {
      errMsg = await res.text().catch(() => `Server error ${res.status}`)
    }
    if (res.status === 401) throw new Error('Sesi habis. Silakan refresh halaman dan login kembali.')
    if (res.status === 403) throw new Error('Akses ditolak. Silakan refresh halaman.')
    if (res.status === 429) throw new Error('Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.')
    if (res.status === 422) throw new Error('Data tidak valid: ' + errMsg)
    throw new Error(errMsg)
  }

  const data = await res.json()
  if (!data || !data.noSurat) throw new Error('Respons tidak valid dari server.')
  return data // {id,noSurat,urut,monthRoman,year}
}

const printNow = async () => {
  if (isPrinting.value) return
  if (!selectedIndexCode.value) {
    alert('Silakan pilih kategori dan nomor index terlebih dahulu!')
    return
  }

  // Langsung cetak dengan nomor preview (belum finalize)
  // Finalize hanya terjadi setelah user konfirmasi berhasil cetak
  showPreview.value = true
  printMode.value = true
  await nextTick()
  window.print()
}

const confirmFinalize = async (confirmed) => {
  showPrintConfirm.value = false

  if (!confirmed) {
    // User batalkan: kembalikan tampilan nomor preview
    if (lastCounterSnapshot.value) setNoSuratFromCounter(lastCounterSnapshot.value)
    return
  }

  isPrinting.value = true
  try {
    await finalizeLetter(props.slug)
    // Berhasil disimpan: kembali ke dashboard dengan form bersih
    router.visit('/dashboard')
  } catch (e) {
    console.error('finalize error:', e)
    alert(e.message || 'Gagal menyimpan data surat ke arsip. Silakan coba lagi.')
    isPrinting.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div v-if="!isDomisili && !isKelahiran && !isKematian && !isPindah">
      <h1 class="text-xl font-bold text-gray-900">Template: {{ slug }}</h1>
      <p class="mt-2 text-sm text-gray-600">Form dan template untuk surat ini akan diisi nanti.</p>
    </div>

    <div v-else class="space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ form.judulSurat }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            Isi form → klik <b>View</b> untuk preview → klik <b>Cetak</b> untuk print.
          </p>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-xl border border-purple-200 bg-white px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50 transition"
            @click.prevent.stop="showPreview = !showPreview"
          >
            {{ showPreview ? 'Tutup View' : 'View' }}
          </button>

          <button
            type="button"
            :disabled="isPrinting || !selectedIndexCode"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white
                   bg-gradient-to-r from-purple-600 to-fuchsia-500
                   hover:from-purple-700 hover:to-fuchsia-600 transition
                   disabled:opacity-50 disabled:cursor-not-allowed"
            @click.prevent.stop="printNow"
          >
            {{ isPrinting ? 'Memproses...' : 'Cetak' }}
          </button>
        </div>
      </div>

      

      <div class="grid gap-6 lg:grid-cols-[minmax(320px,420px)_1fr]" :class="showPreview ? '' : 'lg:grid-cols-2'">
        <!-- FORM -->
        <div class="print:hidden rounded-2xl border border-purple-100 bg-white p-5 shadow-sm">
          <div class="text-sm font-semibold text-gray-900">Form Data</div>

          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <!-- KATEGORI -->
            <div class="sm:col-span-2">
              <label class="text-xs font-semibold text-gray-700">Kategori Nomor Index</label>
              <select
                v-model="selectedGroupKey"
                :disabled="isLoadingIndexes"
                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="" disabled>
                  {{ isLoadingIndexes ? 'Memuat kategori...' : (indexLoadError ? 'Error memuat data' : 'Pilih Kategori') }}
                </option>
                <option v-for="g in indexGroups" :key="g.key" :value="g.key">
                  {{ g.label }}
                </option>
              </select>
              <p v-if="indexLoadError" class="mt-1 text-xs text-red-600">{{ indexLoadError }}</p>
              <p v-else-if="!isLoadingIndexes && indexGroups.length > 0" class="mt-1 text-xs text-green-600">
                ✓ {{ indexGroups.length }} kategori tersedia
              </p>
            </div>

            <!-- INDEX -->
            <div class="sm:col-span-2">
              <label class="text-xs font-semibold text-gray-700">Nomor Index</label>
              <select
                v-model="selectedIndexCode"
                :disabled="isLoadingIndexes || !selectedGroupKey || filteredIndexItems.length === 0"
                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="" disabled>
                  {{ isLoadingIndexes ? 'Memuat...' : (!selectedGroupKey ? 'Pilih kategori dulu' : 'Pilih Nomor Index') }}
                </option>
                <option v-for="item in filteredIndexItems" :key="item.code" :value="item.code">
                  {{ item.code }} - {{ item.name }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500">
                Pilih nomor index sesuai klasifikasi surat.
              </p>
            </div>

            <!-- NOMOR SURAT -->
            <div class="sm:col-span-2">
              <label class="text-xs font-semibold text-gray-700">Nomor Surat</label>
              <input
                v-model="form.noSurat"
                readonly
                :placeholder="selectedIndexCode ? '' : 'Pilih nomor index untuk generate nomor surat'"
                class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 focus:border-purple-400 focus:ring-purple-400"
              />
              <p class="mt-1 text-xs text-gray-500">
                <span v-if="!selectedIndexCode" class="text-amber-600 font-semibold">⚠️ Pilih kategori dan nomor index terlebih dahulu.</span>
                <span v-else>Nomor urut naik hanya saat <b>Cetak</b>. Saat edit, memakai nomor final terakhir.</span>
              </p>
            </div>

            <!-- TANGGAL SURAT -->
            <div>
              <label class="text-xs font-semibold text-gray-700">Tanggal Surat</label>
              <input
                type="date"
                v-model="form.tanggalSurat"
                class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
              />
            </div>

            <!-- Domisili Fields -->
            <template v-if="isDomisili">
              <div>
                <label class="text-xs font-semibold text-gray-700">NIK</label>
                <input
                  v-model="form.nik"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  v-model="form.nama"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Jenis Kelamin</label>
                <select
                  v-model="form.jenisKelamin"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                >
                  <option value="">Pilih</option>
                  <option>Laki-laki</option>
                  <option>Perempuan</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  type="date"
                  v-model="form.tanggalLahir"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat Asal (sesuai KTP)</label>
                <div class="mt-1 grid grid-cols-1 gap-2">
                  <input
                    v-model="form.alamatAsalJalan"
                    type="text"
                    class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Jalan / Alamat"
                  />
                  <div class="grid grid-cols-2 gap-2">
                    <input
                      v-model="form.alamatAsalRt"
                      type="text"
                      class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                      placeholder="RT (mis: 001)"
                    />
                    <input
                      v-model="form.alamatAsalRw"
                      type="text"
                      class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                      placeholder="RW (mis: 002)"
                    />
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <input
                      v-model="form.alamatAsalKelurahan"
                      type="text"
                      class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                      placeholder="Kelurahan"
                    />
                    <input
                      v-model="form.alamatAsalKecamatan"
                      type="text"
                      class="w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                      placeholder="Kecamatan"
                    />
                  </div>
                </div>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat Domisili</label>
                <textarea
                  v-model="form.alamatDomisili"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                ></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RT</label>
                <input
                  v-model="form.rt"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RW</label>
                <input
                  v-model="form.rw"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>
            </template>

            <!-- Kelahiran Fields -->
            <template v-else-if="isKelahiran">
              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  v-model="form.nama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan nama lengkap"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Jenis Kelamin</label>
                <select
                  v-model="form.jenisKelamin"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                >
                  <option value="">Pilih Jenis Kelamin</option>
                  <option value="Laki-laki">Laki-laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Agama</label>
                <input
                  v-model="form.agama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan agama"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan tempat lahir"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  v-model="form.tanggalLahir"
                  type="date"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Nama Ayah</label>
                <input
                  v-model="form.namaAyah"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan nama ayah"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Nama Ibu</label>
                <input
                  v-model="form.namaIbu"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan nama ibu"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan pekerjaan"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Jalan / Alamat</label>
                <input
                  v-model="form.alamat"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: Jl. Alor No.1 A"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RT</label>
                <input
                  v-model="form.rt"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="001"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RW</label>
                <input
                  v-model="form.rw"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="002"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kelurahan</label>
                <input
                  v-model="form.kelurahan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Fatubesi"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kecamatan</label>
                <input
                  v-model="form.kecamatan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Kota Lama"
                />
              </div>
            </template>

            <!-- Kematian Fields -->
            <template v-else-if="isKematian">
              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  v-model="form.nama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan nama lengkap"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Jenis Kelamin</label>
                <select
                  v-model="form.jenisKelamin"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                >
                  <option value="">Pilih Jenis Kelamin</option>
                  <option value="Laki-laki">Laki-laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">NIK</label>
                <input
                  v-model="form.nik"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan NIK"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan tempat lahir"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  v-model="form.tanggalLahir"
                  type="date"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Agama</label>
                <input
                  v-model="form.agama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan agama"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat</label>
                <textarea
                  v-model="form.alamat"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan alamat"
                ></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Sebab Kematian</label>
                <input
                  v-model="form.sebabKematian"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: sakit"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Meninggal</label>
                <input
                  v-model="form.tanggalMeninggal"
                  type="date"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Umur</label>
                <input
                  v-model="form.umur"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: 78 Tahun"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Meninggal</label>
                <input
                  v-model="form.tempatMeninggal"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: Kupang"
                />
              </div>
            </template>

            <!-- Pindahan Fields -->
            <template v-else-if="isPindah">
              <div class="sm:col-span-2">
                <div class="space-y-4">

              <div>
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  v-model="form.nama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan nama lengkap"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Jenis Kelamin</label>
                <select
                  v-model="form.jenisKelamin"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                >
                  <option value="">Pilih Jenis Kelamin</option>
                  <option value="Laki-laki">Laki-laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">NIK</label>
                <input
                  v-model="form.nik"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan NIK"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan tempat lahir"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  v-model="form.tanggalLahir"
                  type="date"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Status Perkawinan</label>
                <input
                  v-model="form.statusPerkawinan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: Kawin"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kewarganegaraan</label>
                <input
                  v-model="form.kewarganegaraan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: Indonesia"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Agama</label>
                <input
                  v-model="form.agama"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan agama"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan pekerjaan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Alamat Asal</label>
                <textarea
                  v-model="form.alamatAsal"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan alamat asal"
                ></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Alamat Tujuan</label>
                <textarea
                  v-model="form.alamatTujuan"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan alamat tujuan"
                ></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Desa/Kelurahan Tujuan</label>
                <input
                  v-model="form.desaTujuan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan desa/kelurahan tujuan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kecamatan Tujuan</label>
                <input
                  v-model="form.kecamatanTujuan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan kecamatan tujuan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kabupaten/Kota Tujuan</label>
                <input
                  v-model="form.kabupatenTujuan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan kabupaten/kota tujuan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Provinsi Tujuan</label>
                <input
                  v-model="form.provinsiTujuan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan provinsi tujuan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Pindah</label>
                <input
                  v-model="form.tanggalPindah"
                  type="date"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Alasan Pindah</label>
                <input
                  v-model="form.alasanPindah"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: Pindah Domisili"
                />
              </div>

              <div class="pt-2">
                <div class="mb-2 flex items-center justify-between">
                  <label class="text-xs font-semibold text-gray-700">Data Pengikut</label>
                  <button
                    type="button"
                    @click="addPengikut"
                    class="rounded-xl bg-purple-600 px-3 py-2 text-sm font-medium text-white hover:bg-purple-700"
                  >
                    + Tambah Pengikut
                  </button>
                </div>

                <div
                  v-for="(item, index) in form.pengikut"
                  :key="index"
                  class="mb-3 rounded-2xl border border-gray-200 p-4"
                >
                  <div class="mb-3 flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-700">
                      Pengikut {{ index + 1 }}
                    </div>
                    <button
                      type="button"
                      @click="removePengikut(index)"
                      class="text-sm font-medium text-red-600 hover:text-red-700"
                    >
                      Hapus
                    </button>
                  </div>

                  <div class="space-y-3">
                    <div>
                      <label class="text-xs font-semibold text-gray-700">Nama</label>
                      <input
                        v-model="item.nama"
                        type="text"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                        placeholder="Masukkan nama pengikut"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-semibold text-gray-700">NIK</label>
                      <input
                        v-model="item.nik"
                        type="text"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                        placeholder="Masukkan NIK pengikut"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                      <input
                        v-model="item.tempatLahir"
                        type="text"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                        placeholder="Contoh: Kupang"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                      <input
                        v-model="item.tanggalLahir"
                        type="date"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                      />
                    </div>

                   <div>
                      <label class="text-xs font-semibold text-gray-700">Hubungan Keluarga</label>
                      <input
                        v-model="item.hubungan"
                        type="text"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                        placeholder="Contoh: Anak"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
          </div>
        </div>
        
        <!-- PREVIEW (di page, untuk lihat saja) -->
        <div
          ref="previewContainerRef"
          class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm"
          :class="{ 'opacity-60': !showPreview }"
        >
          <div class="print:hidden flex items-center justify-between">
            <div class="text-sm font-semibold text-gray-900">Preview Surat</div>
            <div class="text-xs text-gray-500">Format siap cetak</div>
          </div>

          <div
            v-if="showPreview"
            class="mt-3 relative overflow-hidden"
            :style="previewScaledHeight > 0 ? { height: previewScaledHeight + 'px' } : {}"
          >
            <div
              ref="previewInnerRef"
              :style="{
                width: '794px',
                transform: `scale(${previewScale})`,
                transformOrigin: 'top left',
                position: 'absolute',
                top: 0,
                left: 0,
              }"
            >
              <DomisiliTemplate
                v-if="isDomisili"
                :form="form"
                :tanggalIndo="tanggalIndo"
              />
              <KelahiranTemplate
                v-else-if="isKelahiran"
                :form="form"
                :tanggalIndo="tanggalIndo"
              />
              <KematianTemplate
                v-else-if="isKematian"
                :form="form"
                :tanggalIndo="tanggalIndo"
              />
              <PindahTemplate
                v-else-if="isPindah"
                :form="form"
                :tanggalIndo="tanggalIndo"
              />
            </div>
          </div>

          <div v-if="!showPreview" class="print:hidden mt-3 text-xs text-gray-500">
            Klik <b>View</b> untuk menampilkan preview lebih jelas sebelum cetak.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- ✅ PRINT OVERLAY: keluar dari AppLayout -->
  <Teleport to="body">
    <div v-if="printMode" class="print-overlay">
      <div class="print-sheet">
        <DomisiliTemplate v-if="isDomisili" :form="form" :tanggalIndo="tanggalIndo" />
        <KelahiranTemplate v-else-if="isKelahiran" :form="form" :tanggalIndo="tanggalIndo" />
        <KematianTemplate v-else-if="isKematian" :form="form" :tanggalIndo="tanggalIndo" />
        <PindahTemplate v-else-if="isPindah" :form="form" :tanggalIndo="tanggalIndo" />
      </div>
    </div>
  </Teleport>

  <!-- ✅ KONFIRMASI setelah print dialog ditutup -->
  <Teleport to="body">
    <div v-if="showPrintConfirm" class="fixed inset-0 z-[999998] flex items-center justify-center bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-base font-semibold text-gray-900">Konfirmasi Cetak</h3>
        <p class="mt-2 text-sm text-gray-600">
          Apakah surat berhasil dicetak atau disimpan sebagai PDF?
          <br />
          <span class="text-xs text-gray-400 mt-1 block">Jika Ya, nomor surat akan dicatat dan masuk ke arsip.</span>
        </p>
        <div class="mt-5 flex gap-3 justify-end">
          <button
            type="button"
            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            @click="confirmFinalize(false)"
          >
            Tidak, Batal
          </button>
          <button
            type="button"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-fuchsia-500 hover:from-purple-700 hover:to-fuchsia-600 transition"
            @click="confirmFinalize(true)"
          >
            Ya, Berhasil Dicetak
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style>
.print-overlay{
  position: fixed;
  inset: 0;
  background: #fff;
  z-index: 999999;
}

.print-sheet{
  width: 210mm;
  min-height: 297mm;
  margin: 0 auto;
  padding: 20mm;
  box-sizing: border-box;
  background: #fff;
}

@page{
  size: A4 portrait;
  margin: 0;
}

@media print{
  #app{ display: none !important; }
  .print-overlay{ display: block !important; }

  html, body{
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
    background: #fff !important;
  }
}
</style>
