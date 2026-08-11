<script setup lang="ts">
import { ref, onMounted } from 'vue'


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

const theme = ref(props.theme)

const updateTheme = () => {
    theme.value = props.theme === 'auto' 
        ? window.matchMedia('(prefers-color-scheme: dark)').matches 
            ? 'dark' 
            : 'light' 
        : props.theme
}

onMounted(() => {
    updateTheme()
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme)
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

