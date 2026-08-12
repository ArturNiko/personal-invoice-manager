<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type HistoryItem = { id: string; expression: string; result: string };

const STORAGE_KEY = 'tally-calculator-history';

const currentInput = ref('0');
const expression = ref('');
const history = ref<HistoryItem[]>([]);
const historyOpen = ref(false);


const isError = () => computed(() => currentInput.value === 'Error');
const isEmpty = () => computed(() => currentInput.value === '0' || currentInput.value === '');
const isLastDigit = () => computed(() => currentInput.value.length > 0 && /\d$/.test(currentInput.value));
const isLastOperator = () => computed(() => currentInput.value.length > 0 && / [+\-*/%]$/.test(currentInput.value));

const saveHistory = () => {
    history.value.unshift({
        id: Date.now().toString(),
        expression: expression.value,
        result: currentInput.value,
    });
        
    localStorage.setItem(STORAGE_KEY, JSON.stringify(history.value));
};

const clearHistory = () => {
    history.value = [];
    localStorage.removeItem(STORAGE_KEY);
};

const toggleHistory = () => {
    historyOpen.value = !historyOpen.value;
};

const recallFromHistory = (item: HistoryItem) => {
    currentInput.value = item.result;
    historyOpen.value = false;
};

const clearAll = () => {
    currentInput.value = '0';
};

const clearLast = () => {
    if (isError().value || isEmpty().value) {
        currentInput.value = '0';
        return;
    }

    currentInput.value = currentInput.value.slice(0, -1).trim();

    if (isEmpty().value) currentInput.value = '0';
};

const calculateResult = () => {
    try {
        if (isError().value || isEmpty().value) currentInput.value = '0';

        if (!/[+\-*/%]/.test(currentInput.value)) return;

        const result = new Function(`return ${currentInput.value}`)();
        expression.value = currentInput.value;
        currentInput.value = result.toString();
        saveHistory();
    } 
    catch (error) {
        console.error('Error calculating result:', error);
        currentInput.value = 'Error';
    }
};

const setOperator = (operator: string) => {
    if (isError().value || isEmpty().value) {
        currentInput.value = '0';
        return;
    }

    if (isLastOperator().value) {
        currentInput.value = currentInput.value.slice(0, -2).trim();
        currentInput.value += ` ${operator}`;
    }

    if (!isLastDigit().value) return;

    currentInput.value += ` ${operator}`;
};

const inputDecimal = () => {
    if (isError().value || isEmpty().value) {
        currentInput.value = '0.';
        return;
    }

    if (isLastOperator().value) {
        currentInput.value += '0.';
        return;
    }

    currentInput.value += '.';

};

const inputDigit = (digit: string) => {
    if (isError().value || isEmpty().value) {
        currentInput.value = digit;
        return;
    }

    if (isLastOperator().value) {
        currentInput.value += ` ${digit}`;
        return;
    }

    currentInput.value += digit;
};

const toggleSign = () => {
    if (isError().value || isEmpty().value) {
        currentInput.value = '0';
        return;
    }

    if (isLastOperator().value) {
        return;
    }

    const parts = currentInput.value.split(' ');
    const lastPart = parts[parts.length - 1];
    const toggledPart = lastPart.startsWith('-') ? lastPart.slice(1) : `-${lastPart}`;
    parts[parts.length - 1] = toggledPart;
    currentInput.value = parts.join(' ');
};

const isOperatorActive = (operator: string) => {
    if (isError().value || isEmpty().value) return false;

    const parts = currentInput.value.split(' ');
    const lastPart = parts[parts.length - 1];
    return lastPart === operator;
};

const handleKeydown = (event: KeyboardEvent) => {
    const key = event.key;

    switch (key) {
        case 'Enter':
            calculateResult();
            break;
        case 'Backspace':
            clearLast();
            break;    
        case '+':
        case '-':
        case '*':
        case '/':
        case '%':
            setOperator(key);
            break;
        case '.':
            inputDecimal();
            break;
        default:
            if (!isNaN(Number(key))) inputDigit(key);
    }
};

