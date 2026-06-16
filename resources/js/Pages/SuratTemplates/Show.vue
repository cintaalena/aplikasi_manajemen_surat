<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DomisiliTemplate from '@/Components/Surat/DomisiliTemplate.vue'
import KelahiranTemplate from '@/Components/Surat/KelahiranTemplate.vue'
import KematianTemplate from '@/Components/Surat/KematianTemplate.vue'
import PindahTemplate from '@/Components/Surat/PindahTemplate.vue'
import { computed, reactive, ref, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  slug:      String,
  lurahUser: { type: Object, default: null },
})
const isDomisili = computed(() => props.slug === 'keterangan-domisili')
const isKelahiran = computed(() => props.slug === 'keterangan-kelahiran')
const isKematian = computed(() => props.slug === 'keterangan-kematian')
const isPindah = computed(() => props.slug === 'keterangan-pindah')

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

const lurahDiTempat = ref(false)
const authUser = computed(() => usePage().props.auth?.user ?? {})
const isCurrentUserLurah = computed(() => authUser.value?.jabatan === 'lurah')
const effectiveSigner = computed(() => {
  if (lurahDiTempat.value && !isCurrentUserLurah.value && props.lurahUser) {
    return props.lurahUser
  }
  return null
})
const pendudukSuggestions = ref([])
const isSearchingPenduduk = ref(false)
const pendudukSearchError = ref('')
const showPendudukDropdown = ref(false)
const pendudukSelected = ref(false)
let pendudukSearchTimer = null

const previewContainerRef = ref(null)
const previewInnerRef = ref(null)
const previewScale = ref(1)
const previewScaledHeight = ref(0)
let previewResizeObserver = null

const recalcPreviewScale = () => {
  if (!previewContainerRef.value || !previewInnerRef.value) return
  const padding = 40
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
  ayah_id: null,
  ibu_id: null,
  kode_keluarga: '',
  nama_kepala_keluarga: '',
  dusun: '',
  alamat: '',
  alamatAsal: '',
  alamatAsalJalan: '',
  alamatAsalRt: '',
  alamatAsalRw: '',
  alamatAsalKelurahan: '',
  alamatAsalKecamatan: '',
  alamatAsalKota: '',
  alamatAsalProvinsi: '',
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
  desaTujuanId: '',
  kecamatanTujuan: '',
  kecamatanTujuanId: '',
  kabupatenTujuan: '',
  kabupatenTujuanId: '',
  provinsiTujuan: '',
  provinsiTujuanId: '',
  tanggalPindah: '',
  alasanPindah: '',
  pengikut: [],
})

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
    return await res.json()
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
  return await res.json()
}

const addPengikut = () => {
  form.pengikut.push({
    penduduk_id: null,
    nama: '',
    nik: '',
    tempatLahir: '',
    tanggalLahir: '',
    hubungan: '',
  })
  pengikutSuggestions.value.push([])
  showPengikutDropdown.value.push(false)
  pengikutSelected.value.push(false)
  pengikutSearchError.value.push('')
}

const removePengikut = (index) => {
  form.pengikut.splice(index, 1)
  pengikutSuggestions.value.splice(index, 1)
  showPengikutDropdown.value.splice(index, 1)
  pengikutSelected.value.splice(index, 1)
  pengikutSearchError.value.splice(index, 1)
}

const pengikutSuggestions    = ref([])
const showPengikutDropdown   = ref([])
const pengikutSelected       = ref([])
const pengikutSearchError    = ref([])
const pengikutSearchTimers   = {}

const applyPengikutFromDb = (index, p) => {
  const item = form.pengikut[index]
  if (!item) return
  const jkMap = { 'L': 'Laki-laki', 'P': 'Perempuan' }
  item.penduduk_id  = p.id ?? null
  item.nama         = p.nama ?? ''
  item.nik          = p.nik ?? ''
  item.tempatLahir  = p.tempat_lahir ?? ''
  item.tanggalLahir = p.tanggal_lahir ?? ''
  pengikutSelected.value[index]      = true
  showPengikutDropdown.value[index]  = false
  pengikutSuggestions.value[index]   = []
  pengikutSearchError.value[index]   = ''
}

const searchPengikutByName = async (index, keyword) => {
  const q = String(keyword || '').trim()
  if (q.length < 2) {
    pengikutSuggestions.value[index]  = []
    showPengikutDropdown.value[index] = false
    return
  }
  try {
    const res = await fetch(`/penduduk/search-by-name?q=${encodeURIComponent(q)}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'include',
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) throw new Error(data?.message || 'Gagal mencari data penduduk')
    pengikutSuggestions.value[index]  = Array.isArray(data) ? data : []
    showPengikutDropdown.value[index] = pengikutSuggestions.value[index].length > 0
    if (pengikutSuggestions.value[index].length === 0) pengikutSelected.value[index] = false
  } catch (e) {
    pengikutSuggestions.value[index]  = []
    showPengikutDropdown.value[index] = false
    pengikutSearchError.value[index]  = e.message || 'Gagal mencari data penduduk'
  }
}

const onPengikutNamaInput = (index, value) => {
  const item = form.pengikut[index]
  if (!item) return
  item.nama = value
  item.penduduk_id = null
  pengikutSelected.value[index] = false
  pengikutSearchError.value[index] = ''
  clearTimeout(pengikutSearchTimers[index])
  if (!value || String(value).trim().length < 2) {
    pengikutSuggestions.value[index]  = []
    showPengikutDropdown.value[index] = false
    return
  }
  pengikutSearchTimers[index] = setTimeout(() => searchPengikutByName(index, value), 300)
}

const closePengikutDropdown = (index) => {
  showPengikutDropdown.value[index] = false
}

const provinsiList   = ref([])
const kabupatenList  = ref([])
const kecamatanList  = ref([])
const desaList       = ref([])
const isLoadingProv  = ref(false)
const isLoadingKab   = ref(false)
const isLoadingKec   = ref(false)
const isLoadingDesa  = ref(false)

const fetchWilayah = async (url) => {
  const res = await fetch(url, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'include',
  })
  if (!res.ok) return []
  return await res.json()
}

const loadProvinsi = async () => {
  if (provinsiList.value.length > 0) return
  isLoadingProv.value = true
  provinsiList.value = await fetchWilayah('/api/wilayah/provinces')
  isLoadingProv.value = false
}

const onProvinsiChange = async (id) => {
  const prov = provinsiList.value.find(p => p.id === id)
  form.provinsiTujuanId  = id
  form.provinsiTujuan    = prov?.nama ?? ''
  form.kabupatenTujuanId = ''
  form.kabupatenTujuan   = ''
  form.kecamatanTujuanId = ''
  form.kecamatanTujuan   = ''
  form.desaTujuanId      = ''
  form.desaTujuan        = ''
  kabupatenList.value    = []
  kecamatanList.value    = []
  desaList.value         = []
  if (!id) return
  isLoadingKab.value = true
  kabupatenList.value = await fetchWilayah(`/api/wilayah/regencies/${id}`)
  isLoadingKab.value = false
}

const onKabupatenChange = async (id) => {
  const kab = kabupatenList.value.find(k => k.id === id)
  form.kabupatenTujuanId = id
  form.kabupatenTujuan   = kab?.nama ?? ''
  form.kecamatanTujuanId = ''
  form.kecamatanTujuan   = ''
  form.desaTujuanId      = ''
  form.desaTujuan        = ''
  kecamatanList.value    = []
  desaList.value         = []
  if (!id) return
  isLoadingKec.value = true
  kecamatanList.value = await fetchWilayah(`/api/wilayah/districts/${id}`)
  isLoadingKec.value = false
}

const onKecamatanChange = async (id) => {
  const kec = kecamatanList.value.find(k => k.id === id)
  form.kecamatanTujuanId = id
  form.kecamatanTujuan   = kec?.nama ?? ''
  form.desaTujuanId      = ''
  form.desaTujuan        = ''
  desaList.value         = []
  if (!id) return
  isLoadingDesa.value = true
  desaList.value = await fetchWilayah(`/api/wilayah/villages/${id}`)
  isLoadingDesa.value = false
}

const onDesaChange = (id) => {
  const desa = desaList.value.find(d => d.id === id)
  form.desaTujuanId = id
  form.desaTujuan   = desa?.nama ?? ''
}

const indexGroups = ref([])
const selectedGroupKey = ref('')
const selectedIndexCode = ref('')
const isLoadingIndexes = ref(true)
const indexLoadError = ref(null)

const loadIndexGroups = async () => {
  isLoadingIndexes.value = true
  indexLoadError.value = null
  try {
    console.log('ðŸ”„ Loading index groups from API...')
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

const pendudukLocked = computed(() => pendudukSelected.value)
const ayahLocked     = computed(() => form.ayah_id !== null)
const ibuLocked      = computed(() => form.ibu_id !== null)
const ortuAddrLocked = computed(() => form.ayah_id !== null)

const resetAyah = () => {
  form.namaAyah = ''
  form.ayah_id  = null
  ayahTidakTerdataChecked.value = false
  ayahSuggestions.value = []
  showAyahDropdown.value = false
}

const resetIbu = () => {
  form.namaIbu = ''
  form.ibu_id  = null
  ibuSuggestions.value  = []
  showIbuDropdown.value = false
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

const ayahSuggestions = ref([])
const showAyahDropdown = ref(false)
const isSearchingAyah = ref(false)
let ayahSearchTimer = null

const ayahTidakTerdataChecked = ref(false)
const showAyahTidakTerdataNotice = computed(() =>
  String(form.namaAyah || '').trim().length >= 2 &&
  !form.ayah_id &&
  !isSearchingAyah.value
)

const ibuSuggestions = ref([])
const showIbuDropdown = ref(false)
const isSearchingIbu = ref(false)
let ibuSearchTimer = null

const searchOrtu = async (keyword, suggestions, showDropdown, isSearching) => {
  const q = String(keyword || '').trim()
  if (q.length < 2) {
    suggestions.value = []
    showDropdown.value = false
    return
  }
  isSearching.value = true
  try {
    const res = await fetch(`/penduduk/search-by-name?q=${encodeURIComponent(q)}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'include',
    })
    const data = await res.json().catch(() => null)
    suggestions.value = Array.isArray(data) ? data : []
    showDropdown.value = suggestions.value.length > 0
  } catch {
    suggestions.value = []
    showDropdown.value = false
  } finally {
    isSearching.value = false
  }
}

