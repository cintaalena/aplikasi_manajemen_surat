<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    credential_code: '',
    password: '',
    remember: false,
});

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
        class="mb-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 text-sm text-purple-800"
        >
        {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
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
                    <span class="ms-2 text-sm text-gray-600"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            
            </div>
        </form>
        <div class="mt-6 text-center">
    <p class="text-sm text-gray-600">
        Belum punya akun?
    </p>

    <Link
        :href="route('register')"
        class="inline-block mt-2 px-4 py-2 text-sm font-semibold
               text-purple-700 border border-purple-300 rounded-lg
               hover:bg-purple-50 transition"
    >
        Daftar Akun Kelurahan
    </Link>
</div>
    </GuestLayout>
</template>
