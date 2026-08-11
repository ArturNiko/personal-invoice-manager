<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

type HistoryItem = { id: string; expression: string; result: string }

const STORAGE_KEY = 'tally-calculator-history'
const opSymbols: Record<string, string> = { '+': '+', '-': '−', '*': '×', '/': '÷' }

const display = ref('0')
const expression = ref('')
const previous = ref<number | null>(null)
const operator = ref<string | null>(null)
const waitingForOperand = ref(false)
const historyOpen = ref(false)
const history = ref<HistoryItem[]>([])
const activeKeyId = ref<string | null>(null)
const keyUpTimer = ref<ReturnType<typeof window.setTimeout> | null>(null)
const tapeSection = ref<HTMLElement | null>(null)

const formattedDisplay = computed(() => {
    if (display.value === 'Error') {
        return 'Error'
    }

    const [intPart, decPart] = display.value.split('.')
    const sign = intPart.startsWith('-') ? '-' : ''
    const intAbs = sign ? intPart.slice(1) : intPart
    const withCommas = intAbs.replace(/\B(?=(\d{3})+(?!\d))/g, ',')

    return sign + withCommas + (decPart !== undefined ? `.${decPart}` : '')
})

function formatNumber(n: number) {
    if (!Number.isFinite(n)) {
        return 'Error'
    }

    const rounded = Number(n.toPrecision(12))
    return rounded.toString()
}

function compute(a: number, b: number, op: string) {
    switch (op) {
        case '+':
            return a + b
        case '-':
            return a - b
        case '*':
            return a * b
        case '/':
            return b === 0 ? Number.NaN : a / b
        default:
            return b
    }
}

function clearAll() {
    display.value = '0'
    expression.value = ''
    previous.value = null
    operator.value = null
    waitingForOperand.value = false
}

function saveHistory() {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(history.value))
    } catch {
        // storage unavailable
    }
}

function addHistory(nextExpression: string, result: string) {
    history.value.unshift({
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
        expression: nextExpression,
        result,
    })

    if (history.value.length > 50) {
        history.value.pop()
    }

    saveHistory()
}

function inputDigit(digit: string) {
    if (display.value === 'Error') {
        clearAll()
    }

    if (waitingForOperand.value) {
        display.value = digit
        waitingForOperand.value = false
    } else {
        display.value = display.value === '0' ? digit : display.value + digit
    }
}

function inputDecimal() {
    if (display.value === 'Error') {
        clearAll()
    }

    if (waitingForOperand.value) {
        display.value = '0.'
        waitingForOperand.value = false
    } else if (!display.value.includes('.')) {
        display.value += '.'
    }
}

function toggleSign() {
    if (display.value === 'Error') {
        return
    }

    display.value = display.value.startsWith('-') ? display.value.slice(1) : `-${display.value}`
}

function percent() {
    if (display.value === 'Error') {
        return
    }

    display.value = formatNumber(Number.parseFloat(display.value) / 100)
}

function backspace() {
    if (display.value === 'Error') {
        clearAll()
        return
    }

    if (waitingForOperand.value) {
        return
    }

    display.value = display.value.length > 1 ? display.value.slice(0, -1) : '0'
}

function setOperator(nextOperator: string) {
    if (display.value === 'Error') {
        clearAll()
    }

    const inputValue = Number.parseFloat(display.value)

    if (previous.value === null) {
        previous.value = inputValue
    } else if (operator.value && !waitingForOperand.value) {
        const result = compute(previous.value, inputValue, operator.value)
        display.value = formatNumber(result)
        previous.value = Number.parseFloat(display.value)
    }

    operator.value = nextOperator
    waitingForOperand.value = true
    expression.value = `${formatNumber(previous.value as number)} ${opSymbols[nextOperator]}`
}

function equals() {
    if (operator.value === null || previous.value === null || display.value === 'Error') {
        return
    }

    const inputValue = Number.parseFloat(display.value)
    const exprStr = `${formatNumber(previous.value)} ${opSymbols[operator.value]} ${formatNumber(inputValue)}`
    const result = compute(previous.value, inputValue, operator.value)
    const resultStr = formatNumber(result)

    display.value = resultStr
    expression.value = ''

    if (resultStr !== 'Error') {
        addHistory(exprStr, resultStr)
    }

    previous.value = null
    operator.value = null
    waitingForOperand.value = true
}

