<script setup>
import {ref, computed} from "vue";
import {router, usePage} from '@inertiajs/vue3'
import {Head} from '@inertiajs/vue3';
import useNotifications from "@/Composables/useNotifications";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Modal from "@/Components/Modal/Modal.vue"
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue"
import Account from "@/Components/Account/Account.vue"
import AddTwitterAccount from "@/Components/Account/AddTwitterAccount.vue"
import AddFacebookPage from "@/Components/Account/AddFacebookPage.vue"
import AddMastodonAccount from "@/Components/Account/AddMastodonAccount.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import DangerButton from "@/Components/Button/DangerButton.vue"
import Dropdown from "@/Components/Dropdown/Dropdown.vue"
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue"
import PlusIcon from "@/Icons/Plus.vue";
import EllipsisVerticalIcon from "@/Icons/EllipsisVertical.vue";
import RefreshIcon from "@/Icons/Refresh.vue";
import TrashIcon from "@/Icons/Trash.vue";
import TagIcon from "@/Icons/Tag.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import AlertUnconfiguredService from "@/Components/Service/AlertUnconfiguredService.vue";
import EntityBadge from "@/Components/Entity/EntityBadge.vue";
import EntitySelect from "@/Components/Entity/EntitySelect.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

const title = 'Social Accounts';
const page = usePage();
const {notify} = useNotifications();

const addAccountModal = ref(false);
const confirmationAccountDeletion = ref(null);
const accountIsDeleting = ref(false);
const entityAssignModal = ref(null);
const selectedEntityId = ref(null);
const filterEntityId = ref(null);

// Filter accounts by entity
const filteredAccounts = computed(() => {
    const accounts = page.props.accounts || [];
    if (filterEntityId.value === null) {
        return accounts;
    }
    if (filterEntityId.value === 'unassigned') {
        return accounts.filter(a => !a.entity_id);
    }
    return accounts.filter(a => a.entity_id === filterEntityId.value);
});

const updateAccount = (accountId) => {
    router.put(route('mixpost.accounts.update', {account: accountId}), {}, {
        preserveScroll: true,
        onSuccess(response) {
            if (response.props.flash.error) {
                return;
            }
            notify('success', 'The account has been refreshed');
        }
    });
}

const deleteAccount = () => {
    router.delete(route('mixpost.accounts.delete', {account: confirmationAccountDeletion.value}), {
        preserveScroll: true,
        onStart() {
            accountIsDeleting.value = true;
        },
        onSuccess() {
            confirmationAccountDeletion.value = null;
            notify('success', 'Account deleted');
        },
        onFinish() {
            accountIsDeleting.value = false;
        },
    });
}

const closeConfirmationAccountDeletion = () => {
    if (accountIsDeleting.value) {
        return;
    }
    confirmationAccountDeletion.value = null
}

const openEntityAssign = (account) => {
    entityAssignModal.value = account;
    selectedEntityId.value = account.entity_id;
}

const assignEntity = () => {
    router.put(route('mixpost.accounts.update', {account: entityAssignModal.value.uuid}), {
        entity_id: selectedEntityId.value
    }, {
        preserveScroll: true,
        onSuccess() {
            entityAssignModal.value = null;
            notify('success', 'Entity assigned');
        }
    });
}
</script>

