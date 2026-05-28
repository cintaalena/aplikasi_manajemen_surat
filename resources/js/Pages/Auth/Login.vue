<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    status: {
        type: String,
    },
    // SECURITY (A07): staffNames prop removed — exposing all active user names
    // in the login page enables username enumeration. Use free-text input instead.
});

const adminMode = ref(false);

const form = useForm({
    name: '',
    credential_code: '',
    password: '',
    remember: false,
});

const toggleAdminMode = () => {
    adminMode.value = !adminMode.value;
    form.reset('name', 'credential_code', 'password');
    form.clearErrors();
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div
            v-if="$page.props.flash?.credential"
            class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            <div class="font-semibold">Credential Anda:</div>
            <div class="mt-1 font-mono text-base">
                {{ $page.props.flash.credential }}
            </div>
            <div class="mt-1 text-xs text-emerald-700">
                Simpan credential ini. Credential hanya ditampilkan sekali.
            </div>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-800"
        >
            {{ status }}
        </div>

        <!-- Mode indicator -->
        <div v-if="adminMode" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
            Mode Admin — masukkan username dan password admin.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nama" />

                <!-- SECURITY (A07): Plain text input for all roles.
                     Dropdown was removed to prevent username enumeration. -->
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    :placeholder="adminMode ? 'Username admin' : 'Masukkan nama Anda'"
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Credential code — disembunyikan saat mode admin -->
            <div v-if="!adminMode" class="mt-4">
                <InputLabel for="credential_code" value="Credential Code" />

                <TextInput
                    id="credential_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.credential_code"
                    required
                    placeholder="Contoh: A-001"
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="form.errors.credential_code" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    class="text-xs text-gray-500 underline hover:text-gray-700"
                    @click="toggleAdminMode"
                >
                    {{ adminMode ? 'Kembali ke login staff' : 'Login sebagai Admin' }}
                </button>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
