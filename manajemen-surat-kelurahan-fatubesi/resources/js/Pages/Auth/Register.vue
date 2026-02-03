<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const step = ref(1); // 1 = isi data + request OTP, 2 = input OTP + verify

const form = useForm({
    name: '',
    email: '',
    jabatan: '',
    password: '',
    password_confirmation: '',
    otp: '',
});

const requestOtp = () => {
    form.clearErrors();
    form.post(route('register.request-otp'), {
        preserveScroll: true,
        onSuccess: () => {
            step.value = 2;
        },
    });
};

const verifyOtp = () => {
    form.clearErrors();
    form.post(route('register.verify-otp'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <!-- STEP 1: Isi data + Kirim OTP -->
        <form v-if="step === 1" @submit.prevent="requestOtp">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>


            <div class="mt-4">
                <InputLabel for="jabatan" value="Jabatan" />

                <select
                    id="jabatan"
                    v-model="form.jabatan"
                    required
                    class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="" disabled>Pilih jabatan...</option>
                    <option value="lurah">Lurah</option>
                    <option value="sekretaris">Sekretaris</option>
                    <option value="kasie pelayanan masyarakat">Kasie Pelayanan Masyarakat</option>
                    <option value="kasie pem dan trantib umum">Kasie Pem dan Trantib Umum</option>
                    <option value="pengelola pemberdayaan masyarakat dan kelembagaan">
                    Pengelola Pemberdayaan Masyarakat dan Kelembagaan
                    </option>
                    <option value="pengadministrasian umum">Pengadministrasian Umum</option>
                    <option value="pppk">PPPK</option>
                    <option value="ptt">PTT</option>
                </select>

                <InputError class="mt-2" :message="form.errors.jabatan" />
                </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim OTP
                </PrimaryButton>
            </div>
        </form>

        <!-- STEP 2: Input OTP + Verifikasi -->
        <form v-else @submit.prevent="verifyOtp">
            <div class="mb-3 text-sm text-gray-600">
                OTP sudah dikirim ke WhatsApp <b>{{ form.phone }}</b>. Masukkan OTP untuk menyelesaikan pendaftaran.
            </div>

            <div>
                <InputLabel for="otp" value="OTP (6 digit)" />
                <TextInput
                    id="otp"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.otp"
                    required
                    maxlength="6"
                    placeholder="123456"
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.otp" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    class="text-sm text-gray-600 underline hover:text-gray-900"
                    @click="step = 1"
                    :disabled="form.processing"
                >
                    Kembali
                </button>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="text-sm text-gray-600 underline hover:text-gray-900"
                        @click="requestOtp"
                        :disabled="form.processing"
                    >
                        Kirim ulang OTP
                    </button>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Verifikasi & Buat Akun
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
