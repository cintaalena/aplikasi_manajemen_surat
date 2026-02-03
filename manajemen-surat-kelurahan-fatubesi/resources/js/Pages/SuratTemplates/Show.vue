<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import { computed, reactive, ref, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({ slug: String })
const isDomisili = computed(() => props.slug === 'keterangan-domisili')

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
  alamatAsal: '',
  alamatDomisili: '',
  rt: '',
  rw: '',
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
  const res = await fetch(`${apiBase}/${templateSlug}`, { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error('Gagal mengambil counter')
  return await res.json() // {count, monthRoman, year}
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
const selectedIndexCode = ref(null)

const loadIndexGroups = async () => {
  const res = await fetch('/api/letter-index-groups', { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error('Gagal mengambil kategori nomor index')
  indexGroups.value = await res.json()

  // default kategori pertama
  if (!selectedGroupKey.value && indexGroups.value.length) {
    selectedGroupKey.value = indexGroups.value[0].key
  }
}

const filteredIndexItems = computed(() => {
  const g = indexGroups.value.find(x => x.key === selectedGroupKey.value)
  return g?.items ?? []
})

// ketika kategori berubah -> set index pertama pada kategori itu
watch(selectedGroupKey, (newKey) => {
  const g = indexGroups.value.find(x => x.key === newKey)
  const first = g?.items?.[0]
  selectedIndexCode.value = first ? first.code : null
})


const lastCounterSnapshot = ref(null) // simpan result getCounter() terakhir biar mudah re-render

const setNoSuratFromCounter = (counter) => {
  if (!counter?.count) {
    form.noSurat = ''
    return
  }
  const indexCode = selectedIndexCode.value ?? 475 // fallback
  form.noSurat = generateNoSurat({
    urut: String(counter.count),
    indexCode,
    monthRoman: counter.monthRoman,
    year: counter.year,
  })
}

// ketika index berubah -> re-render nomor surat memakai counter terakhir (tanpa fetch berulang)
watch(selectedIndexCode, () => {
  if (lastCounterSnapshot.value) setNoSuratFromCounter(lastCounterSnapshot.value)
})

// init: load kategori dulu, lalu ambil counter terakhir (nomor final terakhir)
onMounted(async () => {
  try {
    await loadIndexGroups()
    // watcher selectedGroupKey akan set selectedIndexCode

    const counter = await getCounter(props.slug)
    lastCounterSnapshot.value = counter
    setNoSuratFromCounter(counter)
  } catch (e) {
    console.error(e)
  }
})

// reset setelah print selesai
const handleAfterPrint = () => {
  printMode.value = false
}

onMounted(() => window.addEventListener('afterprint', handleAfterPrint))
onBeforeUnmount(() => window.removeEventListener('afterprint', handleAfterPrint))

const finalizeLetter = async (templateSlug) => {
  const res = await fetch(`/api/letters/${templateSlug}/finalize`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({
      title: form.judulSurat,
      index_code: selectedIndexCode.value ?? 475,
      payload: { ...form }, // simpan semua data form
    }),
  })

  if (!res.ok) throw new Error(await res.text())
  return await res.json() // {id,noSurat,urut,monthRoman,year}
}

const printNow = async () => {
  try {
    const result = await finalizeLetter(props.slug)

   
    form.noSurat = result.noSurat

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
    <div v-if="!isDomisili">
      <h1 class="text-xl font-bold text-gray-900">Template: {{ slug }}</h1>
      <p class="mt-2 text-sm text-gray-600">Form dan template untuk surat ini akan diisi nanti.</p>
    </div>

    <div v-else class="space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-900">Surat Keterangan Domisili</h1>
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
                class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
              >
                <option v-for="g in indexGroups" :key="g.key" :value="g.key">
                  {{ g.label }}
                </option>
              </select>
            </div>

            <!-- INDEX -->
            <div class="sm:col-span-2">
              <label class="text-xs font-semibold text-gray-700">Nomor Index</label>
              <select
                v-model="selectedIndexCode"
                class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
              >
                <option v-for="item in filteredIndexItems" :key="item.code" :value="item.code">
                  {{ item.name }} - {{ item.code }}
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
                class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 focus:border-purple-400 focus:ring-purple-400"
              />
              <p class="mt-1 text-xs text-gray-500">
                Nomor urut naik hanya saat <b>Cetak</b>. Saat edit, memakai nomor final terakhir.
              </p>
            </div>

            <div>
              <label class="text-xs font-semibold text-gray-700">Tanggal Surat</label>
              <input
                type="date"
                v-model="form.tanggalSurat"
                class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
              />
            </div>

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
          </div>
        </div>

        <!-- PREVIEW (di page, untuk lihat saja) -->
        <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm" :class="showPreview ? '' : 'opacity-60'">
          <div class="print:hidden flex items-center justify-between">
            <div class="text-sm font-semibold text-gray-900">Preview Surat</div>
            <div class="text-xs text-gray-500">Format siap cetak</div>
          </div>

          <DomisiliTemplate :form="form" :tanggalIndo="tanggalIndo" />

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
        <DomisiliTemplate :form="form" :tanggalIndo="tanggalIndo" />
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