const onAyahInput = (value) => {
  form.namaAyah = value
  form.ayah_id = null
  ayahTidakTerdataChecked.value = false
  showAyahDropdown.value = false
  clearTimeout(ayahSearchTimer)
  if (!value || value.trim().length < 2) { ayahSuggestions.value = []; return }
  ayahSearchTimer = setTimeout(() => searchOrtu(value, ayahSuggestions, showAyahDropdown, isSearchingAyah), 300)
}

const applyAyah = async (p) => {
  form.namaAyah             = p.nama ?? ''
  form.ayah_id              = p.id ?? null
  form.kode_keluarga        = p.kode_keluarga ?? ''
  form.nama_kepala_keluarga = p.nama_kepala_keluarga ?? ''
  form.dusun                = p.dusun ?? ''
  form.pekerjaan            = p.pekerjaan ?? ''
  form.rt                   = p.rt ?? ''
  form.rw                   = p.rw ?? ''
  form.alamat               = p.alamat ?? ''
  form.kelurahan            = 'Fatubesi'
  form.kecamatan            = 'Kota Lama'
  showAyahDropdown.value    = false
  ayahSuggestions.value     = []

  const kode = p.kode_keluarga ?? ''
  if (kode) {
    try {
      const res = await fetch(`/penduduk/cari-istri?kode_keluarga=${encodeURIComponent(kode)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'include',
      })
      const istri = await res.json().catch(() => null)
      if (istri && istri.id) {
        form.namaIbu          = istri.nama ?? ''
        form.ibu_id           = istri.id ?? null
        showIbuDropdown.value = false
        ibuSuggestions.value  = []
      }
    } catch {}
  }
}

const onIbuInput = (value) => {
  form.namaIbu = value
  showIbuDropdown.value = false
  clearTimeout(ibuSearchTimer)
  if (!value || value.trim().length < 2) { ibuSuggestions.value = []; return }
  ibuSearchTimer = setTimeout(() => searchOrtu(value, ibuSuggestions, showIbuDropdown, isSearchingIbu), 300)
}

const applyIbu = (p) => {
  form.namaIbu          = p.nama ?? ''
  form.ibu_id           = p.id ?? null
  showIbuDropdown.value = false
  ibuSuggestions.value  = []

  if (!form.ayah_id && !ayahTidakTerdataChecked.value) {
    form.pekerjaan  = p.pekerjaan ?? ''
    form.rt         = p.rt ?? ''
    form.rw         = p.rw ?? ''
    form.alamat     = p.alamat ?? ''
    form.kelurahan  = 'Fatubesi'
    form.kecamatan  = 'Kota Lama'

    if (!form.kode_keluarga) {
      form.kode_keluarga        = p.kode_keluarga ?? ''
      form.nama_kepala_keluarga = p.nama_kepala_keluarga ?? ''
      form.dusun                = p.dusun ?? ''
    }
  }
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

  if (isPindah.value && form.pengikut && form.pengikut.length > 0) {
    for (let i = 0; i < form.pengikut.length; i++) {
      const item = form.pengikut[i]
      const namaItem = String(item.nama || '').trim()
      if (!namaItem) {
        throw new Error(`Nama Pengikut ${i + 1} wajib diisi`)
      }
      if (!item.penduduk_id || !pengikutSelected.value[i]) {
        throw new Error(`Pengikut ${i + 1} (${namaItem}) tidak terdaftar di database penduduk Kelurahan Fatubesi`)
      }
    }
  }

  return true
}

const filteredIndexItems = computed(() => {
  const g = indexGroups.value.find(x => x.key === selectedGroupKey.value)
  const items = g?.items ?? []
  console.log('Filtered items for', selectedGroupKey.value, ':', items)
  return items
})

watch(selectedGroupKey, (newKey) => {
  console.log('Category changed to:', newKey)
  if (!newKey) {
    selectedIndexCode.value = ''
    return
  }
  const g = indexGroups.value.find(x => x.key === newKey)
  selectedIndexCode.value = ''
  console.log('Index code reset, please select an index')
})

const lastCounterSnapshot = ref(null)

const setNoSuratFromCounter = (counter) => {
  if (!counter || !selectedIndexCode.value) {
    form.noSurat = ''
    return
  }
  const urut = (counter.count ?? 0) + 1
  const indexCode = selectedIndexCode.value
  form.noSurat = generateNoSurat({
    urut: String(urut),
    indexCode,
    monthRoman: counter.monthRoman,
    year: counter.year,
  })
}

watch(selectedIndexCode, (newCode) => {
  console.log('Index code changed to:', newCode)
  if (lastCounterSnapshot.value) {
    setNoSuratFromCounter(lastCounterSnapshot.value)
  }
})

onMounted(async () => {
  console.log('=== Initializing form for slug:', props.slug, '===')
  
  try {
    if (isDomisili.value) {
      form.judulSurat = 'Surat Keterangan Domisili'
    } else if (isKelahiran.value) {
      form.judulSurat = 'Surat Keterangan Kelahiran'
    } else if (isKematian.value) {
      form.judulSurat = 'Surat Keterangan Kematian'
    } else if (isPindah.value) {
      form.judulSurat = 'Surat Keterangan Pindah'
      loadProvinsi()
    }
    console.log('Letter title set to:', form.judulSurat)

    console.log('Loading index groups...')
    await loadIndexGroups()
    console.log('Index groups loaded. Waiting for user to select category and index.')

    await nextTick()

    console.log('Fetching counter...')
    const counter = await getCounter(props.slug)
    console.log('Counter fetched:', counter)
    lastCounterSnapshot.value = counter
    
    console.log('=== Initialization complete. Please select category and index. ===')
  } catch (e) {
    console.error('Error initializing letter form:', e)
  }
})

const showPrintConfirm = ref(false)
const printSheetRef = ref(null)

const handleAfterPrint = () => {
  printMode.value = false
  showPrintConfirm.value = true
}

onMounted(() => {
  window.addEventListener('afterprint', handleAfterPrint)
})
onBeforeUnmount(() => {
  window.removeEventListener('afterprint', handleAfterPrint)
  previewResizeObserver?.disconnect()
})

const jenisPendaftaranKelahiran = ref('')

const KELAHIRAN_DOCS_CONFIG = {
  suratKetLahir:      { label: 'Surat Keterangan Lahir dari RS/Bidan/Puskesmas',          wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  fotoKkKelahiran:    { label: 'Kartu Keluarga (KK)',                                     wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  fotoKtpAyahIbu:     { label: 'Fotocopy KTP Ayah dan Ibu',                               wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  fotoBukuNikah:      { label: 'Fotocopy Buku Nikah / Akta Perkawinan',                  wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  fotoKtp2Saksi:      { label: 'Fotocopy KTP 2 Orang Saksi',                             wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  suratPengantarRtRwLahir: { label: 'Surat Pengantar RT/RW',                             wajib: true,  kasus: ['normal_0_60', 'normal_lebih_60'] },
  sptjmDataKelahiran: { label: 'SPTJM Kebenaran Data Kelahiran',                         wajib: true,  kasus: ['normal_lebih_60'] },
  suratPernyataanBelumAkta: { label: 'Surat Pernyataan Belum Punya Akta',               wajib: true,  kasus: ['normal_lebih_60'] },
  fotoIjazahOrtu:     { label: 'Fotocopy Ijazah Orang Tua',                              wajib: true,  kasus: ['normal_lebih_60'] },
  suratKetLahirLN:    { label: 'Surat Keterangan Lahir dari RS/Bidan/Puskesmas',         wajib: true,  kasus: ['luar_nikah'] },
  fotoKkIbu:          { label: 'KK Asli dari Ibu',                                       wajib: true,  kasus: ['luar_nikah'] },
  fotoKtpAyahIbuLN:   { label: 'Fotocopy KTP Ayah dan Ibu',                              wajib: true,  kasus: ['luar_nikah'] },
  fotoKtp2SaksiLN:    { label: 'KTP 2 Orang Saksi',                                     wajib: true,  kasus: ['luar_nikah'] },
  suratPengantarRtRwLN: { label: 'Surat Pengantar RT/RW',                               wajib: true,  kasus: ['luar_nikah'] },
  sptjmPengakuanAnak: { label: 'SPTJM Pengakuan Anak dari Ayah Biologis (opsional, jika nama ayah ingin masuk akta)', wajib: false, kasus: ['luar_nikah'] },
}

const kelDokState = reactive(
  Object.fromEntries(Object.keys(KELAHIRAN_DOCS_CONFIG).map(k => [k, { id: null, url: null, isUploading: false, error: '' }]))
)

const activeKelahiranDocs = computed(() => {
  if (!jenisPendaftaranKelahiran.value) return []
  return Object.entries(KELAHIRAN_DOCS_CONFIG)
    .filter(([, cfg]) => cfg.kasus.includes(jenisPendaftaranKelahiran.value))
    .map(([key, cfg]) => ({ key, ...cfg }))
})

const handleKelDokUpload = async (key, event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    kelDokState[key].error = 'Ukuran file terlalu besar. Maksimal 5 MB.'
    return
  }
  const cfg = KELAHIRAN_DOCS_CONFIG[key]
  kelDokState[key].isUploading = true
  kelDokState[key].error = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_key', key)
    fd.append('doc_label', cfg.label)
    const res = await fetch('/surat/dokumen/upload', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
      credentials: 'include',
      body: fd,
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) {
      if (res.status === 419) throw new Error('Sesi Anda telah berakhir. Silakan muat ulang halaman lalu coba lagi.')
      throw new Error(data?.message ?? `Upload gagal (${res.status})`)
    }
    kelDokState[key].id  = data.id
    kelDokState[key].url = data.url
  } catch (e) {
    kelDokState[key].error = e.message ?? 'Gagal upload'
  } finally {
    kelDokState[key].isUploading = false
  }
}

const removeKelDok = async (key) => {
  const id = kelDokState[key].id
  if (id) {
    try {
      await fetch(`/surat/dokumen/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'include',
      })
    } catch {}
  }
  kelDokState[key].id  = null
  kelDokState[key].url = null
  kelDokState[key].error = ''
}

