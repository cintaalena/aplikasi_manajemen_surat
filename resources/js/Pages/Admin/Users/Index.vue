<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    users: { type: Array, default: () => [] },
})

// ── Flash helpers ──────────────────────────────────────────────────────────
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const flashSuccess        = computed(() => page.props.flash?.success)
const flashCredential         = computed(() => page.props.flash?.credential)
const flashCredentialName     = computed(() => page.props.flash?.credential_name)
const flashCredentialRole     = computed(() => page.props.flash?.credential_role)
const flashCredentialPassword = computed(() => page.props.flash?.credential_password)

// ── JABATAN options ────────────────────────────────────────────────────────
const jabatanOptions = [
    { value: 'lurah',                             label: 'Lurah' },
    { value: 'sekretaris',                        label: 'Sekretaris' },
    { value: 'kasie_pelayanan_masyarakat',        label: 'Kasie Pelayanan Masyarakat' },
    { value: 'kasie_pem_trantib_umum',            label: 'Kasie Pem & Trantib Umum' },
    { value: 'pengelola_pemberdayaan_masyarakat', label: 'Pengelola Pemberdayaan Masyarakat' },
    { value: 'pengadministrasi_perkantoran',      label: 'Pengadministrasi Perkantoran' },
    { value: 'penata_layanan_operasional',        label: 'Penata Layanan Operasional' },
]

const roleLabel = (role) => ({ lurah: 'Lurah', staff: 'Staff' }[role] ?? role)
const jabatanLabel = (val) => jabatanOptions.find(j => j.value === val)?.label ?? val

// ── Modal state ────────────────────────────────────────────────────────────
const showCreate = ref(false)
const showEdit   = ref(false)
const showReset  = ref(false)
const editTarget = ref(null)
const resetTarget= ref(null)

// ── Forms ──────────────────────────────────────────────────────────────────
const createForm = useForm({
    name: '', nip: '', jabatan: '', role: 'staff', password: '', password_confirmation: '',
})

const editForm = useForm({
    name: '', nip: '', jabatan: '', role: 'staff',
})

const resetForm = useForm({ password: '' })

// ── Actions ────────────────────────────────────────────────────────────────
const submitCreate = () => {
    createForm.post(route('admin.pengguna.store'), {
        onSuccess: () => { showCreate.value = false; createForm.reset() },
    })
}

const openEdit = (user) => {
    editTarget.value = user
    editForm.name    = user.name
    editForm.nip     = user.nip ?? ''
    editForm.jabatan = user.jabatan
    editForm.role    = user.role
    showEdit.value   = true
}

const submitEdit = () => {
    editForm.put(route('admin.pengguna.update', editTarget.value.id), {
        onSuccess: () => { showEdit.value = false; editTarget.value = null },
    })
}

const toggleActive = (user) => {
    router.patch(route('admin.pengguna.toggle-active', user.id), {}, {
        preserveScroll: true,
    })
}

const openReset = (user) => {
    resetTarget.value = user
    resetForm.reset()
    showReset.value   = true
}

