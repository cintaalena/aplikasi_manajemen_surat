<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import { computed, reactive, ref, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({ slug: String })
const isDomisili = computed(() => props.slug === 'keterangan-domisili')
const isKelahiran = computed(() => props.slug === 'keterangan-kelahiran')

const showPreview = ref(false)
const printMode = ref(false)

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
  alamatDomisili: '',
  rt: '',
  rw: '',
  kelurahan: '',
  kecamatan: '',
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

// reset setelah print selesai, lalu tampilkan nomor berikutnya
const handleAfterPrint = () => {
  printMode.value = false
  // Tampilkan nomor berikutnya untuk surat selanjutnya
  if (lastCounterSnapshot.value) {
    setNoSuratFromCounter(lastCounterSnapshot.value)
  }
}

onMounted(() => window.addEventListener('afterprint', handleAfterPrint))
onBeforeUnmount(() => window.removeEventListener('afterprint', handleAfterPrint))

const finalizeLetter = async (templateSlug) => {
  if (!selectedIndexCode.value) {
    throw new Error('Silakan pilih kategori dan nomor index terlebih dahulu!')
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (!csrfToken) throw new Error('CSRF token tidak ditemukan. Silakan refresh halaman.')

  const res = await fetch(`/surat/${templateSlug}/finalize`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    body: JSON.stringify({
      title: form.judulSurat,
      index_code: selectedIndexCode.value,
      payload: { ...form },
    }),
  })

  if (!res.ok) throw new Error(await res.text())
  return await res.json() // {id,noSurat,urut,monthRoman,year}
}

const printNow = async () => {
  try {
    const result = await finalizeLetter(props.slug)

    // Simpan nomor surat resmi yang dipakai untuk dicetak
    form.noSurat = result.noSurat

    // Update snapshot counter dengan urut yang baru saja dipakai
    // Sehingga setelah print, preview akan menampilkan nomor berikutnya
    lastCounterSnapshot.value = {
      count: result.urut,
      monthRoman: result.monthRoman,
      year: result.year,
    }

    showPreview.value = true
    printMode.value = true

    await nextTick()
    window.print()
  } catch (e) {
    console.error(e)
    alert('Gagal finalisasi / cetak surat. Coba ulangi.')
  }
}
</script>

<template>
  <AppLayout>
    <div v-if="!isDomisili && !isKelahiran">
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
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white
                   bg-gradient-to-r from-purple-600 to-fuchsia-500
                   hover:from-purple-700 hover:to-fuchsia-600 transition"
            @click.prevent.stop="printNow"
          >
            Cetak
          </button>
        </div>
      </div>

      

      <div class="grid gap-6 lg:grid-cols-2">
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
                <textarea
                  v-model="form.alamatAsal"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat Domisili</label>
                <textarea
                  v-model="form.alamatDomisili"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                />
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
          </div>
        </div>

        <!-- PREVIEW (di page, untuk lihat saja) -->
        <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm" :class="showPreview ? '' : 'opacity-60'">
          <div class="print:hidden flex items-center justify-between">
            <div class="text-sm font-semibold text-gray-900">Preview Surat</div>
            <div class="text-xs text-gray-500">Format siap cetak</div>
          </div>

          <div v-if="showPreview">
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
