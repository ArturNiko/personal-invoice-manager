<script setup lang="ts">
const notFoundImage = `${import.meta.env.BASE_URL}images/not-found.png`;
</script>

<template>
    <section class="flex min-h-dvh items-center justify-center px-4 py-12">
        <div
            class="w-full max-w-lg rounded-[2rem] border border-white/10 bg-slate-900/80 p-8 text-center shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-10"
        >
            <img
                :src="notFoundImage"
                alt="Personal Invoice Reader"
                class="mx-auto w-auto h-40 sm:h-80 md:h-96"
            />

            <p
                class="mt-6 text-xs font-semibold uppercase tracking-[0.35em] text-rose-300/80"
            >
                404
            </p>
            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">
                Page not found.
            </h2>
            <p class="mt-3 text-sm leading-6 text-slate-400">
                The route you opened does not exist in this app.
            </p>
        </div>
    </section>
</template>
