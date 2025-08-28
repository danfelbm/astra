<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AvatarUpload from '@/components/forms/AvatarUpload.vue';
import UserLayout from "@/layouts/UserLayout.vue";
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import axios from 'axios';
import { toast } from 'vue-sonner';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
    // Props de permisos de usuario
    canEdit: boolean;
    canChangePassword: boolean;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/miembro/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

// Manejo de avatar
const handleAvatarUpload = async (file: File) => {
    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await axios.post(route('profile.avatar.upload'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            // Actualizar el avatar en el objeto user
            if (user) {
                user.avatar_url = response.data.avatar_url;
            }
            
            toast.success('Avatar actualizado correctamente');
            
            // Recargar la página para actualizar el avatar en toda la app
            window.location.reload();
        }
    } catch (error) {
        console.error('Error uploading avatar:', error);
        toast.error('No se pudo cargar el avatar');
    }
};

const handleAvatarDelete = async () => {
    try {
        const response = await axios.delete(route('profile.avatar.delete'));

        if (response.data.success) {
            // Actualizar el avatar en el objeto user
            if (user) {
                user.avatar_url = response.data.avatar_url;
            }
            
            toast.success('Avatar eliminado correctamente');
            
            // Recargar la página para actualizar el avatar en toda la app
            window.location.reload();
        }
    } catch (error) {
        console.error('Error deleting avatar:', error);
        toast.error('No se pudo eliminar el avatar');
    }
};
</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name and email address" />

                <!-- Avatar upload section -->
                <div class="space-y-4">
                    <AvatarUpload
                        :model-value="user?.avatar_url"
                        :user-name="user?.name || 'Usuario'"
                        label="Foto de perfil"
                        description="Sube una foto de perfil personalizada. JPG, PNG o WEBP. Máximo 5MB."
                        @upload="handleAvatarUpload"
                        @delete="handleAvatarDelete"
                        :disabled="!canEdit"
                    />
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="mt-2 text-sm text-neutral-800">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="focus:outline-hidden rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </UserLayout>
</template>
