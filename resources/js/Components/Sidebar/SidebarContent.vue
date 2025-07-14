<script setup>
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { sidebarState } from '@/Composables'
import {onMounted, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import {
    IconLayoutDashboard,
    IconUsers,
    IconUsersGroup,
    IconDatabaseDollar,
    IconCategory,
    IconCoinMonero,
    IconHistory,
    IconClockDollar,
    IconAdjustmentsDollar,
    IconTag,
    IconClipboardData,
    IconPhotoCog,
    IconChartPie,
    IconId,
    IconServerCog,
    IconSettingsDollar,
    IconCreditCardPay,
} from '@tabler/icons-vue';
import SidebarCategoryLabel from "@/Components/Sidebar/SidebarCategoryLabel.vue";

const page = usePage();
const pendingKYC = ref(page.props.pendingKYC);
const pendingSubscriberCounts = ref(page.props.pendingSubscriberCounts);

const getPendingCounts = async () => {
    try {
        const response = await axios.get('/getPendingCounts');
        pendingKYC.value = response.data.pendingKYC
        pendingSubscriberCounts.value = response.data.pendingSubscriberCounts
    } catch (error) {
        console.error('Error pending counts:', error);
    }
};

onMounted(() => {
    getPendingCounts();
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getPendingCounts();
    }
});
</script>

<template>
    <nav
        class="relative flex flex-col flex-1 max-h-full gap-1 items-center overflow-y-auto"
        :class="{
            'p-3': sidebarState.isOpen || sidebarState.isHovered,
            'px-5 py-3': !sidebarState.isOpen && !sidebarState.isHovered,
        }"
    >
        <SidebarCategoryLabel
            :title="$t('public.main_menu')"
        />

        <!-- Dashboard -->
        <SidebarLink
            :title="$t('public.dashboard')"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <IconLayoutDashboard :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <!-- Pending -->
        <SidebarCollapsible
            :title="$t('public.pending')"
            :active="route().current('pending.*')"
            :pending-counts="pendingSubscriberCounts"
        >
            <template #icon>
                <IconClockDollar :size="20" stroke-width="1.5" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.investment')"
                :href="route('pending.investment')"
                :active="route().current('pending.investment')"
                :pending-counts="pendingSubscriberCounts"
            />

            <SidebarCollapsibleItem
                :title="$t('public.withdrawal')"
                :href="route('pending.withdrawal')"
                :active="route().current('pending.withdrawal')"
            />
        </SidebarCollapsible>

        <SidebarCategoryLabel
            :title="$t('public.member_management')"
        />

        <!-- Member -->
        <SidebarCollapsible
            :title="$t('public.sidebar_member')"
            :active="route().current('member.*')"
        >
            <template #icon>
                <IconUsers :size="20" stroke-width="1.5" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_listing')"
                :href="route('member.listing')"
                :active="route().current('member.listing') || route().current('member.detail')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_kyc')"
                :href="route('member.pending_kyc')"
                :active="route().current('member.pending_kyc')"
                :pendingCounts="pendingKYC"
            />
        </SidebarCollapsible>

        <!-- Group -->
        <SidebarLink
            :title="$t('public.group')"
            :href="route('group.listing')"
            :active="route().current('group.listing')"
        >
            <template #icon>
                <IconUsersGroup :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <SidebarCategoryLabel
            :title="$t('public.trading')"
        />

        <!-- Account -->
        <SidebarLink
            :title="$t('public.accounts')"
            :href="route('account.listing')"
            :active="route().current('account.listing')"
        >
            <template #icon>
                <IconId :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <!-- Master -->
        <SidebarLink
            :title="$t('public.strategy')"
            :href="route('strategy.listing')"
            :active="route().current('strategy.listing')"
        >
            <template #icon>
                <IconCoinMonero :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <!-- Investment -->
        <SidebarLink
            :title="$t('public.investment')"
            :href="route('investment.listing')"
            :active="route().current('investment.listing')"
        >
            <template #icon>
                <IconDatabaseDollar :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <SidebarCategoryLabel
            :title="$t('public.configuration')"
        />

        <!-- Account Type -->
        <SidebarLink
            :title="$t('public.account_type')"
            :href="route('account_type.index')"
            :active="route().current('account_type.index')"
        >
            <template #icon>
                <IconServerCog :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <!-- Account Type -->
        <SidebarLink
            :title="$t('public.top_up_profile')"
            :href="route('configuration.top_up_profile')"
            :active="route().current('configuration.top_up_profile')"
        >
            <template #icon>
                <IconSettingsDollar :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <SidebarCategoryLabel
            :title="$t('public.reports')"
        />

        <SidebarCollapsible
            :title="$t('public.transaction')"
            :active="route().current('transaction.*')"
        >
            <template #icon>
                <IconCreditCardPay :size="20" stroke-width="1.5" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.top_up')"
                :href="route('report.transaction.top_up')"
                :active="route().current('report.transaction.top_up')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.withdrawal')"
                :href="route('report.transaction.withdrawal')"
                :active="route().current('report.transaction.withdrawal')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.adjustment')"
                :href="route('report.transaction.adjustment')"
                :active="route().current('report.transaction.adjustment')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.redemption')"
                :href="route('report.transaction.redemption')"
                :active="route().current('report.transaction.redemption')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.transfer')"
                :href="route('report.transaction.transfer')"
                :active="route().current('report.transaction.transfer')"
            />
        </SidebarCollapsible>

<!--        <SidebarLink-->
<!--            :title="'Accounts'"-->
<!--            :href="route('account')"-->
<!--            :active="route().current('account')"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconId :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarLink-->
<!--            :title="'Master'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconCoinMonero :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarLink-->
<!--            :title="'Structure'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconUsersGroup :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarCategoryLabel-->
<!--            :title="'Transaction'"-->
<!--        />-->

<!--        <SidebarLink-->
<!--            :title="'History'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconClockDollar :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->
    </nav>
</template>