function recall(item: HistoryItem) {
    display.value = item.result
    expression.value = ''
    previous.value = null
    operator.value = null
    waitingForOperand.value = true
}

function clearHistory() {
    history.value = []
    saveHistory()
}

function toggleHistory() {
    historyOpen.value = !historyOpen.value

    if (historyOpen.value) {
        void nextTick().then(() => {
            tapeSection.value?.scrollIntoView({
                behavior: 'smooth',
                block: 'end',
            })
        })
    }
}

function loadHistory() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY)
        if (raw) {
            history.value = JSON.parse(raw) as HistoryItem[]
        }
    } catch {
        history.value = []
    }
}

function onKeyup() {
    activeKeyId.value = null
}

function onKeydown(event: KeyboardEvent) {
    if (event.metaKey || event.ctrlKey || event.altKey) {
        return
    }

    let id: string | null = null

    if (event.key >= '0' && event.key <= '9') {
        id = event.key
        inputDigit(event.key)
    } else if (event.key === '.' || event.code === 'NumpadDecimal') {
        id = '.'
        inputDecimal()
    } else if (event.key === '+' || event.code === 'NumpadAdd') {
        id = '+'
        setOperator('+')
    } else if (event.key === '-' || event.code === 'NumpadSubtract') {
        id = '-'
        setOperator('-')
    } else if (event.key === '*' || event.code === 'NumpadMultiply') {
        id = '*'
        setOperator('*')
    } else if (event.key === '/' || event.code === 'NumpadDivide') {
        event.preventDefault()
        id = '/'
        setOperator('/')
    } else if (event.key === 'Enter' || event.key === '=' || event.code === 'NumpadEnter') {
        event.preventDefault()
        id = 'enter'
        equals()
    } else if (event.key === 'Backspace') {
        backspace()
    } else if (event.key === 'Delete' || event.key === 'Escape' || event.key === 'Clear') {
        id = 'escape'
        clearAll()
    } else if (event.key === '%') {
        id = '%'
        percent()
    } else if (event.key.toLowerCase() === 'n') {
        id = 'sign'
        toggleSign()
    } else {
        return
    }

    activeKeyId.value = id

    if (keyUpTimer.value !== null) {
        clearTimeout(keyUpTimer.value)
    }

    keyUpTimer.value = window.setTimeout(() => {
        activeKeyId.value = null
    }, 250)
}

onMounted(() => {
    loadHistory()
    window.addEventListener('keydown', onKeydown)
    window.addEventListener('keyup', onKeyup)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown)
    window.removeEventListener('keyup', onKeyup)

    if (keyUpTimer.value !== null) {
        clearTimeout(keyUpTimer.value)
    }
})
</script>

