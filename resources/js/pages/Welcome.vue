<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Verificar si el usuario está autenticado
const page = usePage();
const isAuthenticated = computed(() => !!(page.props as any).auth?.user);
</script>

<template>
    <Head title="Sistema de Votaciones Digital" />
    
    <div class="min-h-screen bg-black">
        <!-- Gradient Background -->
        <div class="absolute top-0 left-0 right-0 z-0 pointer-events-none">
            <div id="gradient" class="aspect-[9/16] sm:aspect-video w-full">
                <img src="/2.png" alt="Background" class="w-full h-full object-cover" />
            </div>
            <div class="absolute inset-0 z-10 w-full h-full aspect-video bg-gradient-to-b from-transparent to-black"></div>
        </div>

        <div class="px-3 sm:px-12 font-medium relative z-20">
            <header class="p-5 sm:px-[40px] sm:py-[30px] sm:flex z-10 my-5 rounded bg-white shadow-lg font-medium">
                <div class="flex justify-between content-center leading-loose sm:leading-none sm:block">
                    <a class="flex items-center gap-3" href="/" title="Home">
                        <img 
                            src="/logo.png" 
                            alt="Colombia Humana" 
                            class="h-10 sm:h-12 w-auto"
                        />
                        <span class="text-lg sm:text-xl font-bold text-gray-800">
                            Colombia Humana
                        </span>
                    </a>
                    <!--<button class="flex items-center gap-x-2 text-black sm:hidden">
                        <svg class="w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 12">
                            <path fill="#172A3D" d="M0 0h14v2H0V0Zm0 5h14v2H0V5Zm14 5v2H0v-2h14Z"></path>
                        </svg>
                        <span class="text-[14px]">Menu</span>
                    </button>-->
                </div>
                <!--<div class="mt-4 sm:mt-0 sm:h-14 grid items-start gap-x-8 sm:gap-y-4 sm:justify-end sm:justify-items-end sm:ml-auto">
                    <hr class="h-px bg-gray-300 sm:hidden -mx-5 mb-5">
                    <nav class="flex sm:row-start-2 mb-5 sm:mb-0 text-blue-900">
                        <ul class="grid sm:grid-flow-col gap-3 sm:gap-4 md:gap-6 sm:justify-between md:text-lg">
                            <li>
                                <Link 
                                    href="/asambleas" 
                                    class="hover:text-blue-600 transition-colors"
                                >
                                    Asambleas
                                </Link>
                            </li>
                            <li>
                                <Link href="/consulta-participantes" class="hover:text-blue-600 transition-colors">
                                    Consulta Participantes
                                </Link>
                            </li>
                            <li v-if="!isAuthenticated">
                                <Link href="/login" class="text-blue-600 font-bold">
                                    Iniciar Sesión
                                </Link>
                            </li>
                            <li v-else>
                                <Link href="/dashboard" class="text-blue-600 font-bold">
                                    Dashboard
                                </Link>
                            </li>
                        </ul>
                    </nav>
                    <nav class="grid text-base sm:opacity-75 sm:row-start-1 sm:grid-flow-col sm:items-center gap-4 sm:text-sm" style="visibility: hidden;">
                        <a href="https://spatie.be/vacancies" class="first:-m-1 first:p-1 rounded-sm text-blue-700">Vacancies</a>
                        <a href="https://spatie.be/about-us" class="first:-m-1 first:p-1 rounded-sm text-blue-700">About</a>
                        <a href="https://spatie.be/blog" class="first:-m-1 first:p-1 rounded-sm text-blue-700">Blog</a>
                        <a href="https://spatie.be/docs" class="first:-m-1 first:p-1 rounded-sm text-blue-700">Docs</a>
                        <a href="https://spatie.be/guidelines" class="first:-m-1 first:p-1 rounded-sm text-blue-700">Guidelines</a>
                        <a href="https://spatie.myspreadshop.net" class="first:-m-1 first:p-1 rounded-sm text-blue-700">Merch ↗</a>
                        <a href="https://spatie.be/login">
                            <span class="icon fill-current text-blue-700" title="Log in">
                                <span class="mr-2 md:hidden">Log in</span>
                                <svg class="w-4 h-4 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"></path>
                                </svg>
                            </span>
                        </a>
                    </nav>
                </div>-->
            </header>
        </div>
        
        <main class="flex-grow relative z-20">
            <div class="px-3 sm:px-16 md:px-10 lg:px-16 flex flex-col gap-y-16 sm:gap-y-20">
                <section id="header" class="md:flex w-full max-w-[1080px] mx-auto mt-8 sm:mt-20 md:mt-32 mb-24 md:mb-52 px-7 lg:px-0">
                    <div class="w-full mb-10 md:mb-0">
                        <h1 class="font-bold uppercase text-[72px] lg:text-[144px] leading-[0.8] mb-10 text-white">Ágora</h1>
                        <h2 class="text-[18px] sm:text-2xl font-medium max-w-[600px] text-white">
                            <span>Sistema de Votaciones Digital para gestionar asambleas, votaciones y procesos electorales de forma segura y transparente.</span>
                            <!--<Link href="/asambleas" class="mt-10 flex items-center gap-x-6 hover:opacity-80 transition-opacity">
                                <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48">
                                    <rect width="46" height="46" x="1" y="1" stroke="#A5A4A3" stroke-width="2" rx="23"></rect>
                                    <path fill="#EAE8E5" d="m24 29.416.706-.707 6-6 .71-.709L30 20.584l-.707.707L24 26.584l-5.294-5.29-.706-.71L16.584 22l.706.706 6 6 .71.71Z"></path>
                                </svg>
                                Ver Asambleas
                            </Link>-->
                        </h2>
                    </div>
                    <aside class="w-full max-w-[360px] flex-shrink-0 md:pl-32 flex flex-col md:justify-end md:items-end pt-10 md:pt-0 border-t md:border-t-0 md:border-l border-white/20">
                        <ul class="text-xl w-full sm:max-w-[240px]">
                            <li class="mb-3 font-bold text-white hover:text-white transition-colors">
                                <Link href="/asambleas" class="flex items-center justify-between">
                                    <span>Ver Asambleas</span>
                                    <svg class="fill-current w-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 7 12">
                                        <path d="m6.687 6-.53.53-4.5 4.5-.532.532L.063 10.5l.53-.53L4.563 6 .596 2.03.063 1.5 1.125.438l.53.53 4.5 4.5.532.532Z"></path>
                                    </svg>
                                </Link>
                            </li>
                            <li class="mb-3 text-white/50 hover:text-white transition-colors">
                                <Link href="/consulta-participantes" class="flex items-center justify-between">
                                    <span>Consulta Participantes</span>
                                    <svg class="fill-current w-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 7 12">
                                        <path d="m6.687 6-.53.53-4.5 4.5-.532.532L.063 10.5l.53-.53L4.563 6 .596 2.03.063 1.5 1.125.438l.53.53 4.5 4.5.532.532Z"></path>
                                    </svg>
                                </Link>
                            </li>
                            <li class="mb-3 text-white/50 hover:text-white transition-colors">
                                <Link href="/postulaciones-aceptadas" class="flex items-center justify-between">
                                    <span>Lista Precandidatos</span>
                                    <svg class="fill-current w-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 7 12">
                                        <path d="m6.687 6-.53.53-4.5 4.5-.532.532L.063 10.5l.53-.53L4.563 6 .596 2.03.063 1.5 1.125.438l.53.53 4.5 4.5.532.532Z"></path>
                                    </svg>
                                </Link>
                            </li>
                            <li class="text-white/50 hover:text-white transition-colors">
                                <Link :href="isAuthenticated ? '/dashboard' : '/login'" class="flex items-center justify-between">
                                    <span>{{ isAuthenticated ? 'Dashboard' : 'Iniciar Sesión' }}</span>
                                    <svg class="fill-current w-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 7 12">
                                        <path d="m6.687 6-.53.53-4.5 4.5-.532.532L.063 10.5l.53-.53L4.563 6 .596 2.03.063 1.5 1.125.438l.53.53 4.5 4.5.532.532Z"></path>
                                    </svg>
                                </Link>
                            </li>
                        </ul>
                    </aside>
                </section>
            </div>
        </main>
        
        <!-- Footer comentado temporalmente -->
        <!-- <footer class="bg-gray-900 text-gray-400 relative z-20">
            <div class="flex-none pt-16 pb-8" role="navigation">
                <div class="px-3 sm:px-16 md:px-10 lg:px-16 leading-loose md:leading-normal">
                    <div class="grid grid-cols-2 items-start text-sm md:flex md:justify-between">
                        <ul class="md:grid grid-flow-col gap-6 justify-between md:text-lg">
                            <li>
                                <a href="https://spatie.be/products">Products</a>
                            </li>
                            <li class="text-blue-400 font-bold">
                                <a href="https://spatie.be/open-source">Open Source</a>
                            </li>
                            <li>
                                <a href="https://spatie.be/courses">Courses</a>
                            </li>
                            <li>
                                <a href="https://spatie.be/web-development">Web Development</a>
                            </li>
                        </ul>
                        <div class="grid md:grid-flow-col md:items-center md:ml-12 md:gap-12">
                            <a href="https://spatie.be/vacancies" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">Vacancies</a>
                            <a href="https://spatie.be/about-us" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">About</a>
                            <a href="https://spatie.be/blog" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">Blog</a>
                            <a href="https://spatie.be/docs" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">Docs</a>
                            <a href="https://spatie.be/guidelines" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">Guidelines</a>
                            <a href="https://spatie.myspreadshop.net" class="first:-m-1 first:p-1 rounded-sm hover:text-blue-300">Merch ↗</a>
                            <a href="https://spatie.be/login">
                                <span class="icon fill-current text-blue-400" title="Log in">
                                    <span class="mr-2 md:hidden">Log in</span>
                                    <svg class="w-4 h-4 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path fill="currentColor" d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"></path>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    </div>
                    <hr class="my-8 h-[2px] bg-gray-700 opacity-25 rounded">
                    <div class="grid gap-4 text-sm sm:grid-cols-2 sm:gap-8 md:flex flex-row-reverse justify-between">
                        <address class="grid gap-4 sm:gap-0 md:grid-flow-col md:gap-8 md:text-right">
                            <div>
                                <a class="group flex items-end md:flex-row-reverse" href="https://goo.gl/maps/A2zoLK3nVF9V8jydA" target="_blank" rel="nofollow noreferrer noopener">
                                    <span>Kruikstraat 22, Box 12<br>2018 Antwerp, Belgium</span>
                                    <span class="icon mb-px px-1 fill-current text-gray-500 group-hover:text-pink-500 transition-all">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                            <path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div>
                                <a href="mailto:info@spatie.be">info@spatie.be</a>
                                <br>
                                <a href="#tel">+32 3 292 56 79</a>
                            </div>
                        </address>
                        <ul class="hidden md:grid md:grid-flow-col md:gap-6">
                            <li>
                                <a href="https://github.com/spatie" target="_blank" rel="nofollow noreferrer noopener">GitHub</a>
                            </li>
                            <li>
                                <a href="https://www.instagram.com/spatie_be" target="_blank" rel="nofollow noreferrer noopener">Instagram</a>
                            </li>
                            <li>
                                <a href="https://be.linkedin.com/company/spatie" rel="me" target="_blank">LinkedIn</a>
                            </li>
                            <li>
                                <a href="https://twitter.com/spatie_be" target="_blank" rel="nofollow noreferrer noopener">Twitter</a>
                            </li>
                            <li>
                                <a href="https://bsky.app/profile/spatie.be" target="_blank" rel="nofollow noreferrer noopener">Bluesky</a>
                            </li>
                            <li class="hidden">
                                <a href="https://phpc.social/@spatie" rel="me" target="_blank">Mastodon</a>
                            </li>
                            <li>
                                <a href="https://www.youtube.com/channel/UCoBbei3S9JLTcS2VeEOWDeQ" target="_blank" rel="nofollow noreferrer noopener">YouTube</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="px-3 sm:px-16 md:px-10 lg:px-16">
                <ul class="grid md:grid-flow-col justify-start text-gray-400 text-xs py-4 md:justify-end md:gap-6">
                    <li>
                        <a href="https://spatie.be/privacy">Privacy</a>
                    </li>
                    <li>
                        <a href="https://spatie.be/disclaimer">Disclaimer</a>
                    </li>
                </ul>
            </div>
        </footer> -->
    </div>
</template>