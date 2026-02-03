<script setup>
import {computed} from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    entities: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select entity...',
    },
    allowClear: {
        type: Boolean,
        default: true,
    }
});

const emit = defineEmits(['update:modelValue']);

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value === '' ? null : Number(value)),
});
</script>

<template>
    <select 
        v-model="selected"
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
    >
        <option value="">{{ placeholder }}</option>
        <option 
            v-for="entity in entities" 
            :key="entity.id" 
            :value="entity.id"
        >
            {{ entity.name }}
        </option>
    </select>
</template>