const validateOrtuKelahiranBeforePrint = () => {
  if (!isKelahiran.value) return true
  const hasAyah = form.ayah_id !== null
  const hasIbu  = form.ibu_id  !== null
  if (!hasAyah && !hasIbu) {
    throw new Error(
      'Data orang tua tidak ditemukan di database.\n\n' +
      'Minimal salah satu (ayah atau ibu) harus terdaftar sebagai penduduk Kelurahan Fatubesi.\n\n' +
      'Silakan ketik nama ayah atau ibu lalu pilih dari daftar yang muncul.'
    )
  }
  return true
}

const validateDokKelahiranBeforePrint = () => {
  if (!isKelahiran.value) return true
  if (!jenisPendaftaranKelahiran.value) {
    throw new Error('Silakan pilih studi kasus pendaftaran kelahiran terlebih dahulu.')
  }
  const missing = activeKelahiranDocs.value
    .filter(d => d.wajib && !kelDokState[d.key].id)
    .map(d => d.label)
  if (missing.length > 0) {
    throw new Error('Dokumen persyaratan belum lengkap:\n• ' + missing.join('\n• '))
  }
  return true
}

const getKelDokIds = () => Object.values(kelDokState).map(s => s.id).filter(Boolean)

const jenisSuratKematian = ref('')

const KEMATIAN_DOCS = [
  { key: 'suratPengantarRtRw',     label: 'Surat Pengantar RT/RW',                                       wajib: true  },
  { key: 'suratKetKematian',       label: '',           wajib: true  },
  { key: 'fotoKtpAlmarhum',        label: 'Fotocopy KTP Almarhum/Almarhumah',                            wajib: true  },
  { key: 'fotoKkAlmarhum',         label: 'Fotocopy Kartu Keluarga Almarhum/Almarhumah',                 wajib: true  },
  { key: 'fotoKtpPemohon',         label: 'Fotocopy KTP Pemohon (Pelapor)',                              wajib: true  },
  { key: 'suratPernyataanPelapor', label: 'Surat Pernyataan dari Pelapor (ditandatangani 2 saksi & RT)', wajib: true  },
]

const labelKetKematian = computed(() =>
  jenisSuratKematian.value === 'dokter'
    ? 'Surat Keterangan Kematian dari Dokter/Bidan/Puskesmas'
    : jenisSuratKematian.value === 'saksi'
      ? 'Surat Pernyataan dari 2 Orang Saksi'
      : 'Dokumen Keterangan Kematian'
)

const dokState = reactive(
  Object.fromEntries(KEMATIAN_DOCS.map(d => [d.key, { id: null, url: null, isUploading: false, error: '' }]))
)

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const uploadDokumen = async (key, file) => {
  const label = key === 'suratKetKematian' ? labelKetKematian.value : (KEMATIAN_DOCS.find(d => d.key === key)?.label ?? key)

  dokState[key].isUploading = true
  dokState[key].error = ''

  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_key', key)
    fd.append('doc_label', label)

    const res = await fetch('/surat/dokumen/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
      body: fd,
    })

    const data = await res.json().catch(() => null)
    if (!res.ok) {
      if (res.status === 419) {
        throw new Error('Sesi Anda telah berakhir. Silakan muat ulang halaman lalu coba lagi.')
      }
      throw new Error(data?.message ?? `Upload gagal (${res.status})`)
    }

    dokState[key].id  = data.id
    dokState[key].url = data.url
  } catch (e) {
    dokState[key].error = e.message ?? 'Gagal upload'
  } finally {
    dokState[key].isUploading = false
  }
}

const handleDokUpload = async (key, event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  if (file.size > 5 * 1024 * 1024) {
    dokState[key].error = 'Ukuran file terlalu besar. Maksimal 5 MB.'
    return
  }

  await uploadDokumen(key, file)
}

const removeDok = async (key) => {
  const id = dokState[key].id
  if (id) {
    try {
      await fetch(`/surat/dokumen/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'include',
      })
    } catch {}
  }
  dokState[key].id  = null
  dokState[key].url = null
  dokState[key].error = ''
  if (key === 'suratKetKematian') {
    jenisSuratKematian.value = ''
  }
}

const validateDokKematianBeforePrint = () => {
  if (!isKematian.value) return true

  if (!jenisSuratKematian.value) {
    throw new Error('Silakan pilih jenis dokumen keterangan kematian (dokter/bidan atau pernyataan saksi).')
  }

  const missing = []
  for (const d of KEMATIAN_DOCS) {
    if (d.wajib && !dokState[d.key].id) {
      const label = d.key === 'suratKetKematian' ? labelKetKematian.value : d.label
      missing.push(label)
    }
  }

  if (missing.length > 0) {
    throw new Error('Dokumen persyaratan belum lengkap:\n• ' + missing.join('\n• '))
  }
  return true
}

const getDokIds = () => KEMATIAN_DOCS.map(d => dokState[d.key].id).filter(Boolean)

const PINDAH_DOCS = [
  { key: 'suratPengantarRt',  label: 'Surat Pengantar dari RT',                                  wajib: true },
  { key: 'fotoKtpPindah',     label: 'Fotocopy KTP yang akan pindah',                            wajib: true },
  { key: 'fotoKkPindah',      label: 'Fotocopy Kartu Keluarga',                                  wajib: true },
  { key: 'suratKetPasFoto',   label: 'Surat keterangan yang sudah ditempel pas foto',             wajib: true },
  { key: 'pasFotoPindah',     label: 'Pas foto yang akan pindah',                                 wajib: true },
]

const pindahDokState = reactive(
  Object.fromEntries(PINDAH_DOCS.map(d => [d.key, { id: null, url: null, isUploading: false, error: '' }]))
)

const handlePindahDokUpload = async (key, event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    pindahDokState[key].error = 'Ukuran file terlalu besar. Maksimal 5 MB.'
    return
  }
  const label = PINDAH_DOCS.find(d => d.key === key)?.label ?? key
  pindahDokState[key].isUploading = true
  pindahDokState[key].error = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_key', key)
    fd.append('doc_label', label)
    const res = await fetch('/surat/dokumen/upload', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
      credentials: 'include',
      body: fd,
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) {
      if (res.status === 419) throw new Error('Sesi Anda telah berakhir. Silakan muat ulang halaman lalu coba lagi.')
      throw new Error(data?.message ?? `Upload gagal (${res.status})`)
    }
    pindahDokState[key].id  = data.id
    pindahDokState[key].url = data.url
  } catch (e) {
    pindahDokState[key].error = e.message ?? 'Gagal upload'
  } finally {
    pindahDokState[key].isUploading = false
  }
}

const removePindahDok = async (key) => {
  const id = pindahDokState[key].id
  if (id) {
    try {
      await fetch(`/surat/dokumen/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'include',
      })
    } catch {}
  }
  pindahDokState[key].id  = null
  pindahDokState[key].url = null
  pindahDokState[key].error = ''
}

