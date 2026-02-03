<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import useNotifications from "@/Composables/useNotifications";
import Panel from "@/Components/Surface/Panel.vue";
import Input from "@/Components/Form/Input.vue";
import DiscordIcon from "@/Icons/Discord.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import HorizontalGroup from "@/Components/Layout/HorizontalGroup.vue";
import Error from "@/Components/Form/Error.vue";
import ReadDocHelp from "@/Components/Util/ReadDocHelp.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import Label from "@/Components/Form/Label.vue";
import InputHidden from "@/Components/Form/InputHidden.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";

const props = defineProps({
    form: {
        required: true,
        type: Object
    }
})

const {notify} = useNotifications();
const errors = ref({});

const save = () => {
    errors.value = {};

    router.put(route('mixpost.services.update', {service: 'discord'}), props.form, {
        preserveScroll: true,
        onSuccess() {
            notify('success', 'Discord service has been saved');
        },
        onError: (err) => {
            errors.value = err;
        },
    });
}
</script>
<template>
    <Panel>
        <template #title>
            <div class="flex items-center">
                <span class="mr-xs"><DiscordIcon class="text-[#5865F2]"/></span>
                <span>Discord</span>
            </div>
        </template>

        <template #description>
            <a href="https://discord.com/developers/applications" class="link" target="_blank">
                Create an App on Discord Developer Portal</a>.
            <ReadDocHelp :href="`${$page.props.mixpost.docs_link}/services/social/discord`"
                         class="mt-xs"/>
        </template>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="discord_client_id">Application ID <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <Input v-model="form.configuration.client_id"
                   :error="errors['configuration.client_id'] !== undefined"
                   type="text"
                   id="discord_client_id"
                   autocomplete="off"/>

            <template #footer>
                <Error :message="errors['configuration.client_id']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="discord_client_secret">Client Secret <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <InputHidden v-model="form.configuration.client_secret"
                         :error="errors['configuration.client_secret'] !== undefined"
                         id="discord_client_secret"
                         autocomplete="new-password"/>

            <template #footer>
                <Error :message="errors['configuration.client_secret']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="discord_bot_token">Bot Token <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <InputHidden v-model="form.configuration.bot_token"
                         :error="errors['configuration.bot_token'] !== undefined"
                         id="discord_bot_token"
                         autocomplete="new-password"/>

            <template #footer>
                <Error :message="errors['configuration.bot_token']"/>
                <p class="text-xs text-gray-500 mt-xs">Required for posting messages to channels</p>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                Status
            </template>

            <Flex :responsive="false" class="items-center">
                <Checkbox v-model:checked="form.active" id="discord_active"/>
                <Label for="discord_active" class="mb-0!">Active</Label>
            </Flex>

            <template #footer>
                <Error :message="errors.active"/>
            </template>
        </HorizontalGroup>

        <PrimaryButton @click="save" class="mt-lg">Save</PrimaryButton>
    </Panel>
</template>
