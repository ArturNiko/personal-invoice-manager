<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'


const props = withDefaults(
    defineProps<{
        icon: string
        theme?: 'auto' | 'light' | 'dark'
    }>(),
    {
        theme: 'auto',
    },
)

const iconUrl = `/icons/${props.icon}.svg`

const systemTheme = ref<'light' | 'dark'>('light')
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

const updateSystemTheme = () => {
    systemTheme.value = mediaQuery.matches ? 'dark' : 'light'
}

const theme = computed(() => {
    if (props.theme === 'auto') {
        return systemTheme.value
    }

    return props.theme
})

onMounted(() => {
    updateSystemTheme()
    mediaQuery.addEventListener('change', updateSystemTheme)
})

onUnmounted(() => {
    mediaQuery.removeEventListener('change', updateSystemTheme)
})



</script>

<template>
    <i
        :class="['icon', `icon--${theme}`]"
        :style="{ '--icon-url': `url(${iconUrl})` }"
        aria-hidden="true"
    ></i>
</template>

<style scoped>
.icon {
    display: inline-block;
    width: 24px;
    height: 24px;
    vertical-align: middle;
    color: #0f172a;
    background-color: currentColor;
    -webkit-mask: var(--icon-url) no-repeat center / contain;
    mask: var(--icon-url) no-repeat center / contain;
}

.icon--light {
    color: #0f172a;
}

.icon--dark {
    color: #ffffff;
}

@media (prefers-color-scheme: dark) {
    .icon--auto {
        color: #ffffff;
    }
}
</style>