const validateDokPindahBeforePrint = () => {
  if (!isPindah.value) return true
  const missing = PINDAH_DOCS.filter(d => d.wajib && !pindahDokState[d.key].id).map(d => d.label)
  if (missing.length > 0) {
    throw new Error('Dokumen persyaratan belum lengkap:\n• ' + missing.join('\n• '))
  }
  return true
}

const getPindahDokIds = () => PINDAH_DOCS.map(d => pindahDokState[d.key].id).filter(Boolean)

const DOMISILI_DOCS = [
  { key: 'suratPengantarRtRwDom', label: 'Surat Pengantar RT/RW', wajib: true },
  { key: 'fotoKtpDomisili',       label: 'Fotocopy KTP Pemohon',  wajib: true },
]

const domDokState = reactive(
  Object.fromEntries(DOMISILI_DOCS.map(d => [d.key, { id: null, url: null, isUploading: false, error: '' }]))
)

const handleDomDokUpload = async (key, event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    domDokState[key].error = 'Ukuran file terlalu besar. Maksimal 5 MB.'
    return
  }
  const label = DOMISILI_DOCS.find(d => d.key === key)?.label ?? key
  domDokState[key].isUploading = true
  domDokState[key].error = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_key', key)
    fd.append('doc_label', label)
    const res = await fetch('/surat/dokumen/upload', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
      credentials: 'include',
      body: fd,
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) {
      if (res.status === 419) throw new Error('Sesi Anda telah berakhir. Silakan muat ulang halaman lalu coba lagi.')
      throw new Error(data?.message ?? `Upload gagal (${res.status})`)
    }
    domDokState[key].id  = data.id
    domDokState[key].url = data.url
  } catch (e) {
    domDokState[key].error = e.message ?? 'Gagal upload'
  } finally {
    domDokState[key].isUploading = false
  }
}

