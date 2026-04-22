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

// Hitung usia dari tanggal lahir hingga tanggal meninggal
const hitungUmurKematian = (tanggalLahir, tanggalMeninggal) => {
  if (!tanggalLahir || !tanggalMeninggal) return ''
  const lahir = new Date(tanggalLahir)
  const meninggal = new Date(tanggalMeninggal)
  if (isNaN(lahir) || isNaN(meninggal)) return ''
  let tahun = meninggal.getFullYear() - lahir.getFullYear()
  const bulanDiff = meninggal.getMonth() - lahir.getMonth()
  if (bulanDiff < 0 || (bulanDiff === 0 && meninggal.getDate() < lahir.getDate())) tahun--
  return tahun >= 0 ? `${tahun} Tahun` : ''
}

const showPreview = ref(false)
const printMode = ref(false)
const isPrinting = ref(false)
const pendudukSuggestions = ref([])
const isSearchingPenduduk = ref(false)
const pendudukSearchError = ref('')
const showPendudukDropdown = ref(false)
const pendudukSelected = ref(false)
let pendudukSearchTimer = null

// Kelahiran: pencarian ayah dan ibu dari database penduduk
const ayahSuggestions = ref([])
const isSearchingAyah = ref(false)
const ayahSelected = ref(false)
const showAyahDropdown = ref(false)
let ayahSearchTimer = null

const ibuSuggestions = ref([])
const isSearchingIbu = ref(false)
const ibuSelected = ref(false)
const showIbuDropdown = ref(false)
let ibuSearchTimer = null

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

  penduduk_id: '',
  nama: '',
  nik: '',
  tempatLahir: '',
  tanggalLahir: '',
  jenisKelamin: '',
  pekerjaan: '',
  agama: '',
  namaAyah: '',
  namaIbu: '',
  ayah_id: '',
  ibu_id: '',
  dusun: '',
  kode_keluarga: '',
  nama_kepala_keluarga: '',
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

// ===== Cascading Wilayah untuk tujuan pindah =====
const WIL_BASE = '/api/wilayah'

// Data 38 provinsi diembedded langsung agar tidak bergantung API eksternal
const PROVINSI_STATIC = [
  { id: '11', name: 'ACEH' },
  { id: '12', name: 'SUMATERA UTARA' },
  { id: '13', name: 'SUMATERA BARAT' },
  { id: '14', name: 'RIAU' },
  { id: '15', name: 'JAMBI' },
  { id: '16', name: 'SUMATERA SELATAN' },
  { id: '17', name: 'BENGKULU' },
  { id: '18', name: 'LAMPUNG' },
  { id: '19', name: 'KEPULAUAN BANGKA BELITUNG' },
  { id: '21', name: 'KEPULAUAN RIAU' },
  { id: '31', name: 'DKI JAKARTA' },
  { id: '32', name: 'JAWA BARAT' },
  { id: '33', name: 'JAWA TENGAH' },
  { id: '34', name: 'DI YOGYAKARTA' },
  { id: '35', name: 'JAWA TIMUR' },
  { id: '36', name: 'BANTEN' },
  { id: '51', name: 'BALI' },
  { id: '52', name: 'NUSA TENGGARA BARAT' },
  { id: '53', name: 'NUSA TENGGARA TIMUR' },
  { id: '61', name: 'KALIMANTAN BARAT' },
  { id: '62', name: 'KALIMANTAN TENGAH' },
  { id: '63', name: 'KALIMANTAN SELATAN' },
  { id: '64', name: 'KALIMANTAN TIMUR' },
  { id: '65', name: 'KALIMANTAN UTARA' },
  { id: '71', name: 'SULAWESI UTARA' },
  { id: '72', name: 'SULAWESI TENGAH' },
  { id: '73', name: 'SULAWESI SELATAN' },
  { id: '74', name: 'SULAWESI TENGGARA' },
  { id: '75', name: 'GORONTALO' },
  { id: '76', name: 'SULAWESI BARAT' },
  { id: '81', name: 'MALUKU' },
  { id: '82', name: 'MALUKU UTARA' },
  { id: '91', name: 'PAPUA BARAT' },
  { id: '92', name: 'PAPUA' },
  { id: '93', name: 'PAPUA SELATAN' },
  { id: '94', name: 'PAPUA TENGAH' },
  { id: '95', name: 'PAPUA PEGUNUNGAN' },
  { id: '96', name: 'PAPUA BARAT DAYA' },
]