<template>
    <div class="mx-auto w-full max-w-[500px] text-slate-100">
        <div class="mb-4 text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
            Tally <span class="text-orange-400">·</span> desk calculator
        </div>

        <div class="relative z-10 rounded-[22px] bg-gradient-to-br from-[#252a37] to-[#1d212c] p-[18px] shadow-[0_30px_60px_-20px_rgba(0,0,0,0.65),inset_0_1px_0_rgba(255,255,255,0.04),inset_0_-1px_0_rgba(0,0,0,0.4)]">
            <div class="mb-[14px] flex min-h-[96px] flex-col justify-end overflow-hidden rounded-[14px] bg-[#0b0d12] px-[18px] pb-4 pt-[22px] shadow-inner shadow-black/60">
                <div class="h-4 truncate text-right font-mono text-[13px] tracking-[0.02em] text-[#5c6070]">
                    {{ expression || '\u00A0' }}
                </div>
                <div
                    class="truncate text-right font-mono text-[clamp(28px,8.5vw,42px)] font-semibold leading-[1.15] text-[#f4efe4] [text-shadow:0_0_18px_rgba(244,239,228,0.18)]"
                    :class="{ 'text-orange-400': display === 'Error' }"
                >
                    {{ formattedDisplay }}
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2.5">
                <button class="h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === 'escape' }" @click="clearAll">C</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === 'sign' }" @click="toggleSign">±</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '%' }" @click="percent">%</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': operator === '/', pressed: activeKeyId === '/' }" @click="setOperator('/')">÷</button>

                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '7' }" @click="inputDigit('7')">7</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '8' }" @click="inputDigit('8')">8</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '9' }" @click="inputDigit('9')">9</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': operator === '*', pressed: activeKeyId === '*' }" @click="setOperator('*')">×</button>

                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '4' }" @click="inputDigit('4')">4</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '5' }" @click="inputDigit('5')">5</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '6' }" @click="inputDigit('6')">6</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': operator === '-', pressed: activeKeyId === '-' }" @click="setOperator('-')">−</button>

                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '1' }" @click="inputDigit('1')">1</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '2' }" @click="inputDigit('2')">2</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '3' }" @click="inputDigit('3')">3</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': operator === '+', pressed: activeKeyId === '+' }" @click="setOperator('+')">+</button>

                <button class="col-span-2 h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '0' }" @click="inputDigit('0')">0</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === '.' }" @click="inputDecimal">.</button>
                <button class="h-[58px] rounded-xl border border-transparent bg-gradient-to-br from-[#ffa05e] to-[#ff8a3d] font-mono text-[19px] font-bold text-[#1a1206] shadow-[0_4px_14px_-2px_rgba(255,138,61,0.5)] transition duration-[60ms] hover:brightness-105 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ pressed: activeKeyId === 'enter' }" @click="equals">=</button>
            </div>
        </div>

        <div ref="tapeSection" class="mt-3.5">
            <button class="flex w-full items-center justify-between rounded-xl bg-[#1d212c] px-4 py-3 text-[12px] font-semibold uppercase tracking-[0.1em] text-[#868ba3] transition duration-150 hover:bg-[#252a37] hover:text-[#f4efe4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="toggleHistory">
                <span>Tape ({{ history.length }})</span>
                <span class="font-mono text-[15px]">{{ historyOpen ? '−' : '+' }}</span>
            </button>

            <template v-if="historyOpen">
                <div class="mt-0.5 max-h-[260px] overflow-y-auto rounded-b-[10px] rounded-t-[2px] bg-[#efe7d5] bg-[repeating-linear-gradient(0deg,rgba(0,0,0,0.028)_0px,rgba(0,0,0,0.028)_1px,transparent_1px,transparent_5px)] px-1 py-[6px] pb-[10px] [clip-path:polygon(0%_0%,4%_3%,8%_0%,12%_3%,16%_0%,20%_3%,24%_0%,28%_3%,32%_0%,36%_3%,40%_0%,44%_3%,48%_0%,52%_3%,56%_0%,60%_3%,64%_0%,68%_3%,72%_0%,76%_3%,80%_0%,84%_3%,88%_0%,92%_3%,96%_0%,100%_3%,100%_100%,0%_100%)]">
                    <div
                        v-for="item in history"
                        :key="item.id"
                        class="cursor-pointer border-b border-dashed border-[#d9cfb6] px-4 py-[9px] font-mono transition hover:bg-black/5 last:border-b-0"
                        @click="recall(item)"
                        title="Click to reuse this result"
                    >
                        <div class="text-[12px] text-[#7a705c]">{{ item.expression }}</div>
                        <div class="text-right text-[16px] font-semibold text-[#332b1f]">= {{ item.result }}</div>
                    </div>

                    <div v-if="!history.length" class="px-4 py-5 text-center text-[13px] text-[#7a705c]">
                        Nothing on the tape yet
                    </div>
                </div>

                <button
                    v-if="history.length"
                    class="block w-full appearance-none border-0 bg-transparent px-0 py-2 text-[11px] uppercase tracking-[0.08em] text-[#868ba3] underline underline-offset-[3px] transition hover:text-orange-400"
                    @click="clearHistory"
                >
                    Clear tape
                </button>
            </template>
        </div>
    </div>
</template>