const submitReset = () => {
    resetForm.patch(route('admin.pengguna.reset-password', resetTarget.value.id), {
        onSuccess: () => { showReset.value = false; resetTarget.value = null },
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Pengguna" />

        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900">Manajemen Pengguna</h1>
                <button
                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 transition"
                    @click="showCreate = true"
                >
                    + Tambah Pengguna
                </button>
            </div>

            <!-- Flash success + credential baru -->
            <div
                v-if="flashCredential"
                class="rounded-xl border-2 border-green-400 bg-green-50 px-5 py-4 text-sm text-green-900"
            >
                <div class="font-bold text-base mb-1">✅ Pengguna berhasil ditambahkan!</div>
                <div class="mb-2">Berikan informasi login berikut kepada <strong>{{ flashCredentialName }}</strong>:</div>
                <table class="text-sm">
                    <tr>
                        <td class="pr-4 text-gray-600">Nama (login)</td>
                        <td class="font-mono font-semibold">{{ flashCredentialName }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 text-gray-600">Credential Code</td>
                        <td class="font-mono font-bold text-green-700 text-base">{{ flashCredential }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 text-gray-600">Password</td>
                        <td class="font-mono font-bold text-red-700 text-base">{{ flashCredentialPassword }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 text-gray-600">Role</td>
                        <td>{{ flashCredentialRole === 'lurah' ? 'Lurah (A-001)' : 'Staff (B-001)' }}</td>
                    </tr>
                </table>
                <div class="mt-2 text-xs text-green-700 font-semibold">&#x26A0; Catat informasi ini sekarang. Halaman ini tidak akan menampilkannya lagi.</div>
                <div class="mt-1 text-xs text-red-700 font-semibold">&#x1F512; Jangan bagikan password melalui media yang tidak aman.</div>
            </div>
            <div
                v-else-if="flashSuccess"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ flashSuccess }}
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">NIP</th>
                            <th class="px-4 py-3 text-left">Jabatan</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ user.nip ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ jabatanLabel(user.jabatan) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase"
                                    :class="user.role === 'lurah'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-stone-100 text-stone-700'"
                                >{{ roleLabel(user.role) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="user.is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-red-100 text-red-600'"
                                >{{ user.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        class="rounded px-2.5 py-1 text-xs font-medium text-white bg-stone-500 hover:bg-stone-700 transition"
                                        @click="openEdit(user)"
                                    >Edit</button>
                                    <button
                                        class="rounded px-2.5 py-1 text-xs font-medium text-white transition"
                                        :class="user.is_active ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600'"
                                        @click="toggleActive(user)"
                                    >{{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    <button
                                        class="rounded px-2.5 py-1 text-xs font-medium text-white bg-gray-500 hover:bg-gray-600 transition"
                                        @click="openReset(user)"
                                    >Reset PW</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="6" class="py-8 text-center text-sm text-gray-400">Belum ada pengguna.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Modal: Tambah Pengguna ──────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="border-b px-6 py-4">
                        <h2 class="text-base font-bold text-gray-900">Tambah Pengguna Baru</h2>
                    </div>
                    <form @submit.prevent="submitCreate" class="space-y-4 px-6 py-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input v-model="createForm.name" type="text" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP (opsional)</label>
                            <input v-model="createForm.nip" type="text"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <select v-model="createForm.jabatan" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="" disabled>-- Pilih Jabatan --</option>
                                <option v-for="j in jabatanOptions" :key="j.value" :value="j.value">{{ j.label }}</option>
                            </select>
                            <p v-if="createForm.errors.jabatan" class="mt-1 text-xs text-red-600">{{ createForm.errors.jabatan }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select v-model="createForm.role" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="staff">Staff</option>
                                <option value="lurah">Lurah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input v-model="createForm.password" type="password" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            <p class="mt-1 text-xs text-gray-500">Min. 6 karakter</p>
                            <p v-if="createForm.errors.password" class="mt-1 text-xs text-red-600">{{ createForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                            <input v-model="createForm.password_confirmation" type="password" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            <p v-if="createForm.password_confirmation && createForm.password !== createForm.password_confirmation" class="mt-1 text-xs text-red-600">Password tidak cocok</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showCreate = false"
                                class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                            <button type="submit" :disabled="createForm.processing"
                                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 transition disabled:opacity-50">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Modal: Edit Pengguna ────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="border-b px-6 py-4">
                        <h2 class="text-base font-bold text-gray-900">Edit Pengguna</h2>
                    </div>
                    <form @submit.prevent="submitEdit" class="space-y-4 px-6 py-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input v-model="editForm.name" type="text" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-600">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP (opsional)</label>
                            <input v-model="editForm.nip" type="text"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <select v-model="editForm.jabatan" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option v-for="j in jabatanOptions" :key="j.value" :value="j.value">{{ j.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select v-model="editForm.role" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="staff">Staff</option>
                                <option value="lurah">Lurah</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showEdit = false"
                                class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                            <button type="submit" :disabled="editForm.processing"
                                class="rounded-lg bg-stone-700 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 transition disabled:opacity-50">
                                Perbarui
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Modal: Reset Password ───────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showReset" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">
                    <div class="border-b px-6 py-4">
                        <h2 class="text-base font-bold text-gray-900">Reset Password</h2>
                        <p class="mt-1 text-xs text-gray-500">{{ resetTarget?.name }}</p>
                    </div>
                    <form @submit.prevent="submitReset" class="space-y-4 px-6 py-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                            <input v-model="resetForm.password" type="password" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            <p v-if="resetForm.errors.password" class="mt-1 text-xs text-red-600">{{ resetForm.errors.password }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showReset = false"
                                class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                            <button type="submit" :disabled="resetForm.processing"
                                class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition disabled:opacity-50">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