const provinsiList   = ref(PROVINSI_STATIC)
const kabupatenList  = ref([])
const kecamatanList  = ref([])
const desaList       = ref([])

const selectedProvinsiId  = ref('')
const selectedKabupatenId = ref('')
const selectedKecamatanId = ref('')

const loadingKabupaten = ref(false)
const loadingKecamatan = ref(false)
const loadingDesa      = ref(false)
const wilayahApiError  = ref('')

const onProvinsiChange = async () => {
  kabupatenList.value = []
  kecamatanList.value = []
  desaList.value = []
  selectedKabupatenId.value = ''
  selectedKecamatanId.value = ''
  form.kabupatenTujuan = ''
  form.kecamatanTujuan = ''
  form.desaTujuan = ''
  wilayahApiError.value = ''
  if (!selectedProvinsiId.value) return
  const prov = provinsiList.value.find(p => p.id === selectedProvinsiId.value)
  form.provinsiTujuan = prov ? prov.name : ''
  loadingKabupaten.value = true
  try {
    const res = await fetch(`${WIL_BASE}/regencies/${selectedProvinsiId.value}`)
    if (!res.ok) throw new Error('Gagal memuat data')
    kabupatenList.value = await res.json()
  } catch (e) {
    kabupatenList.value = []
    wilayahApiError.value = 'Gagal memuat kabupaten/kota. Isi manual di bawah.'
  } finally {
    loadingKabupaten.value = false
  }
}

const onKabupatenChange = async () => {
  kecamatanList.value = []
  desaList.value = []
  selectedKecamatanId.value = ''
  form.kecamatanTujuan = ''
  form.desaTujuan = ''
  wilayahApiError.value = ''
  if (!selectedKabupatenId.value) return
  const kab = kabupatenList.value.find(k => String(k.id) === String(selectedKabupatenId.value))
  form.kabupatenTujuan = kab ? kab.nama : ''
  loadingKecamatan.value = true
  try {
    const res = await fetch(`${WIL_BASE}/districts/${selectedKabupatenId.value}`)
    if (!res.ok) throw new Error('Gagal memuat data')
    kecamatanList.value = await res.json()
  } catch (e) {
    kecamatanList.value = []
    wilayahApiError.value = 'Gagal memuat kecamatan. Isi manual di bawah.'
  } finally {
    loadingKecamatan.value = false
  }
}

const onKecamatanChange = async () => {
  desaList.value = []
  form.desaTujuan = ''
  wilayahApiError.value = ''
  if (!selectedKecamatanId.value) return
  const kec = kecamatanList.value.find(k => String(k.id) === String(selectedKecamatanId.value))
  form.kecamatanTujuan = kec ? kec.nama : ''
  loadingDesa.value = true
  try {
    const res = await fetch(`${WIL_BASE}/villages/${selectedKecamatanId.value}`)
    if (!res.ok) throw new Error('Gagal memuat data')
    desaList.value = await res.json()
  } catch (e) {
    desaList.value = []
    wilayahApiError.value = 'Gagal memuat desa/kelurahan. Isi manual di bawah.'
  } finally {
    loadingDesa.value = false
  }
}

const onDesaChange = () => {
  const desa = desaList.value.find(d => d.nama === form.desaTujuan)
  if (desa) form.desaTujuan = desa.nama
}

// Auto-isi umur saat tanggal meninggal atau tanggal lahir berubah
watch(
  () => [form.tanggalMeninggal, form.tanggalLahir],
  ([meninggal, lahir]) => {
    if (isKematian.value) {
      form.umur = hitungUmurKematian(lahir, meninggal)
    }
  }
)

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

const requiresPendudukValidation = computed(() =>
  isDomisili.value || isKematian.value || isPindah.value
)

const clearPendudukSelection = () => {
  form.penduduk_id = ''
  pendudukSelected.value = false
}

