<script setup>
import {ref} from "vue";
import {Head, useForm, router} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Input from "@/Components/Form/Input.vue";
import ColorPicker from "@/Components/Form/ColorPicker.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Trash from "@/Icons/Trash.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";

const pageTitle = 'Entities / Brands';

const props = defineProps({
    entities: {
        type: Array,
        required: true,
    }
});

const form = useForm({
    name: '',
    hex_color: '#6366f1',
});

const editingEntity = ref(null);
const deletingEntity = ref(null);

const submit = () => {
    if (editingEntity.value) {
        form.put(route('mixpost.entities.update', editingEntity.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                editingEntity.value = null;
            },
        });
    } else {
        form.post(route('mixpost.entities.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};

const startEdit = (entity) => {
    editingEntity.value = entity;
    form.name = entity.name;
    form.hex_color = entity.hex_color;
};

const cancelEdit = () => {
    editingEntity.value = null;
    form.reset();
};

const confirmDelete = (entity) => {
    deletingEntity.value = entity;
};

const deleteEntity = () => {
    router.delete(route('mixpost.entities.destroy', deletingEntity.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deletingEntity.value = null;
        },
    });
};
</script>

<template>
    <Head :title="pageTitle"/>

    <div class="row-py w-full mx-auto">
        <PageHeader :title="pageTitle">
            <template #description>
                Organize your social accounts by entity or brand (e.g., Mighty House Inc, DSAIC, Computer Store).
            </template>
        </PageHeader>

        <div class="row-px">
            <Panel>
                <template #title>
                    {{ editingEntity ? 'Edit Entity' : 'Add Entity' }}
                </template>

                <form @submit.prevent="submit" class="flex flex-wrap items-end gap-md">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <Input v-model="form.name" type="text" placeholder="Entity name" class="w-full" required/>
                    </div>

                    <div class="w-32">
                        <label class="block text-sm font-medium mb-1">Color</label>
                        <ColorPicker v-model="form.hex_color"/>
                    </div>

                    <div class="flex gap-xs">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            {{ editingEntity ? 'Update' : 'Add' }}
                        </PrimaryButton>
                        <SecondaryButton v-if="editingEntity" @click="cancelEdit" type="button">
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Panel>

            <Panel class="mt-lg">
                <template #title>
                    Entities
                </template>

                <div v-if="entities.length === 0" class="text-gray-500 text-center py-lg">
                    No entities yet. Add one above to get started.
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="entity in entities"
                        :key="entity.id"
                        class="flex items-center justify-between py-sm"
                    >
                        <div class="flex items-center gap-sm">
                            <div
                                class="w-4 h-4 rounded-full"
                                :style="{ backgroundColor: entity.hex_color }"
                            ></div>
                            <span class="font-medium">{{ entity.name }}</span>
                            <span v-if="entity.accounts_count" class="text-sm text-gray-500">
                                ({{ entity.accounts_count }} accounts)
                            </span>
                        </div>

                        <div class="flex gap-xs">
                            <SecondaryButton size="xs" @click="startEdit(entity)">
                                <PencilSquare class="w-4 h-4"/>
                            </SecondaryButton>
                            <DangerButton size="xs" @click="confirmDelete(entity)">
                                <Trash class="w-4 h-4"/>
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </Panel>
        </div>
    </div>

    <ConfirmationModal
        :show="deletingEntity !== null"
        variant="danger"
        @close="deletingEntity = null"
    >
        <template #header>
            Delete Entity
        </template>
        <template #body>
            Are you sure you want to delete <strong>{{ deletingEntity?.name }}</strong>?
            Accounts assigned to this entity will become unassigned.
        </template>
        <template #footer>
            <SecondaryButton @click="deletingEntity = null">Cancel</SecondaryButton>
            <DangerButton @click="deleteEntity" class="ml-2">Delete</DangerButton>
        </template>
    </ConfirmationModal>
</template>
