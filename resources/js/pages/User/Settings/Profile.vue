<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
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

// Solo mantenemos el formulario para mostrar el nombre pero sin funcionalidad de envío
const form = useForm({
    name: user.name,
});

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
                <HeadingSmall title="Información del Perfil" description="Visualiza tu información personal" />

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

                <!-- Solo mostrar nombre como campo deshabilitado -->
                <div class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input 
                            id="name" 
                            class="mt-1 block w-full" 
                            v-model="form.name" 
                            disabled 
                            autocomplete="name" 
                            placeholder="Nombre completo" 
                        />
                    </div>

                </div>
            </div>

            <!-- Sección de eliminar cuenta oculta -->
        </SettingsLayout>
    </UserLayout>
</template>