const applyPendudukToForm = (p) => {
  form.penduduk_id = p.id ?? ''
  form.nama = p.nama ?? ''
  form.nik = p.nik ?? ''
  const jkMap = { 'L': 'Laki-laki', 'P': 'Perempuan' }
  form.jenisKelamin = jkMap[p.jenis_kelamin] ?? p.jenis_kelamin ?? ''
  form.tempatLahir = p.tempat_lahir ?? ''
  form.tanggalLahir = p.tanggal_lahir ?? ''
  form.agama = p.agama ?? ''
  form.pekerjaan = p.pekerjaan ?? ''
  form.alamat = p.alamat ?? ''
  form.rt = p.rt ?? ''
  form.rw = p.rw ?? ''
  form.statusPerkawinan = p.status_perkawinan ?? ''
  form.kewarganegaraan = p.kewarganegaraan ?? 'Indonesia'

  if (isDomisili.value) {
    form.alamatAsal = p.alamat ?? ''
    form.alamatAsalJalan = p.alamat ?? ''
    form.alamatAsalRt = p.rt ?? ''
    form.alamatAsalRw = p.rw ?? ''
    form.alamatAsalKelurahan = ''
    form.alamatAsalKecamatan = ''
    form.alamatDomisili = p.alamat ?? ''
  }

  if (isPindah.value) {
    form.alamatAsal = p.alamat ?? ''
  }

  pendudukSelected.value = true
  showPendudukDropdown.value = false
  pendudukSuggestions.value = []
  pendudukSearchError.value = ''
}

const toTitleCase = (str) => {
  if (!str) return str
  return String(str).replace(/\S+/g, word => word.charAt(0).toUpperCase() + word.slice(1))
}

const searchPendudukByName = async (keyword) => {
  const q = String(keyword || '').trim()

  if (!requiresPendudukValidation.value) return

  if (q.length < 2) {
    pendudukSuggestions.value = []
    showPendudukDropdown.value = false
    pendudukSearchError.value = ''
    return
  }

  isSearchingPenduduk.value = true
  pendudukSearchError.value = ''

  try {
    const res = await fetch(`/penduduk/search-by-name?q=${encodeURIComponent(q)}`, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'include',
    })

    const data = await res.json().catch(() => null)

    if (!res.ok) {
      throw new Error(data?.message || 'Gagal mencari data penduduk')
    }

    pendudukSuggestions.value = Array.isArray(data) ? data : []
    showPendudukDropdown.value = pendudukSuggestions.value.length > 0

    if (pendudukSuggestions.value.length === 0) {
      pendudukSelected.value = false
    }
  } catch (e) {
    pendudukSuggestions.value = []
    showPendudukDropdown.value = false
    pendudukSelected.value = false
    pendudukSearchError.value = e.message || 'Gagal mencari data penduduk'
  } finally {
    isSearchingPenduduk.value = false
  }
}

const onNamaInput = (value) => {
  form.nama = value

  if (!requiresPendudukValidation.value) return

  form.penduduk_id = ''
  pendudukSelected.value = false
  pendudukSearchError.value = ''

  clearTimeout(pendudukSearchTimer)

  if (!value || String(value).trim().length < 2) {
    pendudukSuggestions.value = []
    showPendudukDropdown.value = false
    return
  }

  pendudukSearchTimer = setTimeout(() => {
    searchPendudukByName(value)
  }, 300)
}

const validatePendudukSelectionBeforePrint = () => {
  if (!requiresPendudukValidation.value) return true

  const typedName = String(form.nama || '').trim()

  if (!typedName) {
    throw new Error('nama wajib diisi')
  }

  if (!form.penduduk_id || !pendudukSelected.value) {
    throw new Error('nama ini tidak terdaftar di database penduduk kelurahan fatubesi')
  }

  return true
}