onMounted(() => {
    const storedHistory = localStorage.getItem(STORAGE_KEY);
    if (storedHistory) {
        try {
            history.value = JSON.parse(storedHistory);
        } 
        catch (error) {
            console.error('Failed to parse stored history:', error);
        }
    }

    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="mx-auto w-full max-w-[500px] text-slate-100">
        <div class="mb-4 text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
            Tally <span class="text-orange-400">·</span> desk calculator
        </div>

        <div class="relative z-10 rounded-[22px] bg-gradient-to-br from-[#252a37] to-[#1d212c] p-[18px] shadow-[0_30px_60px_-20px_rgba(0,0,0,0.65),inset_0_1px_0_rgba(255,255,255,0.04),inset_0_-1px_0_rgba(0,0,0,0.4)]">
            <div class="mb-[14px] flex min-h-[96px] flex-col justify-end overflow-hidden rounded-[14px] bg-[#0b0d12] px-[18px] pb-4 pt-[22px] shadow-inner shadow-black/60">
                <div ref="topLineRef" class="calculator-scrollbar-none h-4 overflow-x-auto overflow-y-hidden whitespace-nowrap text-right font-mono text-[12px] tracking-[0.02em] text-[#5c6070]">
                    {{ expression || '\u00A0' }}
                </div>
                <div
                    ref="mainLineRef"
                    class="calculator-scrollbar-none overflow-x-auto overflow-y-hidden whitespace-nowrap text-right font-mono text-[clamp(24px,7.2vw,38px)] font-semibold leading-[1.15] text-[#f4efe4] [text-shadow:0_0_18px_rgba(244,239,228,0.18)]"
                    :class="{ 'text-orange-400': isError }"
                >
                    {{ currentInput || '\u00A0' }}
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2">
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[15px] sm:text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="clearAll">C</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[15px] sm:text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="toggleSign">±</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#3a4159] font-sans text-[15px] sm:text-[16px] font-semibold text-[#d7dae6] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#454e69] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="setOperator('%')">%</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[20px] sm:text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': isOperatorActive('/') }" @click="setOperator('/')">÷</button>

                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('7')">7</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('8')">8</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('9')">9</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[20px] sm:text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': isOperatorActive('*') }" @click="setOperator('*')">×</button>

                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('4')">4</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('5')">5</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('6')">6</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[20px] sm:text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': isOperatorActive('-') }" @click="setOperator('-')">−</button>

                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('1')">1</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('2')">2</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('3')">3</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-transparent font-mono text-[20px] sm:text-[22px] text-orange-400 transition duration-[60ms] hover:bg-orange-400/10 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" :class="{ 'bg-orange-400/16': isOperatorActive('+') }" @click="setOperator('+')">+</button>

                <button class="col-span-2 h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDigit('0')">0</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-[#2b3040] font-mono text-[18px] sm:text-[19px] font-medium text-[#f4efe4] shadow-[0_2px_0_rgba(0,0,0,0.35),inset_0_1px_0_rgba(255,255,255,0.03)] transition duration-[60ms] hover:bg-[#333a4c] active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="inputDecimal">.</button>
                <button class="h-[52px] sm:h-[58px] rounded-xl border border-transparent bg-gradient-to-br from-[#ffa05e] to-[#ff8a3d] font-mono text-[18px] sm:text-[19px] font-bold text-[#1a1206] shadow-[0_4px_14px_-2px_rgba(255,138,61,0.5)] transition duration-[60ms] hover:brightness-105 active:translate-y-[2px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent" @click="calculateResult">=</button>
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
                        @click="recallFromHistory(item)"
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
<style scoped>
.calculator-scrollbar-none {
    scrollbar-width: none;
}

.calculator-scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>