<script setup>
defineProps({
    entity: {
        type: Object,
        default: null,
    },
    size: {
        type: String,
        default: 'md', // sm, md
    }
});
</script>

<template>
    <div 
        v-if="entity"
        class="inline-flex items-center gap-1 rounded-full"
        :class="{
            'px-2 py-0.5 text-xs': size === 'sm',
            'px-2.5 py-1 text-sm': size === 'md',
        }"
        :style="{ 
            backgroundColor: entity.hex_color + '20',
            color: entity.hex_color,
            border: `1px solid ${entity.hex_color}40`
        }"
    >
        <span 
            class="rounded-full"
            :class="{
                'w-2 h-2': size === 'sm',
                'w-2.5 h-2.5': size === 'md',
            }"
            :style="{ backgroundColor: entity.hex_color }"
        ></span>
        <span class="font-medium">{{ entity.name }}</span>
    </div>
    <div 
        v-else
        class="inline-flex items-center gap-1 rounded-full text-gray-400 border border-gray-300 bg-gray-50"
        :class="{
            'px-2 py-0.5 text-xs': size === 'sm',
            'px-2.5 py-1 text-sm': size === 'md',
        }"
    >
        <span>Unassigned</span>
    </div>
</template>
