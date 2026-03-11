<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    name: '',
    recovery_email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="mb-4 text-sm text-gray-600">
            Lupa password? Masukkan <strong>username</strong> dan <strong>email pemulihan</strong>
            yang Anda daftarkan saat registrasi. Kami akan mengirimkan link reset password ke email tersebut.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Username" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="off"
                    placeholder="Masukkan username Anda"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="recovery_email" value="Email Pemulihan" />

                <TextInput
                    id="recovery_email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.recovery_email"
                    required
                    autocomplete="off"
                    placeholder="Masukkan email pemulihan Anda"
                />

                <InputError class="mt-2" :message="form.errors.recovery_email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim Link Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