<template>
    <Head :title="title"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="title">
            <template #description>
                Connect a social account you'd like to manage.
            </template>
        </PageHeader>

        <div class="mt-lg row-px w-full">
            <AlertUnconfiguredService
                :isConfigured="$page.props.is_configured_service"
            />

            <!-- Entity Filter -->
            <div v-if="$page.props.entities?.length" class="mb-md flex items-center gap-sm flex-wrap">
                <span class="text-sm text-gray-600">Filter by entity:</span>
                <button
                    @click="filterEntityId = null"
                    class="px-3 py-1 rounded-full text-sm transition-colors"
                    :class="filterEntityId === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                >
                    All
                </button>
                <button
                    v-for="entity in $page.props.entities"
                    :key="entity.id"
                    @click="filterEntityId = entity.id"
                    class="px-3 py-1 rounded-full text-sm transition-colors"
                    :class="filterEntityId === entity.id ? 'text-white' : 'hover:opacity-80'"
                    :style="{ 
                        backgroundColor: filterEntityId === entity.id ? entity.hex_color : entity.hex_color + '20',
                        color: filterEntityId === entity.id ? 'white' : entity.hex_color
                    }"
                >
                    {{ entity.name }}
                </button>
                <button
                    @click="filterEntityId = 'unassigned'"
                    class="px-3 py-1 rounded-full text-sm transition-colors"
                    :class="filterEntityId === 'unassigned' ? 'bg-gray-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                >
                    Unassigned
                </button>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                <button @click="addAccountModal = true"
                        class="border border-indigo-800 rounded-lg hover:border-indigo-500 hover:text-indigo-500 transition-colors ease-in-out duration-200">
                    <span class="block p-lg">
                        <span class="flex flex-col justify-center items-center">
                            <PlusIcon class="w-7 h-7"/>
                            <span class="mt-xs text-lg">Add account</span>
                        </span>
                    </span>
                </button>

                <template v-for="account in (filterEntityId !== null ? filteredAccounts : $page.props.accounts)" :key="account.id">
                    <Panel class="relative">
                        <div class="absolute top-0 right-0 mt-sm mr-sm">
                            <Dropdown width-classes="w-36">
                                <template #trigger>
                                    <PureButton>
                                        <EllipsisVerticalIcon/>
                                    </PureButton>
                                </template>

                                <template #content>
                                    <DropdownItem @click="openEntityAssign(account)" as="button">
                                        <TagIcon class="w-5! h-5! mr-1"/>
                                        Set Entity
                                    </DropdownItem>
                                    <DropdownItem @click="updateAccount(account.uuid)" as="button">
                                        <RefreshIcon class="w-5! h-5! mr-1"/>
                                        Refresh
                                    </DropdownItem>
                                    <DropdownItem @click="confirmationAccountDeletion = account.uuid" as="button">
                                        <TrashIcon class="w-5! h-5! mr-1 text-red-500"/>
                                        Delete
                                    </DropdownItem>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="flex flex-col justify-center">
                            <Account
                                size="lg"
                                :img-url="account.image"
                                :provider="account.provider"
                                :active="true"
                            />
                            <div
                                v-if="!account.authorized"
                                class="absolute top-0 left-0"
                            >
                                <div
                                    v-tooltip="'Unauthorized'"
                                    class="w-md h-md bg-red-500 rounded-full"
                                ></div>
                            </div>
                            <div class="mt-sm font-medium text-center break-words">{{ account.name }}</div>
                            <div class="mt-1 text-center">
                                <EntityBadge :entity="account.entity" size="sm"/>
                            </div>
                            <div class="mt-1 text-center text-stone-800 text-xs">Added: {{ account.created_at }}</div>
                        </div>
                    </Panel>
                </template>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal :show="confirmationAccountDeletion !== null"
                       @close="closeConfirmationAccountDeletion"
                       variant="danger">
        <template #header>
            Delete account
        </template>
        <template #body>
            Are you sure you want to delete this account?
        </template>
        <template #footer>
            <SecondaryButton @click="closeConfirmationAccountDeletion" :disabled="accountIsDeleting"
                             class="mr-xs">Cancel
            </SecondaryButton>
            <DangerButton @click="deleteAccount" :is-loading="accountIsDeleting"
                          :disabled="accountIsDeleting">Delete
            </DangerButton>
        </template>
    </ConfirmationModal>

    <!-- Entity Assignment Modal -->
    <Modal :show="entityAssignModal !== null" @close="entityAssignModal = null">
        <div class="p-lg">
            <h3 class="text-lg font-medium mb-md">Assign Entity</h3>
            <p class="text-sm text-gray-600 mb-md">
                Assign <strong>{{ entityAssignModal?.name }}</strong> to an entity/brand.
            </p>
            
            <div class="mb-md">
                <EntitySelect 
                    v-model="selectedEntityId" 
                    :entities="$page.props.entities || []"
                    placeholder="No entity (unassigned)"
                />
            </div>
            
            <div class="flex justify-end gap-xs">
                <SecondaryButton @click="entityAssignModal = null">Cancel</SecondaryButton>
                <DangerButton v-if="selectedEntityId" @click="selectedEntityId = null" class="mr-auto">
                    Remove Entity
                </DangerButton>
                <PrimaryButton @click="assignEntity">Save</PrimaryButton>
            </div>
        </div>
    </Modal>

    <!-- Add Account Modal -->
    <Modal :show="addAccountModal"
           :closeable="true"
           @close="addAccountModal = false">
        <div class="flex flex-col">
            <AddFacebookPage v-if="$page.props.is_service_active.facebook"/>
            <AddMastodonAccount/>
            <AddTwitterAccount v-if="$page.props.is_service_active.twitter"/>
            <!-- New providers will be added here as components are created -->
        </div>
    </Modal>
</template>
