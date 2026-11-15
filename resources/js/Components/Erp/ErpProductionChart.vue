<script setup>
import { computed } from 'vue';

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
});

const width = 640;
const height = 240;
const pad = { l: 46, r: 52, t: 16, b: 32 };
const innerW = width - pad.l - pad.r;
const innerH = height - pad.t - pad.b;

const danaValues = computed(() => props.points.map((point) => Number(point.dana_kg) || 0));
const bagValues = computed(() => props.points.map((point) => Number(point.bags_pcs) || 0));
const maxDana = computed(() => Math.max(1, ...danaValues.value, 0));
const maxBags = computed(() => Math.max(1, ...bagValues.value, 0));

const xAt = (index) => {
    const count = props.points.length;

    if (count <= 1) {
        return pad.l + innerW / 2;
    }

    return pad.l + (index / (count - 1)) * innerW;
};

const yAt = (value, max) => pad.t + innerH - (value / max) * innerH;

const toPath = (values, max) =>
    values.map((value, index) => `${index === 0 ? 'M' : 'L'} ${xAt(index).toFixed(2)} ${yAt(value, max).toFixed(2)}`).join(' ');

const danaPath = computed(() => (props.points.length ? toPath(danaValues.value, maxDana.value) : ''));
const bagPath = computed(() => (props.points.length ? toPath(bagValues.value, maxBags.value) : ''));

const ticks = [0, 0.25, 0.5, 0.75, 1];

const formatTick = (value) => {
    if (Math.abs(value) >= 1000) {
        return `${Math.round((value / 1000) * 10) / 10}k`;
    }

    if (Math.abs(value) >= 10) {
        return String(Math.round(value));
    }

    return String(Math.round(value * 100) / 100);
};

const labelIndexes = computed(() => {
    const count = props.points.length;

    if (count <= 8) {
        return props.points.map((_, index) => index);
    }

    const step = Math.ceil(count / 7);
    const indexes = [];

    for (let index = 0; index < count; index += step) {
        indexes.push(index);
    }

    if (indexes[indexes.length - 1] !== count - 1) {
        indexes.push(count - 1);
    }

    return indexes;
});

const showDots = computed(() => props.points.length > 0 && props.points.length <= 16);

const gridY = (fraction) => pad.t + innerH - fraction * innerH;
</script>

<template>
    <div v-if="!points.length" class="flex h-56 items-center justify-center text-sm text-erp-muted">
        No production in this range.
    </div>
    <div v-else>
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="h-auto w-full"
            role="img"
            aria-label="Dana kilograms and bag pieces for the selected range. Each line uses its own scale."
        >
            <line
                v-for="fraction in ticks"
                :key="fraction"
                :x1="pad.l"
                :x2="width - pad.r"
                :y1="gridY(fraction)"
                :y2="gridY(fraction)"
                stroke="currentColor"
                class="text-slate-200 dark:text-slate-700"
                stroke-width="1"
            />
            <text
                v-for="fraction in ticks"
                :key="`dana-${fraction}`"
                :x="pad.l - 8"
                :y="gridY(fraction) + 4"
                text-anchor="end"
                class="fill-blue-500 text-[11px]"
            >
                {{ formatTick(maxDana * fraction) }}
            </text>
            <text
                v-for="fraction in ticks"
                :key="`bags-${fraction}`"
                :x="width - pad.r + 8"
                :y="gridY(fraction) + 4"
                text-anchor="start"
                class="fill-emerald-500 text-[11px]"
            >
                {{ formatTick(maxBags * fraction) }}
            </text>
            <path :d="danaPath" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
            <path :d="bagPath" fill="none" stroke="#22c55e" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
            <template v-if="showDots">
                <circle
                    v-for="(point, index) in points"
                    :key="`${point.date}-dana`"
                    :cx="xAt(index)"
                    :cy="yAt(danaValues[index], maxDana)"
                    r="3.5"
                    fill="#3b82f6"
                >
                    <title>{{ point.label }}: {{ point.dana_kg }} KG</title>
                </circle>
                <circle
                    v-for="(point, index) in points"
                    :key="`${point.date}-bags`"
                    :cx="xAt(index)"
                    :cy="yAt(bagValues[index], maxBags)"
                    r="3.5"
                    fill="#22c55e"
                >
                    <title>{{ point.label }}: {{ point.bags_pcs }} pcs</title>
                </circle>
            </template>
            <text
                v-for="index in labelIndexes"
                :key="`label-${points[index].date}`"
                :x="xAt(index)"
                :y="height - 8"
                text-anchor="middle"
                class="fill-slate-400 text-[11px]"
            >
                {{ points[index].label }}
            </text>
        </svg>
        <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-erp-muted">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-blue-500" />
                Dana (KG)
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                Bags (pcs)
            </span>
            <span>Each line uses its own scale.</span>
        </div>
    </div>
</template>