const removeDomDok = async (key) => {
  const id = domDokState[key].id
  if (id) {
    try {
      await fetch(`/surat/dokumen/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'include',
      })
    } catch {}
  }
  domDokState[key].id  = null
  domDokState[key].url = null
  domDokState[key].error = ''
}

const validateDokDomisiliBeforePrint = () => {
  if (!isDomisili.value) return true
  const missing = DOMISILI_DOCS.filter(d => d.wajib && !domDokState[d.key].id).map(d => d.label)
  if (missing.length > 0) {
    throw new Error('Dokumen persyaratan belum lengkap:\n• ' + missing.join('\n• '))
  }
  return true
}

const getDomDokIds = () => DOMISILI_DOCS.map(d => domDokState[d.key].id).filter(Boolean)

const finalizeLetter = async (templateSlug) => {
  if (!selectedIndexCode.value) {
    throw new Error('Silakan pilih kategori dan nomor index terlebih dahulu!')
  }

  const body = {
    title: form.judulSurat,
    index_code: selectedIndexCode.value,
    payload: { ...form },
  }

  if (isDomisili.value) {
    body.doc_ids = getDomDokIds()
  } else if (isKematian.value) {
    body.doc_ids = getDokIds()
  } else if (isKelahiran.value) {
    body.doc_ids = getKelDokIds()
  } else if (isPindah.value) {
    body.doc_ids = getPindahDokIds()
  }

  const res = await fetch(`/surat/${templateSlug}/finalize`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    credentials: 'include',
    body: JSON.stringify(body),
  })

  if (!res.ok) {
    if (res.status === 409) {
      const errData = await res.json().catch(() => ({}))
      if (errData.duplicate) {
        const e = new Error(errData.message || 'Nomor surat sudah digunakan.')
        e.isDuplicate = true
        e.nextNoSurat = errData.nextNoSurat ?? null
        throw e
      }
    }
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
  return data
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

  try {
    validateOrtuKelahiranBeforePrint()
  } catch (e) {
    alert(e.message)
    return
  }

  try {
    validateDokDomisiliBeforePrint()
  } catch (e) {
    alert(e.message)
    return
  }

  try {
    validateDokKematianBeforePrint()
  } catch (e) {
    alert(e.message)
    return
  }

  try {
    validateDokKelahiranBeforePrint()
  } catch (e) {
    alert(e.message)
    return
  }

  try {
    validateDokPindahBeforePrint()
  } catch (e) {
    alert(e.message)
    return
  }

  showPreview.value = true
  printMode.value = true
  await nextTick()

  if (printSheetRef.value) {
    const imgs = Array.from(printSheetRef.value.querySelectorAll('img'))
    const pending = imgs.filter(img => !img.complete)
    if (pending.length > 0) {
      await Promise.all(
        pending.map(img => new Promise(resolve => {
          img.addEventListener('load',  resolve, { once: true })
          img.addEventListener('error', resolve, { once: true })
        }))
      )
    }
  }

  window.print()
}

const showSuccessModal = ref(false)
const savedLetter = ref(null)
const isFinalizing = ref(false)

const confirmFinalize = async (confirmed) => {
  showPrintConfirm.value = false

  if (!confirmed) {
    if (lastCounterSnapshot.value) setNoSuratFromCounter(lastCounterSnapshot.value)
    return
  }

  if (isFinalizing.value) return
  isFinalizing.value = true
  isPrinting.value = true
  try {
    await finalizeLetter(props.slug)
    router.visit('/dashboard')
  } catch (e) {
    console.error('finalize error:', e)
    if (e.isDuplicate) {
      const next = e.nextNoSurat ?? null
      const msg  = next
        ? `Nomor surat tersebut sudah digunakan oleh surat lain.\n\nNomor surat diubah otomatis ke: ${next}\n\nSilakan tekan tombol "Cetak" lagi untuk mencetak dengan nomor baru.`
        : (e.message || 'Nomor surat sudah digunakan.')
      alert(msg)
      if (next) {
        form.noSurat = next
        const urutMatch = next.match(/^(\d+)\//)
        if (urutMatch && lastCounterSnapshot.value) {
          lastCounterSnapshot.value = {
            ...lastCounterSnapshot.value,
            count: parseInt(urutMatch[1]) - 1,
          }
        }
      }
    } else {
      alert(e.message || 'Gagal menyimpan data surat ke arsip. Silakan coba lagi.')
    }
  } finally {
    isPrinting.value = false
    isFinalizing.value = false
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
            Isi form → <b>View</b> untuk preview → <b>Cetak</b> untuk arsip.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <label
            v-if="!isCurrentUserLurah && props.lurahUser"
            class="flex items-center gap-2 cursor-pointer select-none rounded-xl border border-gray-200 bg-white px-3 py-2"
          >
            <input
              type="checkbox"
              v-model="lurahDiTempat"
              class="h-4 w-4 rounded border-gray-400 accent-purple-600"
            />
            <span class="text-sm text-gray-700">
              Lurah ada di tempat?
              <span v-if="lurahDiTempat" class="text-xs text-purple-600 font-semibold">(TTD: {{ props.lurahUser?.name }})</span>
            </span>
          </label>

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
                   bg-gradient-to-r from-green-600 to-emerald-500
                   hover:from-green-700 hover:to-emerald-600 transition
                   disabled:opacity-50 disabled:cursor-not-allowed"
            @click.prevent.stop="printNow"
          >
            {{ isPrinting ? 'Memproses...' : 'Cetak' }}
          </button>
        </div>
      </div>

      

      <div class="grid gap-6 lg:grid-cols-[minmax(320px,420px)_1fr]" :class="showPreview ? '' : 'lg:grid-cols-2'">
        <div class="print:hidden rounded-2xl border border-purple-100 bg-white p-5 shadow-sm">
          <div class="text-sm font-semibold text-gray-900">Form Data</div>

          <div class="mt-4 grid gap-4 sm:grid-cols-2">
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

            <div>
              <label class="text-xs font-semibold text-gray-700">Tanggal Surat</label>
              <input
                type="date"
                v-model="form.tanggalSurat"
                class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
              />
            </div>

            <template v-if="isDomisili">

              <div
                v-if="!pendudukSelected"
                class="sm:col-span-2 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-amber-700">
                  <span class="font-semibold">Mulai dengan mengisi kolom Nama di bawah.</span>
                  NIK dan data lainnya akan terisi otomatis dari database penduduk setelah nama dipilih.
                </p>
              </div>
              <div
                v-if="pendudukLocked"
                class="sm:col-span-2 flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-xs text-blue-700">
                  <span class="font-semibold">Field diisi otomatis dari database dan tidak dapat diedit langsung.</span>
                  Untuk mengubah data, perbarui terlebih dahulu di menu
                  <a href="/penduduk" class="underline font-semibold hover:text-blue-900">Database Penduduk</a>.
                </p>
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-700">NIK</label>
                <input
                  v-model="form.nik"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Terisi otomatis setelah nama dipilih"
                />
              </div>

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="!pendudukLocked && onNamaInput($event.target.value)"
                  @focus="!pendudukLocked && searchPendudukByName(form.nama)"
                  autocomplete="off"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
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
                <div v-else-if="pendudukSelected" class="mt-1 flex items-center gap-2">
                  <p class="text-xs text-green-600">✓ Nama ditemukan di database penduduk Kelurahan Fatubesi</p>
                  <button type="button" @click="clearPendudukSelection()" class="text-xs text-gray-400 hover:text-red-500 underline whitespace-nowrap">Ganti</button>
                </div>
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
                  :disabled="!pendudukSelected || pendudukLocked"
                  class="mt-1 w-full rounded-xl border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed"
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
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  type="date"
                  v-model="form.tanggalLahir"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                />
              </div>

              <div class="sm:col-span-2">
                <p class="text-xs font-bold text-gray-700 mb-2">Alamat Asal (sesuai KTP)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3">
                  <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-gray-600">Nama Jalan / Nama Tempat</label>
                    <input
                      v-model="form.alamatAsalJalan"
                      type="text"
                      placeholder="Contoh: Jln. Alor"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">RT</label>
                    <input
                      v-model="form.alamatAsalRt"
                      type="text"
                      inputmode="numeric"
                      placeholder="001"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                      @input="form.alamatAsalRt = $event.target.value.replace(/\D/g, '')"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">RW</label>
                    <input
                      v-model="form.alamatAsalRw"
                      type="text"
                      inputmode="numeric"
                      placeholder="001"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                      @input="form.alamatAsalRw = $event.target.value.replace(/\D/g, '')"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">Kelurahan / Desa</label>
                    <input
                      v-model="form.alamatAsalKelurahan"
                      type="text"
                      placeholder="Contoh: Fatubesi"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">Kecamatan</label>
                    <input
                      v-model="form.alamatAsalKecamatan"
                      type="text"
                      placeholder="Contoh: Kota Lama"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">Kota / Kabupaten</label>
                    <input
                      v-model="form.alamatAsalKota"
                      type="text"
                      placeholder="Contoh: Kota Kupang"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs font-semibold text-gray-600">Provinsi</label>
                    <input
                      v-model="form.alamatAsalProvinsi"
                      type="text"
                      placeholder="Contoh: Nusa Tenggara Timur"
                      class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400 text-sm"
                    />
                  </div>
                </div>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat Domisili</label>
                <textarea
                  v-model="form.alamatDomisili"
                  rows="2"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                ></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RT</label>
                <input
                  v-model="form.rt"
                  type="text"
                  inputmode="numeric"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  @input="!pendudukLocked && (form.rt = $event.target.value.replace(/\D/g, ''))"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RW</label>
                <input
                  v-model="form.rw"
                  type="text"
                  inputmode="numeric"
                  :disabled="!pendudukSelected"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  @input="!pendudukLocked && (form.rw = $event.target.value.replace(/\D/g, ''))"
                />
              </div>

              <div class="sm:col-span-2 mt-1">
                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 space-y-4">
                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div>
                      <div class="text-sm font-semibold text-purple-800">Dokumen Persyaratan</div>
                      <p class="text-xs text-purple-700 mt-0.5">Semua dokumen <span class="font-semibold">Wajib</span> harus diupload sebelum surat dapat dicetak dan masuk arsip. Format: JPG, PNG, WEBP, atau PDF · maks 5 MB.</p>
                    </div>
                  </div>

                  <div
                    v-for="(doc, idx) in DOMISILI_DOCS"
                    :key="doc.key"
                    class="rounded-lg bg-white border border-purple-100 p-3"
                  >
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">{{ idx + 1 }}. {{ doc.label }}</span>
                      </div>
                      <div v-if="domDokState[doc.key].isUploading" class="text-xs text-purple-600 italic">Mengupload...</div>
                      <div v-else-if="!domDokState[doc.key].id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDomDokUpload(doc.key, $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDomDok(doc.key)" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="domDokState[doc.key].error" class="mt-1 text-xs text-red-600">{{ domDokState[doc.key].error }}</p>
                    <div v-if="domDokState[doc.key].url" class="mt-2">
                      <img v-if="!domDokState[doc.key].url.endsWith('.pdf')" :src="domDokState[doc.key].url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="domDokState[doc.key].url" target="_blank" class="text-xs text-purple-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                </div>
              </div>

            </template>

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

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama Ayah</label>
                <input
                  :value="form.namaAyah"
                  @input="!ayahLocked && onAyahInput($event.target.value)"
                  @focus="!ayahLocked && searchOrtu(form.namaAyah, ayahSuggestions, showAyahDropdown, isSearchingAyah)"
                  type="text"
                  autocomplete="off"
                  :readonly="ayahLocked"
                  :class="ayahLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Ketik nama ayah..."
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
                    @click="applyAyah(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} • RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>
                <div v-if="isSearchingAyah" class="mt-1 text-xs text-gray-500">Mencari...</div>
                <div v-else-if="ayahLocked" class="mt-1 flex items-center gap-2">
                  <span class="text-xs text-green-600">✓ Data ayah dari database</span>
                  <button type="button" @click="resetAyah()" class="text-xs text-gray-400 hover:text-red-500 underline whitespace-nowrap">Ganti</button>
                </div>

                <div
                  v-if="showAyahTidakTerdataNotice"
                  class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5"
                >
                  <label class="flex cursor-pointer items-start gap-2.5">
                    <input
                      type="checkbox"
                      v-model="ayahTidakTerdataChecked"
                      class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-amber-400 text-amber-600 accent-amber-600"
                    />
                    <span class="text-xs leading-snug text-amber-800">
                      <span class="font-semibold">Nama ayah tidak terdata di database.</span>
                      Centang jika ingin tetap menggunakan nama ayah ini dan isi data secara manual.
                    </span>
                  </label>
                </div>
              </div>

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama Ibu</label>
                <input
                  :value="form.namaIbu"
                  @input="!ibuLocked && onIbuInput($event.target.value)"
                  @focus="!ibuLocked && searchOrtu(form.namaIbu, ibuSuggestions, showIbuDropdown, isSearchingIbu)"
                  type="text"
                  autocomplete="off"
                  :readonly="ibuLocked"
                  :class="ibuLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Ketik nama ibu..."
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
                    @click="applyIbu(item)"
                  >
                    <div class="font-semibold text-gray-900">{{ item.nama }}</div>
                    <div class="text-xs text-gray-500">NIK: {{ item.nik }} • RT {{ item.rt }}/RW {{ item.rw }}</div>
                  </button>
                </div>
                <div v-if="isSearchingIbu" class="mt-1 text-xs text-gray-500">Mencari...</div>
                <div v-else-if="ibuLocked" class="mt-1 flex items-center gap-2">
                  <span class="text-xs text-green-600">✓ Data ibu dari database</span>
                  <button type="button" @click="resetIbu()" class="text-xs text-gray-400 hover:text-red-500 underline whitespace-nowrap">Ganti</button>
                </div>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  type="text"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan pekerjaan"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Jalan / Alamat</label>
                <input
                  v-model="form.alamat"
                  type="text"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Contoh: Jl. Alor No.1 A"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RT</label>
                <input
                  v-model="form.rt"
                  type="text"
                  inputmode="numeric"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="001"
                  @input="!ortuAddrLocked && (form.rt = $event.target.value.replace(/\D/g, ''))"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">RW</label>
                <input
                  v-model="form.rw"
                  type="text"
                  inputmode="numeric"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="002"
                  @input="!ortuAddrLocked && (form.rw = $event.target.value.replace(/\D/g, ''))"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kelurahan</label>
                <input
                  v-model="form.kelurahan"
                  type="text"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Fatubesi"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kecamatan</label>
                <input
                  v-model="form.kecamatan"
                  type="text"
                  :readonly="ortuAddrLocked"
                  :class="ortuAddrLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Kota Lama"
                />
              </div>

              <div class="sm:col-span-2 mt-1">
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 space-y-4">
                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div>
                      <div class="text-sm font-semibold text-green-800">Dokumen Persyaratan</div>
                      <p class="text-xs text-green-700 mt-0.5">Pilih studi kasus, lalu upload semua dokumen <span class="font-semibold">Wajib</span> sebelum surat dapat dicetak.</p>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-green-100 p-3 space-y-3">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Wajib</span>
                      <span class="text-xs font-medium text-gray-700">Pilih Studi Kasus Pendaftaran</span>
                    </div>
                    <div class="flex flex-col gap-2 pl-1">
                      <label class="flex items-start gap-2 cursor-pointer select-none">
                        <input type="radio" name="jenisPendaftaranKelahiran" value="normal_0_60" v-model="jenisPendaftaranKelahiran" class="accent-green-600 h-4 w-4 mt-0.5 flex-shrink-0" />
                        <span class="text-xs text-gray-700"><strong>Kasus 1:</strong> Bayi usia <strong>0–60 hari</strong> (didaftarkan tepat waktu, dalam pernikahan sah)</span>
                      </label>
                      <label class="flex items-start gap-2 cursor-pointer select-none">
                        <input type="radio" name="jenisPendaftaranKelahiran" value="normal_lebih_60" v-model="jenisPendaftaranKelahiran" class="accent-green-600 h-4 w-4 mt-0.5 flex-shrink-0" />
                        <span class="text-xs text-gray-700"><strong>Kasus 2:</strong> Bayi usia <strong>lebih dari 60 hari</strong> (terlambat mendaftar, dalam pernikahan sah)</span>
                      </label>
                      <label class="flex items-start gap-2 cursor-pointer select-none">
                        <input type="radio" name="jenisPendaftaranKelahiran" value="luar_nikah" v-model="jenisPendaftaranKelahiran" class="accent-green-600 h-4 w-4 mt-0.5 flex-shrink-0" />
                        <span class="text-xs text-gray-700"><strong>Kasus 3:</strong> Anak lahir <strong>di luar nikah</strong></span>
                      </label>
                    </div>
                    <p v-if="!jenisPendaftaranKelahiran" class="text-xs text-amber-700 font-medium pl-1">⚠ Pilih salah satu studi kasus di atas untuk melihat daftar dokumen yang diperlukan.</p>
                  </div>

                  <div v-if="jenisPendaftaranKelahiran" class="rounded-lg bg-white border border-green-100 p-3">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Dokumen yang diperlukan untuk kasus ini:</p>
                    <ul class="space-y-1 pl-1">
                      <li v-for="(doc, idx) in activeKelahiranDocs" :key="doc.key" class="flex items-center gap-2 text-xs text-gray-700">
                        <span :class="doc.wajib ? 'text-green-600' : 'text-gray-400'" class="flex-shrink-0">{{ doc.wajib ? '●' : '○' }}</span>
                        {{ idx + 1 }}. {{ doc.label }}
                        <span v-if="!doc.wajib" class="text-gray-400">(opsional)</span>
                        <span v-if="kelDokState[doc.key].id" class="ml-auto text-green-700 font-semibold">✓</span>
                      </li>
                    </ul>
                  </div>

                  <template v-if="jenisPendaftaranKelahiran">
                    <div
                      v-for="(doc, idx) in activeKelahiranDocs"
                      :key="doc.key"
                      class="rounded-lg bg-white border border-green-100 p-3 space-y-2"
                    >
                      <div class="flex items-start gap-2">
                        <span
                          :class="doc.wajib ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                          class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold flex-shrink-0 mt-0.5"
                        >{{ doc.wajib ? 'Wajib' : 'Opsional' }}</span>
                        <span class="text-xs font-medium text-gray-700 leading-snug">{{ idx + 1 }}. {{ doc.label }}</span>
                      </div>
                      <div v-if="kelDokState[doc.key].isUploading" class="rounded-lg border-2 border-dashed border-green-300 bg-green-50 py-2 text-center text-xs text-green-700 font-medium">
                        Mengupload...
                      </div>
                      <div v-else-if="!kelDokState[doc.key].id">
                        <label class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-green-400 bg-green-50 py-2.5 text-xs font-semibold text-green-700 hover:bg-green-100 transition">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                          Klik untuk Upload File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleKelDokUpload(doc.key, $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                        <span class="text-xs text-green-700 font-semibold">✓ File tersimpan</span>
                        <button type="button" @click="removeKelDok(doc.key)" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                      </div>
                      <p v-if="kelDokState[doc.key].error" class="text-xs text-red-600">{{ kelDokState[doc.key].error }}</p>
                      <div v-if="kelDokState[doc.key].url" class="mt-1">
                        <img v-if="!kelDokState[doc.key].url.endsWith('.pdf')" :src="kelDokState[doc.key].url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                        <a v-else :href="kelDokState[doc.key].url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                      </div>
                    </div>
                  </template>

                </div>
              </div>
            </template>

            <template v-else-if="isKematian">

              <div
                v-if="pendudukLocked"
                class="sm:col-span-2 flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-xs text-blue-700">
                  <span class="font-semibold">Field diisi otomatis dari database dan tidak dapat diedit langsung.</span>
                  Untuk mengubah data, perbarui terlebih dahulu di menu
                  <a href="/penduduk" class="underline font-semibold hover:text-blue-900">Database Penduduk</a>.
                </p>
              </div>

              <div class="sm:col-span-2 relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="!pendudukLocked && onNamaInput($event.target.value)"
                  @focus="!pendudukLocked && searchPendudukByName(form.nama)"
                  type="text"
                  autocomplete="off"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
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
                <div v-else-if="pendudukSelected" class="mt-1 flex items-center gap-2">
                  <p class="text-xs text-green-600">✓ Nama ditemukan di database penduduk Kelurahan Fatubesi</p>
                  <button type="button" @click="clearPendudukSelection()" class="text-xs text-gray-400 hover:text-red-500 underline whitespace-nowrap">Ganti</button>
                </div>
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
                  class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed"
                  placeholder="Otomatis dari data penduduk"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">NIK</label>
                <input
                  v-model="form.nik"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan NIK"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan tempat lahir"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  v-model="form.tanggalLahir"
                  type="date"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Agama</label>
                <input
                  v-model="form.agama"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan agama"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Alamat</label>
                <textarea
                  v-model="form.alamat"
                  rows="2"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
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

              <div class="sm:col-span-2 mt-1">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-4">
                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div>
                      <div class="text-sm font-semibold text-amber-800">Dokumen Persyaratan</div>
                      <p class="text-xs text-amber-700 mt-0.5">Semua dokumen <span class="font-semibold">Wajib</span> harus diupload sebelum surat dapat dicetak dan masuk arsip.</p>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">1. Surat Pengantar RT/RW</span>
                      </div>
                      <div v-if="dokState.suratPengantarRtRw.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                      <div v-else-if="!dokState.suratPengantarRtRw.id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('suratPengantarRtRw', $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDok('suratPengantarRtRw')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="dokState.suratPengantarRtRw.error" class="mt-1 text-xs text-red-600">{{ dokState.suratPengantarRtRw.error }}</p>
                    <div v-if="dokState.suratPengantarRtRw.url" class="mt-2">
                      <img v-if="!dokState.suratPengantarRtRw.url.endsWith('.pdf')" :src="dokState.suratPengantarRtRw.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="dokState.suratPengantarRtRw.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3 space-y-3">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Wajib</span>
                      <span class="text-xs font-medium text-gray-700">2. Keterangan Kematian</span>
                    </div>

                    <div v-if="!dokState.suratKetKematian.id" class="flex flex-col gap-2 pl-1">
                      <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                          type="radio"
                          name="jenisSuratKematian"
                          value="dokter"
                          v-model="jenisSuratKematian"
                          class="accent-amber-600 h-4 w-4"
                        />
                        <span class="text-xs text-gray-700">Surat Keterangan Kematian dari <strong>Dokter / Bidan / Puskesmas</strong></span>
                      </label>
                      <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                          type="radio"
                          name="jenisSuratKematian"
                          value="saksi"
                          v-model="jenisSuratKematian"
                          class="accent-amber-600 h-4 w-4"
                        />
                        <span class="text-xs text-gray-700">Surat Pernyataan dari <strong>2 Orang Saksi</strong> (pengganti surat dokter)</span>
                      </label>
                    </div>
                    <p v-if="!jenisSuratKematian && !dokState.suratKetKematian.id" class="text-xs text-amber-700 font-medium pl-1">⚠ Pilih salah satu di atas, lalu upload file.</p>

                    <div v-if="jenisSuratKematian || dokState.suratKetKematian.id" class="pl-2 border-l-2 border-amber-300">
                      <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span class="text-xs text-gray-600 italic">{{ labelKetKematian }}</span>
                        <div v-if="dokState.suratKetKematian.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                        <div v-else-if="!dokState.suratKetKematian.id" class="flex-shrink-0">
                          <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                            Pilih File
                            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('suratKetKematian', $event)" />
                          </label>
                        </div>
                        <div v-else class="flex items-center gap-2 flex-shrink-0">
                          <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                          <button type="button" @click="removeDok('suratKetKematian')" class="text-xs text-red-500 hover:text-red-700">Hapus / Ganti</button>
                        </div>
                      </div>
                      <p v-if="dokState.suratKetKematian.error" class="mt-1 text-xs text-red-600">{{ dokState.suratKetKematian.error }}</p>
                      <div v-if="dokState.suratKetKematian.url" class="mt-2">
                        <img v-if="!dokState.suratKetKematian.url.endsWith('.pdf')" :src="dokState.suratKetKematian.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                        <a v-else :href="dokState.suratKetKematian.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                      </div>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">3. Fotocopy KTP Almarhum/Almarhumah</span>
                      </div>
                      <div v-if="dokState.fotoKtpAlmarhum.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                      <div v-else-if="!dokState.fotoKtpAlmarhum.id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('fotoKtpAlmarhum', $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDok('fotoKtpAlmarhum')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="dokState.fotoKtpAlmarhum.error" class="mt-1 text-xs text-red-600">{{ dokState.fotoKtpAlmarhum.error }}</p>
                    <div v-if="dokState.fotoKtpAlmarhum.url" class="mt-2">
                      <img v-if="!dokState.fotoKtpAlmarhum.url.endsWith('.pdf')" :src="dokState.fotoKtpAlmarhum.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="dokState.fotoKtpAlmarhum.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">4. Fotocopy Kartu Keluarga Almarhum/Almarhumah</span>
                      </div>
                      <div v-if="dokState.fotoKkAlmarhum.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                      <div v-else-if="!dokState.fotoKkAlmarhum.id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('fotoKkAlmarhum', $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDok('fotoKkAlmarhum')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="dokState.fotoKkAlmarhum.error" class="mt-1 text-xs text-red-600">{{ dokState.fotoKkAlmarhum.error }}</p>
                    <div v-if="dokState.fotoKkAlmarhum.url" class="mt-2">
                      <img v-if="!dokState.fotoKkAlmarhum.url.endsWith('.pdf')" :src="dokState.fotoKkAlmarhum.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="dokState.fotoKkAlmarhum.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">5. Fotocopy KTP Pemohon (Pelapor)</span>
                      </div>
                      <div v-if="dokState.fotoKtpPemohon.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                      <div v-else-if="!dokState.fotoKtpPemohon.id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('fotoKtpPemohon', $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDok('fotoKtpPemohon')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="dokState.fotoKtpPemohon.error" class="mt-1 text-xs text-red-600">{{ dokState.fotoKtpPemohon.error }}</p>
                    <div v-if="dokState.fotoKtpPemohon.url" class="mt-2">
                      <img v-if="!dokState.fotoKtpPemohon.url.endsWith('.pdf')" :src="dokState.fotoKtpPemohon.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="dokState.fotoKtpPemohon.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                  <div class="rounded-lg bg-white border border-amber-100 p-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">6. Surat Pernyataan dari Pelapor (2 saksi &amp; RT)</span>
                      </div>
                      <div v-if="dokState.suratPernyataanPelapor.isUploading" class="text-xs text-amber-600 italic">Mengupload...</div>
                      <div v-else-if="!dokState.suratPernyataanPelapor.id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleDokUpload('suratPernyataanPelapor', $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removeDok('suratPernyataanPelapor')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Pernyataan bahwa almarhum benar-benar telah meninggal, ditandatangani oleh 2 saksi dan ketua RT.</p>
                    <p v-if="dokState.suratPernyataanPelapor.error" class="mt-1 text-xs text-red-600">{{ dokState.suratPernyataanPelapor.error }}</p>
                    <div v-if="dokState.suratPernyataanPelapor.url" class="mt-2">
                      <img v-if="!dokState.suratPernyataanPelapor.url.endsWith('.pdf')" :src="dokState.suratPernyataanPelapor.url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="dokState.suratPernyataanPelapor.url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                </div>
              </div>
            </template>

            <template v-else-if="isPindah">
              <div class="sm:col-span-2">
                <div class="space-y-4">

              <div
                v-if="pendudukLocked"
                class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-xs text-blue-700">
                  <span class="font-semibold">Field diisi otomatis dari database dan tidak dapat diedit langsung.</span>
                  Untuk mengubah data, perbarui terlebih dahulu di menu
                  <a href="/penduduk" class="underline font-semibold hover:text-blue-900">Database Penduduk</a>.
                </p>
              </div>

              <div class="relative">
                <label class="text-xs font-semibold text-gray-700">Nama</label>
                <input
                  :value="form.nama"
                  @input="!pendudukLocked && onNamaInput($event.target.value)"
                  @focus="!pendudukLocked && searchPendudukByName(form.nama)"
                  autocomplete="off"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
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
                      NIK: {{ item.nik }} Î“Ã‡Ã³ RT {{ item.rt }}/RW {{ item.rw }}
                    </div>
                  </button>
                </div>

               <p v-if="isSearchingPenduduk" class="mt-1 text-xs text-gray-500">Mencari data penduduk...</p>
              <p v-else-if="pendudukSearchError" class="mt-1 text-xs text-red-600">{{ pendudukSearchError }}</p>
              <div v-else-if="pendudukSelected" class="mt-1 flex items-center gap-2">
                <p class="text-xs text-green-600">✓ Nama ditemukan di database penduduk Kelurahan Fatubesi</p>
                <button type="button" @click="clearPendudukSelection()" class="text-xs text-gray-400 hover:text-red-500 underline whitespace-nowrap">Ganti</button>
              </div>
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
                  :disabled="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
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
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan NIK"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tempat Lahir</label>
                <input
                  v-model="form.tempatLahir"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan tempat lahir"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Tanggal Lahir</label>
                <input
                  v-model="form.tanggalLahir"
                  type="date"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Status Perkawinan</label>
                <input
                  v-model="form.statusPerkawinan"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Contoh: Kawin"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Kewarganegaraan</label>
                <input
                  v-model="form.kewarganegaraan"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Contoh: Indonesia"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Agama</label>
                <input
                  v-model="form.agama"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan agama"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Pekerjaan</label>
                <input
                  v-model="form.pekerjaan"
                  type="text"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan pekerjaan"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-gray-700">Alamat Asal</label>
                <textarea
                  v-model="form.alamatAsal"
                  rows="2"
                  :readonly="pendudukLocked"
                  :class="pendudukLocked ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'focus:border-purple-400 focus:ring-purple-400'"
                  class="mt-1 w-full rounded-xl border-gray-200"
                  placeholder="Masukkan alamat asal"
                ></textarea>
              </div>

              <div class="sm:col-span-2">
                <p class="text-xs font-bold text-purple-700 mb-2 mt-1">Alamat Tujuan Pindah</p>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Provinsi Tujuan</label>
                <select
                  :value="form.provinsiTujuanId"
                  @change="onProvinsiChange($event.target.value)"
                  :disabled="isLoadingProv"
                  class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  <option value="">{{ isLoadingProv ? 'Memuat provinsi...' : 'Pilih Provinsi' }}</option>
                  <option v-for="p in provinsiList" :key="p.id" :value="p.id">{{ p.nama }}</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Kabupaten/Kota Tujuan</label>
                <select
                  :value="form.kabupatenTujuanId"
                  @change="onKabupatenChange($event.target.value)"
                  :disabled="isLoadingKab || !form.provinsiTujuanId"
                  class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  <option value="">
                    {{ isLoadingKab ? 'Memuat...' : (!form.provinsiTujuanId ? 'Pilih provinsi dulu' : 'Pilih Kabupaten/Kota') }}
                  </option>
                  <option v-for="k in kabupatenList" :key="k.id" :value="k.id">{{ k.nama }}</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Kecamatan Tujuan</label>
                <select
                  :value="form.kecamatanTujuanId"
                  @change="onKecamatanChange($event.target.value)"
                  :disabled="isLoadingKec || !form.kabupatenTujuanId"
                  class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  <option value="">
                    {{ isLoadingKec ? 'Memuat...' : (!form.kabupatenTujuanId ? 'Pilih kabupaten dulu' : 'Pilih Kecamatan') }}
                  </option>
                  <option v-for="k in kecamatanList" :key="k.id" :value="k.id">{{ k.nama }}</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Desa/Kelurahan Tujuan</label>
                <select
                  :value="form.desaTujuanId"
                  @change="onDesaChange($event.target.value)"
                  :disabled="isLoadingDesa || !form.kecamatanTujuanId"
                  class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  <option value="">
                    {{ isLoadingDesa ? 'Memuat...' : (!form.kecamatanTujuanId ? 'Pilih kecamatan dulu' : 'Pilih Desa/Kelurahan') }}
                  </option>
                  <option v-for="d in desaList" :key="d.id" :value="d.id">{{ d.nama }}</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-gray-700">Detail Alamat Tujuan <span class="font-normal text-gray-400">(RT/RW, nama jalan, dll)</span></label>
                <textarea
                  v-model="form.alamatTujuan"
                  rows="2"
                  class="mt-1 w-full rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-400"
                  placeholder="Contoh: RT 005/RW 002, Jl. Soekarno No. 10"
                ></textarea>
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
                    <div class="relative">
                      <label class="text-xs font-semibold text-gray-700">
                        Nama
                        <span class="ml-1 text-xs font-normal text-gray-400">(wajib terdaftar di database)</span>
                      </label>
                      <div class="relative mt-1">
                        <input
                          :value="item.nama"
                          @input="onPengikutNamaInput(index, $event.target.value)"
                          @focus="!pengikutSelected[index] && searchPengikutByName(index, item.nama)"
                          @blur="() => setTimeout(() => closePengikutDropdown(index), 200)"
                          type="text"
                          autocomplete="off"
                          :class="[
                            'w-full rounded-xl border px-3 py-2 text-sm focus:outline-none focus:ring-2',
                            pengikutSelected[index]
                              ? 'border-green-400 bg-green-50 focus:border-green-400 focus:ring-green-300'
                              : 'border-gray-200 focus:border-purple-400 focus:ring-purple-400'
                          ]"
                          placeholder="Ketik nama pengikut..."
                        />
                        <span v-if="pengikutSelected[index]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-green-600 text-sm font-medium">✓ Terdaftar</span>
                      </div>
                      <ul
                        v-if="showPengikutDropdown[index] && pengikutSuggestions[index]?.length > 0"
                        class="absolute z-50 mt-1 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                        style="max-height:200px"
                      >
                        <li
                          v-for="p in pengikutSuggestions[index]"
                          :key="p.id"
                          @mousedown.prevent="applyPengikutFromDb(index, p)"
                          class="cursor-pointer px-4 py-2 text-sm hover:bg-purple-50"
                        >
                          <span class="font-medium">{{ p.nama }}</span>
                          <span class="ml-2 text-xs text-gray-400">{{ p.nik }}</span>
                        </li>
                      </ul>
                      <p
                        v-else-if="item.nama && item.nama.length >= 2 && !showPengikutDropdown[index] && pengikutSuggestions[index]?.length === 0 && !pengikutSelected[index]"
                        class="mt-1 text-xs text-red-600"
                      >
                        Nama tidak ditemukan di database penduduk
                      </p>
                      <p v-if="pengikutSearchError[index]" class="mt-1 text-xs text-red-600">{{ pengikutSearchError[index] }}</p>
                    </div>

                    <div>
                      <label class="text-xs font-semibold text-gray-700">NIK <span class="font-normal text-gray-400">(otomatis dari database)</span></label>
                      <input
                        :value="item.nik"
                        readonly
                        type="text"
                        class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed"
                        placeholder="Terisi otomatis saat nama dipilih"
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

              <div class="mt-1">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 space-y-4">
                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div>
                      <div class="text-sm font-semibold text-blue-800">Dokumen Persyaratan</div>
                      <p class="text-xs text-blue-700 mt-0.5">Semua dokumen <span class="font-semibold">Wajib</span> harus diupload sebelum surat dapat dicetak dan masuk arsip.</p>
                    </div>
                  </div>

                  <div
                    v-for="(doc, idx) in PINDAH_DOCS"
                    :key="doc.key"
                    class="rounded-lg bg-white border border-blue-100 p-3"
                  >
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 flex-shrink-0">Wajib</span>
                        <span class="text-xs font-medium text-gray-700">{{ idx + 1 }}. {{ doc.label }}</span>
                      </div>
                      <div v-if="pindahDokState[doc.key].isUploading" class="text-xs text-blue-600 italic">Mengupload...</div>
                      <div v-else-if="!pindahDokState[doc.key].id" class="flex-shrink-0">
                        <label class="cursor-pointer rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">
                          Pilih File
                          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handlePindahDokUpload(doc.key, $event)" />
                        </label>
                      </div>
                      <div v-else class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-green-700 font-semibold">✓ Tersimpan</span>
                        <button type="button" @click="removePindahDok(doc.key)" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                      </div>
                    </div>
                    <p v-if="pindahDokState[doc.key].error" class="mt-1 text-xs text-red-600">{{ pindahDokState[doc.key].error }}</p>
                    <div v-if="pindahDokState[doc.key].url" class="mt-2">
                      <img v-if="!pindahDokState[doc.key].url.endsWith('.pdf')" :src="pindahDokState[doc.key].url" alt="Preview" class="max-h-32 rounded-lg border border-gray-200 object-contain" />
                      <a v-else :href="pindahDokState[doc.key].url" target="_blank" class="text-xs text-blue-600 underline">Lihat PDF</a>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </template>
          </div>
        </div>
        
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
                :signer="effectiveSigner"
              />
              <KelahiranTemplate
                v-else-if="isKelahiran"
                :form="form"
                :tanggalIndo="tanggalIndo"
                :signer="effectiveSigner"
              />
              <KematianTemplate
                v-else-if="isKematian"
                :form="form"
                :tanggalIndo="tanggalIndo"
                :signer="effectiveSigner"
              />
              <PindahTemplate
                v-else-if="isPindah"
                :form="form"
                :tanggalIndo="tanggalIndo"
                :signer="effectiveSigner"
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

  <Teleport to="body">
    <div v-if="printMode || isCapturing" :class="printMode ? 'print-overlay' : 'capture-overlay'">
      <div ref="printSheetRef" class="print-sheet">
        <DomisiliTemplate v-if="isDomisili" :form="form" :tanggalIndo="tanggalIndo" :signer="effectiveSigner" />
        <KelahiranTemplate v-else-if="isKelahiran" :form="form" :tanggalIndo="tanggalIndo" :signer="effectiveSigner" />
        <KematianTemplate v-else-if="isKematian" :form="form" :tanggalIndo="tanggalIndo" :signer="effectiveSigner" />
        <PindahTemplate v-else-if="isPindah" :form="form" :tanggalIndo="tanggalIndo" :signer="effectiveSigner" />
      </div>
    </div>
  </Teleport>

  <Teleport to="body">
    <div v-if="showPrintConfirm" class="fixed inset-0 z-[999998] flex items-center justify-center bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-base font-semibold text-gray-900">Konfirmasi Cetak</h3>
        <p class="mt-2 text-sm text-gray-600">
          Apakah surat berhasil dicetak atau disimpan sebagai PDF?
          <br />
          <span class="text-xs text-gray-400 mt-1 block">Jika Ya, nomor surat akan dicatat dan salinan digital masuk ke arsip.</span>
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
            :disabled="isPrinting || isFinalizing"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 transition disabled:opacity-60"
            @click="confirmFinalize(true)"
          >
            {{ isFinalizing ? 'Menyimpan...' : 'Ya, Berhasil Dicetak' }}
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

.capture-overlay{
  position: fixed;
  left: -99999px;
  top: 0;
  visibility: hidden;
  pointer-events: none;
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
