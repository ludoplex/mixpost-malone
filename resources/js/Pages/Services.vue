<script setup>
import {ref, defineAsyncComponent} from "vue";
import {Head, useForm} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import ProviderIcon from "@/Components/Account/ProviderIcon.vue";
import UnsplashIcon from "@/Icons/Unsplash.vue";
import TenorIcon from "@/Icons/Tenor.vue";
import DiscordIcon from "@/Icons/Discord.vue";
import TikTokIcon from "@/Icons/TikTok.vue";
import TwitchIcon from "@/Icons/Twitch.vue";
import WhatnotIcon from "@/Icons/Whatnot.vue";
import YouTubeIcon from "@/Icons/YouTube.vue";

const TwitterServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/TwitterServiceForm.vue"));
const FacebookServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/FacebookServiceForm.vue"));
const UnsplashServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/UnsplashServiceForm.vue"));
const TenorServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/TenorServiceForm.vue"));
const DiscordServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/DiscordServiceForm.vue"));
const TikTokServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/TikTokServiceForm.vue"));
const TwitchServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/TwitchServiceForm.vue"));
const WhatnotServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/WhatnotServiceForm.vue"));
const YouTubeServiceForm = defineAsyncComponent(() => import("@/Components/ServiceForm/YouTubeServiceForm.vue"));

const pageTitle = 'Third Party Services';

const props = defineProps(['services'])

const form = useForm(props.services);

const tab = ref('facebook');
</script>
<template>
    <Head :title="pageTitle"/>

    <div class="row-py w-full mx-auto">
        <PageHeader :title="pageTitle">
            <template #description>
                This page is for storing the credentials for third party services.
            </template>
        </PageHeader>

        <div class="w-full row-px mb-lg">
            <Tabs class="overflow-x-auto flex-nowrap! 2xl:flex-wrap! 2xl:gap-sm max-w-full w-full">
                <Tab @click="tab = 'facebook'" :active="tab === 'facebook'">
                    <template #icon>
                        <ProviderIcon provider="facebook"/>
                    </template>

                    Facebook
                </Tab>

                <Tab @click="tab = 'twitter'" :active="tab === 'twitter'">
                    <template #icon>
                        <ProviderIcon provider="twitter"/>
                    </template>
                    X
                </Tab>

                <Tab @click="tab = 'youtube'" :active="tab === 'youtube'">
                    <template #icon>
                        <YouTubeIcon class="text-[#FF0000]"/>
                    </template>
                    YouTube
                </Tab>

                <Tab @click="tab = 'tiktok'" :active="tab === 'tiktok'">
                    <template #icon>
                        <TikTokIcon class="text-black"/>
                    </template>
                    TikTok
                </Tab>

                <Tab @click="tab = 'twitch'" :active="tab === 'twitch'">
                    <template #icon>
                        <TwitchIcon class="text-[#9146FF]"/>
                    </template>
                    Twitch
                </Tab>

                <Tab @click="tab = 'discord'" :active="tab === 'discord'">
                    <template #icon>
                        <DiscordIcon class="text-[#5865F2]"/>
                    </template>
                    Discord
                </Tab>

                <Tab @click="tab = 'whatnot'" :active="tab === 'whatnot'">
                    <template #icon>
                        <WhatnotIcon class="text-[#7C3AED]"/>
                    </template>
                    Whatnot
                </Tab>

                <Tab @click="tab = 'unsplash'" :active="tab === 'unsplash'">
                    <template #icon>
                        <UnsplashIcon class="text-black"/>
                    </template>
                    Unsplash
                </Tab>

                <Tab @click="tab = 'tenor'" :active="tab === 'tenor'">
                    <template #icon>
                        <TenorIcon/>
                    </template>
                    Tenor
                </Tab>
            </Tabs>
        </div>

        <div class="row-px">
            <template v-if="tab === 'facebook'">
                <FacebookServiceForm :form="form.facebook"/>
            </template>

            <template v-if="tab === 'twitter'">
                <TwitterServiceForm :form="form.twitter"/>
            </template>

            <template v-if="tab === 'youtube'">
                <YouTubeServiceForm :form="form.youtube"/>
            </template>

            <template v-if="tab === 'tiktok'">
                <TikTokServiceForm :form="form.tiktok"/>
            </template>

            <template v-if="tab === 'twitch'">
                <TwitchServiceForm :form="form.twitch"/>
            </template>

            <template v-if="tab === 'discord'">
                <DiscordServiceForm :form="form.discord"/>
            </template>

            <template v-if="tab === 'whatnot'">
                <WhatnotServiceForm :form="form.whatnot"/>
            </template>

            <template v-if="tab === 'unsplash'">
                <UnsplashServiceForm :form="form.unsplash"/>
            </template>

            <template v-if="tab === 'tenor'">
                <TenorServiceForm :form="form.tenor"/>
            </template>
        </div>
    </div>
</template>
