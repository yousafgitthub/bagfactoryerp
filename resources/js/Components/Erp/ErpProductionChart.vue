<script setup>
import { computed } from 'vue';

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
});

const maxDana = computed(() => {
    const values = props.points.map((p) => Number(p.dana_kg) || 0);

    return Math.max(1, ...values);
});

const maxBags = computed(() => {
    const values = props.points.map((p) => Number(p.bags_pcs) || 0);

    return Math.max(1, ...values);
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-erp-muted">Dana output (KG)</p>
            <div class="flex h-32 items-end gap-1" role="img" aria-label="Dana production last seven days">
                <div
                    v-for="point in points"
                    :key="point.date + '-dana'"
                    class="flex flex-1 flex-col items-center gap-1"
                >
                    <div
                        class="w-full max-w-[2rem] rounded-t bg-blue-600 dark:bg-blue-400"
                        :style="{ height: `${Math.max(4, (Number(point.dana_kg) / maxDana) * 100)}%` }"
                        :title="`${point.label}: ${point.dana_kg} KG`"
                    />
                    <span class="text-[10px] text-erp-muted">{{ point.label }}</span>
                </div>
            </div>
        </div>
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-erp-muted">Shopping bags (pieces)</p>
            <div class="flex h-32 items-end gap-1" role="img" aria-label="Bag production last seven days">
                <div
                    v-for="point in points"
                    :key="point.date + '-bags'"
                    class="flex flex-1 flex-col items-center gap-1"
                >
                    <div
                        class="w-full max-w-[2rem] rounded-t bg-emerald-600 dark:bg-emerald-400"
                        :style="{ height: `${Math.max(4, (Number(point.bags_pcs) / maxBags) * 100)}%` }"
                        :title="`${point.label}: ${point.bags_pcs} pcs`"
                    />
                    <span class="text-[10px] text-erp-muted">{{ point.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>