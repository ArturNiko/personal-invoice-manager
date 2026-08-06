<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref } from 'vue'

type TooltipPositionEvent = MouseEvent | PointerEvent | TouchEvent
type TooltipAnchorElement = HTMLElement | null

const props = withDefaults(
    defineProps<{
        width?: string
        closeOnOutsideClick?: boolean
        closeOnClick?: boolean
        offset?: number
    }>(),
    {
        width: 'w-64',
        closeOnOutsideClick: true,
        closeOnClick: false,
        offset: 12,
    },
)

const tooltipRef = ref<HTMLElement | null>(null)
const positionStyle = ref<Record<string, string>>({})
const isOpen = ref(false)
const anchor = ref<{ x: number; y: number } | null>(null)
const anchorElement = ref<TooltipAnchorElement>(null)

const emit = defineEmits<{
    close: [event?: Event]
}>()

const clamp = (value: number, min: number, max: number) => {
    return Math.min(Math.max(value, min), max)
}

const getPointerPosition = (event: TooltipPositionEvent) => {
    if ('touches' in event) {
        const touch = event.touches[0] ?? event.changedTouches[0]

        if (touch) return { x: touch.pageX, y: touch.pageY }
    }

    const pointerEvent = event as MouseEvent | PointerEvent

    return { x: pointerEvent.pageX, y: pointerEvent.pageY }
}

const getAnchorRect = () => {
    if (anchorElement.value) {
        return anchorElement.value.getBoundingClientRect()
    }

    return null
}

const updatePosition = () => {
    if ((!anchor.value && !anchorElement.value) || !tooltipRef.value) {
        positionStyle.value = {}
        return
    }

    const viewportPadding = 12
    const rect = tooltipRef.value.getBoundingClientRect()
    const tooltipWidth = rect.width
    const tooltipHeight = rect.height
    const anchorRect = getAnchorRect()
    const anchorX = anchorRect ? anchorRect.left : anchor.value!.x - window.scrollX
    const anchorY = anchorRect ? anchorRect.bottom : anchor.value!.y - window.scrollY

    let left = anchorX + props.offset
    let top = anchorY + props.offset

    if (left + tooltipWidth + viewportPadding > window.innerWidth) {
        left = anchorX - tooltipWidth - props.offset
    }

    if (top + tooltipHeight + viewportPadding > window.innerHeight) {
        top = anchorY - tooltipHeight - props.offset
    }

    positionStyle.value = {
        left: `${clamp(left, viewportPadding, window.innerWidth - tooltipWidth - viewportPadding)}px`,
        top: `${clamp(top, viewportPadding, window.innerHeight - tooltipHeight - viewportPadding)}px`,
        visibility: 'visible',
    }
}

const open = async (event: TooltipPositionEvent, element?: HTMLElement | null) => {
    anchor.value = getPointerPosition(event)
    anchorElement.value = element ?? (event.currentTarget instanceof HTMLElement ? event.currentTarget : null)
    isOpen.value = true
    positionStyle.value = { visibility: 'hidden' }

    await nextTick()
    updatePosition()
}

const close = (event?: Event) => {
    isOpen.value = false
    anchor.value = null
    anchorElement.value = null
    positionStyle.value = {}

    if (event) {
        emit('close', event)
    }
}

const handleClick = () => {
    if (props.closeOnClick) {
        close()
    }
}

const handleDocumentPointerDown = (event: PointerEvent) => {
    if (!props.closeOnOutsideClick || !isOpen.value) return

    const target = event.target

    if (!(target instanceof Node)) return

    if (tooltipRef.value?.contains(target)) return
    
    if (anchorElement.value?.contains(target)) return

    close(event)
}

onMounted(() => {
    document.addEventListener('pointerdown', handleDocumentPointerDown)
    window.addEventListener('resize', updatePosition)
    window.addEventListener('scroll', updatePosition, true)
})

onUnmounted(() => {
    document.removeEventListener('pointerdown', handleDocumentPointerDown)
    window.removeEventListener('resize', updatePosition)
    window.removeEventListener('scroll', updatePosition, true)
})

defineExpose({
    open,
    close,
    isOpen,
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            ref="tooltipRef"
            class="fixed z-50 rounded-2xl border border-cyan-400/30 bg-slate-950/95 p-3 shadow-2xl shadow-slate-950/50 backdrop-blur-xl"
            :class="width"
            :style="positionStyle"
            @pointerdown.stop
            @click.stop="handleClick"
        >
            <slot />
        </div>
    </Teleport>
</template>