const searchOrangTuaByName = async (keyword) => {
  const q = String(keyword || '').trim()
  if (q.length < 2) return []
  try {
    const res = await fetch(`/penduduk/search-by-name?q=${encodeURIComponent(q)}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'include',
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) return []
    return Array.isArray(data) ? data : []
  } catch {
    return []
  }
}

const applyAyahToForm = (p) => {
  form.namaAyah = p.nama ?? ''
  form.ayah_id = p.id ?? ''
  ayahSelected.value = true
  showAyahDropdown.value = false
  ayahSuggestions.value = []
  form.kode_keluarga = p.kode_keluarga ?? ''
  form.nama_kepala_keluarga = p.nama_kepala_keluarga ?? p.nama ?? ''
  form.alamat = p.alamat ?? ''
  form.rt = p.rt ?? ''
  form.rw = p.rw ?? ''
  form.dusun = p.dusun ?? ''
  form.pekerjaan = p.pekerjaan ?? ''
}

const applyIbuToForm = (p) => {
  form.namaIbu = p.nama ?? ''
  form.ibu_id = p.id ?? ''
  ibuSelected.value = true
  showIbuDropdown.value = false
  ibuSuggestions.value = []
  // Auto-fill data keluarga dari ibu hanya jika ayah belum dipilih
  if (!ayahSelected.value) {
    form.kode_keluarga = p.kode_keluarga ?? ''
    form.nama_kepala_keluarga = p.nama_kepala_keluarga ?? p.nama ?? ''
    form.alamat = p.alamat ?? ''
    form.rt = p.rt ?? ''
    form.rw = p.rw ?? ''
    form.dusun = p.dusun ?? ''
  }
}

const onNamaAyahInput = (value) => {
  form.namaAyah = value
  form.ayah_id = ''
  ayahSelected.value = false
  clearTimeout(ayahSearchTimer)
  if (!value || String(value).trim().length < 2) {
    ayahSuggestions.value = []
    showAyahDropdown.value = false
    return
  }
  ayahSearchTimer = setTimeout(async () => {
    isSearchingAyah.value = true
    ayahSuggestions.value = await searchOrangTuaByName(value)
    showAyahDropdown.value = ayahSuggestions.value.length > 0
    isSearchingAyah.value = false
  }, 300)
}

const onNamaIbuInput = (value) => {
  form.namaIbu = value
  form.ibu_id = ''
  ibuSelected.value = false
  clearTimeout(ibuSearchTimer)
  if (!value || String(value).trim().length < 2) {
    ibuSuggestions.value = []
    showIbuDropdown.value = false
    return
  }
  ibuSearchTimer = setTimeout(async () => {
    isSearchingIbu.value = true
    ibuSuggestions.value = await searchOrangTuaByName(value)
    showIbuDropdown.value = ibuSuggestions.value.length > 0
    isSearchingIbu.value = false
  }, 300)
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

  try {
    validatePendudukSelectionBeforePrint()
  } catch (e) {
    alert(e.message || 'nama ini tidak terdaftar di database penduduk kelurahan fatubesi')
    return
  }

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

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="onNamaInput($event.target.value)"
                  @focus="searchPendudukByName(form.nama)"
                  autocomplete="off"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Ketik nama penduduk..."
                />

                <div
                  v-if="showPendudukDropdown && pendudukSuggestions.length > 0"
                  class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in pendudukSuggestions"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-purple-50"
                    @click="applyPendudukToForm(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} • RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>

                <p v-if="isSearchingPenduduk" class="mt-1 text-xs text-gray-500">Mencari data penduduk...</p>
                <p v-else-if="pendudukSearchError" class="mt-1 text-xs text-red-600">{{ pendudukSearchError }}</p>
                <p v-else-if="pendudukSelected" class="mt-1 text-xs text-green-600">
                  ✓ Nama ditemukan di database penduduk Kelurahan Fatubesi
                </p>
                <p
                  v-else-if="form.nama && form.nama.length >= 2 && !showPendudukDropdown && pendudukSuggestions.length === 0"
                  class="mt-1 text-xs text-red-600"
                >
                  ✗ Nama belum ditemukan di database penduduk Kelurahan Fatubesi
                </p>
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

              <!-- Nama Ayah dengan autocomplete -->
              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama Ayah</label>
                <input
                  :value="form.namaAyah"
                  @input="onNamaAyahInput($event.target.value)"
                  type="text"
                  autocomplete="off"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Ketik nama ayah (cari dari database)"
                />
                <div
                  v-if="showAyahDropdown && ayahSuggestions.length > 0"
                  class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in ayahSuggestions"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-purple-50"
                    @click="applyAyahToForm(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} &bull; RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>
                <p v-if="isSearchingAyah" class="mt-1 text-xs text-gray-500">Mencari...</p>
                <p v-else-if="ayahSelected" class="mt-1 text-xs text-green-600">&#x2713; Ayah ditemukan di database penduduk</p>
                <p v-else class="mt-1 text-xs text-gray-400">Jika hanya ada ibu, kosongkan kolom ini</p>
              </div>

              <!-- Nama Ibu dengan autocomplete -->
              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama Ibu</label>
                <input
                  :value="form.namaIbu"
                  @input="onNamaIbuInput($event.target.value)"
                  type="text"
                  autocomplete="off"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Ketik nama ibu (cari dari database)"
                />
                <div
                  v-if="showIbuDropdown && ibuSuggestions.length > 0"
                  class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in ibuSuggestions"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-purple-50"
                    @click="applyIbuToForm(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} &bull; RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>
                <p v-if="isSearchingIbu" class="mt-1 text-xs text-gray-500">Mencari...</p>
                <p v-else-if="ibuSelected" class="mt-1 text-xs text-green-600">&#x2713; Ibu ditemukan di database penduduk</p>
                <p v-else class="mt-1 text-xs text-gray-400">Jika hanya ada ayah, kosongkan kolom ini</p>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Masukkan pekerjaan (otomatis dari data ayah)"
                />
                <p v-if="ayahSelected && form.pekerjaan" class="mt-1 text-xs text-green-600">&#x2713; Diisi otomatis dari data ayah &mdash; dapat diubah jika perlu</p>
                <p v-else-if="!ayahSelected" class="mt-1 text-xs text-gray-400">Isi manual jika ayah tidak ada di database</p>
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
                  @blur="form.kecamatan = toTitleCase(form.kecamatan)"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Dusun</label>
                <input
                  v-model="form.dusun"
                  type="text"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Otomatis dari data orang tua"
                />
              </div>

              <div v-if="form.kode_keluarga" class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">No. KK (otomatis dari orang tua)</label>
                <input
                  :value="form.kode_keluarga"
                  type="text"
                  readonly
                  class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 text-gray-600"
                />
              </div>
            </template>

            <!-- Kematian Fields -->
            <template v-else-if="isKematian">
              <div class="sm:col-span-2 relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="onNamaInput($event.target.value)"
                  @focus="searchPendudukByName(form.nama)"
                  type="text"
                  autocomplete="off"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Ketik nama penduduk..."
                />

                <div
                  v-if="showPendudukDropdown && pendudukSuggestions.length > 0"
                  class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in pendudukSuggestions"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-purple-50"
                    @click="applyPendudukToForm(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} • RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>

                <p v-if="isSearchingPenduduk" class="mt-1 text-xs text-gray-500">Mencari data penduduk...</p>
                <p v-else-if="pendudukSearchError" class="mt-1 text-xs text-red-600">{{ pendudukSearchError }}</p>
                <p v-else-if="pendudukSelected" class="mt-1 text-xs text-green-600">
                  ✓ Nama ditemukan di database penduduk Kelurahan Fatubesi
                </p>
                <p
                  v-else-if="form.nama && form.nama.length >= 2 && !showPendudukDropdown && pendudukSuggestions.length === 0"
                  class="mt-1 text-xs text-red-600"
                >
                  ✗ Nama belum ditemukan di database penduduk Kelurahan Fatubesi
                </p>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Jenis Kelamin</label>
                <input
                  :value="form.jenisKelamin || '-'"
                  type="text"
                  readonly
                  class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 text-gray-600"
                  placeholder="Otomatis dari data penduduk"
                />
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

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="onNamaInput($event.target.value)"
                  @focus="searchPendudukByName(form.nama)"
                  autocomplete="off"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Ketik nama penduduk..."
                />

                <div
                  v-if="showPendudukDropdown && pendudukSuggestions.length > 0"
                  class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                  <button
                    v-for="item in pendudukSuggestions"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-purple-50"
                    @click="applyPendudukToForm(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">
                      NIK: {{ item.nik }} ΓÇó RT {{ item.rt }}/RW {{ item.rw }}
                    </div>
                  </button>
                </div>

               <p v-if="isSearchingPenduduk" class="mt-1 text-xs text-gray-500">Mencari data penduduk...</p>
              <p v-else-if="pendudukSearchError" class="mt-1 text-xs text-red-600">{{ pendudukSearchError }}</p>
              <p v-else-if="pendudukSelected" class="mt-1 text-xs text-green-600">
                Γ£ô Nama ditemukan di database penduduk Kelurahan Fatubesi
              </p>
              <p
                v-else-if="form.nama && form.nama.length >= 2 && !showPendudukDropdown && pendudukSuggestions.length === 0"
                class="mt-1 text-xs text-red-600"
              >
                Γ£ù Nama belum ditemukan di database penduduk Kelurahan Fatubesi
              </p>
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

              <!-- ===== TUJUAN PINDAH (cascading wilayah) ===== -->
              <div class="rounded-xl border border-purple-100 bg-purple-50/40 p-3 space-y-3">
                <p class="text-xs font-bold text-purple-700 uppercase tracking-wide">Lokasi Tujuan Pindah</p>

                <!-- Provinsi -->
                <div>
                  <label class="text-xs font-semibold text-gray-700">Provinsi</label>
                  <select
                    v-model="selectedProvinsiId"
                    @change="onProvinsiChange"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  >
                    <option value="">-- Pilih Provinsi --</option>
                    <option v-for="p in provinsiList" :key="p.id" :value="p.id">{{ toTitleCase(p.name) }}</option>
                  </select>
                </div>

                <!-- Kabupaten/Kota -->
                <div>
                  <label class="text-xs font-semibold text-gray-700">Kabupaten / Kota</label>
                  <select
                    v-if="kabupatenList.length > 0 || loadingKabupaten"
                    v-model="selectedKabupatenId"
                    @change="onKabupatenChange"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :disabled="loadingKabupaten"
                  >
                    <option value="">{{ loadingKabupaten ? 'Memuat...' : '-- Pilih Kabupaten/Kota --' }}</option>
                    <option v-for="k in kabupatenList" :key="k.id" :value="String(k.id)">{{ toTitleCase(k.nama) }}</option>
                  </select>
                  <input
                    v-else
                    v-model="form.kabupatenTujuan"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :placeholder="selectedProvinsiId ? 'Ketik nama kabupaten/kota' : 'Pilih provinsi dahulu'"
                    :disabled="!selectedProvinsiId"
                  />
                </div>

                <!-- Kecamatan -->
                <div>
                  <label class="text-xs font-semibold text-gray-700">Kecamatan</label>
                  <select
                    v-if="kecamatanList.length > 0 || loadingKecamatan"
                    v-model="selectedKecamatanId"
                    @change="onKecamatanChange"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :disabled="loadingKecamatan"
                  >
                    <option value="">{{ loadingKecamatan ? 'Memuat...' : '-- Pilih Kecamatan --' }}</option>
                    <option v-for="k in kecamatanList" :key="k.id" :value="String(k.id)">{{ toTitleCase(k.nama) }}</option>
                  </select>
                  <input
                    v-else
                    v-model="form.kecamatanTujuan"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :placeholder="selectedKabupatenId || form.kabupatenTujuan ? 'Ketik nama kecamatan' : 'Pilih kabupaten/kota dahulu'"
                    :disabled="!selectedKabupatenId && !form.kabupatenTujuan"
                  />
                </div>

                <!-- Desa/Kelurahan -->
                <div>
                  <label class="text-xs font-semibold text-gray-700">Desa / Kelurahan</label>
                  <select
                    v-if="desaList.length > 0 || loadingDesa"
                    v-model="form.desaTujuan"
                    @change="onDesaChange"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :disabled="loadingDesa"
                  >
                    <option value="">{{ loadingDesa ? 'Memuat...' : '-- Pilih Desa/Kelurahan --' }}</option>
                    <option v-for="d in desaList" :key="d.id" :value="d.nama">{{ toTitleCase(d.nama) }}</option>
                  </select>
                  <input
                    v-else
                    v-model="form.desaTujuan"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    :placeholder="selectedKecamatanId || form.kecamatanTujuan ? 'Ketik nama desa/kelurahan' : 'Pilih kecamatan dahulu'"
                    :disabled="!selectedKecamatanId && !form.kecamatanTujuan"
                  />
                </div>

                <!-- Notifikasi error API -->
                <p v-if="wilayahApiError" class="text-xs text-orange-500">&#9888; {{ wilayahApiError }}</p>

                <!-- Alamat detail (jalan + RT/RW) -->
                <div>
                  <label class="text-xs font-semibold text-gray-700">Alamat Tujuan <span class="font-normal text-gray-400">(Jalan, RT/RW — opsional)</span></label>
                  <input
                    v-model="form.alamatTujuan"
                    type="text"
                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                    placeholder="Contoh: Jl. Merdeka No.5, RT.01/RW.03"
                  />
                </div>
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

  <!-- Γ£à PRINT OVERLAY: keluar dari AppLayout -->
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

  <!-- Γ£à KONFIRMASI setelah print dialog ditutup -->
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
