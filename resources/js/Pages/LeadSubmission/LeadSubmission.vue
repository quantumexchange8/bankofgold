<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, h } from "vue";
import LeadSubmissionUpload from "@/Pages/LeadSubmission/Partials/LeadSubmissionUpload.vue";
import LeadSubmissionListing from "@/Pages/LeadSubmission/Partials/LeadSubmissionListing.vue";
import LeadSubmissionDuplicateListing from "@/Pages/LeadSubmission/Partials/LeadSubmissionDuplicateListing.vue";

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

const tabs = [
    {
        title: "lead_listing",
        type: "lead",
        component: LeadSubmissionListing
    },
    {
        title: "duplicate_listing",
        type: "duplicate",
        component: LeadSubmissionDuplicateListing
    }
];

const selectedTab = ref("lead");
</script>

<template>
    <AuthenticatedLayout :title="$t('public.lead_submission')">
        <div class="flex flex-col items-center gap-5 self-stretch">
            <LeadSubmissionUpload />

            <div class="w-full">
                <Tabs :value="selectedTab" @update:value="selectedTab = $event" lazy>
                    <TabList>
                        <Tab v-for="tab in tabs" :key="tab.type" :value="tab.type">
                            {{ $t(`public.${tab.title}`) }}
                        </Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel v-for="tab in tabs" :key="tab.type" :value="tab.type">
                            <!-- lazy render -->
                            <component :is="tab.component" />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
