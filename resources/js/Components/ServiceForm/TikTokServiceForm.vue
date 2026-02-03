<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import useNotifications from "@/Composables/useNotifications";
import Panel from "@/Components/Surface/Panel.vue";
import Input from "@/Components/Form/Input.vue";
import TikTokIcon from "@/Icons/TikTok.vue";
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

    router.put(route('mixpost.services.update', {service: 'tiktok'}), props.form, {
        preserveScroll: true,
        onSuccess() {
            notify('success', 'TikTok service has been saved');
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
                <span class="mr-xs"><TikTokIcon class="text-black"/></span>
                <span>TikTok</span>
            </div>
        </template>

        <template #description>
            <a href="https://developers.tiktok.com/" class="link" target="_blank">
                Create an App on TikTok for Developers</a>.
            <ReadDocHelp :href="`${$page.props.mixpost.docs_link}/services/social/tiktok`"
                         class="mt-xs"/>
        </template>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="tiktok_client_id">Client Key <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <Input v-model="form.configuration.client_id"
                   :error="errors['configuration.client_id'] !== undefined"
                   type="text"
                   id="tiktok_client_id"
                   autocomplete="off"/>

            <template #footer>
                <Error :message="errors['configuration.client_id']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="tiktok_client_secret">Client Secret <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <InputHidden v-model="form.configuration.client_secret"
                         :error="errors['configuration.client_secret'] !== undefined"
                         id="tiktok_client_secret"
                         autocomplete="new-password"/>

            <template #footer>
                <Error :message="errors['configuration.client_secret']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                Status
            </template>

            <Flex :responsive="false" class="items-center">
                <Checkbox v-model:checked="form.active" id="tiktok_active"/>
                <Label for="tiktok_active" class="mb-0!">Active</Label>
            </Flex>

            <template #footer>
                <Error :message="errors.active"/>
            </template>
        </HorizontalGroup>

        <PrimaryButton @click="save" class="mt-lg">Save</PrimaryButton>
    </Panel>
</template>
