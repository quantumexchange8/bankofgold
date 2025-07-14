<script setup>
import {onMounted, ref, watch, watchEffect} from "vue";
import {CalendarIcon, ClockRewindIcon} from "@/Components/Icons/outline.jsx";
import { generalFormat } from "@/Composables/format.js";
import debounce from "lodash/debounce.js";
import { usePage, router} from "@inertiajs/vue3";
import dayjs from "dayjs";
import {
    IconCircleXFilled,
    IconSearch,
    IconX,
    IconAdjustments,
    IconCloudDownload,
} from "@tabler/icons-vue";
import Empty from "@/Components/Empty.vue";
import {
    Button,
    Column,
    DataTable,
    Tag,
    InputText,
    Select,
    MultiSelect,
    DatePicker,
    ColumnGroup,
    Row,
    ProgressSpinner,
    Popover,
    RadioButton,
    Dialog,
    IconField,
} from "primevue";
import { FilterMatchMode } from '@primevue/core/api';

const exportStatus = ref(false);
const isLoading = ref(false);
const dt = ref(null);
const files = ref();
const selectedFiles = ref();
const { formatRgbaColor, formatAmount, formatDateTime, formatNameLabel } = generalFormat();
const totalRecords = ref(0);
const first = ref(0);
const visible = ref(false);

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
  end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const initialLoaded = ref(true);

watch(filters, debounce(() => {
    if (initialLoaded.value) {
        initialLoaded.value = false;
        return;
    }

    const f = filters.value;

    const start = f.start_date.value;
    const end = f.end_date.value;

    const validDateRange = (start !== null && end !== null);

    if (!validDateRange) return;

    first.value = 0;
    loadLazyData();
}, 300), { deep: true });

const abortController = ref(null);
const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    // Abort previous request
    if (abortController.value) {
        abortController.value.abort();
    }

    // Create new controller
    abortController.value = new AbortController();

    lazyParams.value = {
        ...lazyParams.value,
        first: event?.first || first.value,
        filters: filters.value,
    };

    setTimeout(async () => {
        try {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('lead_submission.getCoreLeads', params);

            const response = await fetch(url, {
                signal: abortController.value.signal,
            });

            const results = await response.json();
            files.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log('Previous request aborted');
            } else {
                console.error('Fetch error:', error);
                files.value = [];
                totalRecords.value = 0;
            }
        } finally {
            isLoading.value = false;
        }
    }, 100);
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onFilter = (event) => {
    lazyParams.value.filters = filters.value;
    loadLazyData(event);
};

// Optimized exportReport function
const exportReport = async () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    const selectedIds = selectedFiles.value.map(core_lead => core_lead.id);

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
        selected_ids: selectedIds.length ? selectedIds : null, // only send if not empty
    };

    const url = route('lead_submission.getCoreLeads', params);

    try {
        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
    }
};

onMounted(() => {
    // Ensure filters are populated before fetching data
    if (Array.isArray(selectedDate.value)) {
        const [startDate, endDate] = selectedDate.value;
        if (startDate && endDate) {
            filters.value['start_date'].value = startDate;
            filters.value['end_date'].value = endDate;
        }
    }

    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
}

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watch(
    () => usePage().props.toast,
    (toast) => {
        if (toast !== null) {
            first.value = 0;
            loadLazyData();
        }
    }
);

// Get current date
const today = new Date();

// Define minDate as the start of the current month and maxDate as today
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);

const clearDate = () => {
    selectedDate.value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
}

// Watch for changes in selectedDate
watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        const normalizedStart = new Date(startDate);
        normalizedStart.setHours(0, 0, 0, 0);

        const normalizedEnd = new Date(endDate);
        normalizedEnd.setHours(23, 59, 59, 999);

        filters.value['start_date'].value = startDate ? normalizedStart : null;
        filters.value['end_date'].value = endDate ? normalizedEnd : null;

        if (startDate !== null && endDate !== null) {
            // loadLazyData();
        }
    }
    else {
        // console.warn('Invalid date range format:', newDateRange);
    }
})

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
        end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    selectedDate.value = [minDate.value, maxDate.value];
};

const sendEmails = () => {
  const coreLeads = selectedFiles.value.map(core_lead => core_lead.id);

  router.post('/lead_submission/sendEmails', { core_leads: coreLeads }, {
    preserveScroll: true,
    onSuccess: () => {
    //   console.log(usePage().props); // Now this will include toast!
    },
    onError: (errors) => {
      console.error('Validation failed:', errors);
    }
  });
};

// // dialog
// const data = ref({});
// const openDialog = (rowData) => {
//     visible.value = true;
//     data.value = rowData;
//     fetchLeadItems(rowData.id);
// };

// const leadItems = ref([]);
// const totalAmount = ref(0);

// const fetchLeadItems = async (leadId) => {
//     isLoading.value = true;

//     const itemLazyParams = {
//         ...lazyParams.value,
//         filters: filters.value,
//         lead_id: leadId,
//     };

//     try {
//         const params = {
//             lazyEvent: JSON.stringify(itemLazyParams),
//         };

//         const url = route('lead.getLeadItems', params);
//         const response = await fetch(url);
//         const result = await response.json();

//         leadItems.value = result?.data || [];
//         totalAmount.value = leadItems.value.reduce((sum, item) => sum + parseFloat(item.amount), 0);
//     } catch (e) {
//         console.error('Error fetching lead items:', e);
//         leadItems.value = [];
//     } finally {
//         isLoading.value = false;
//     }
// };

</script>

<template>
    <div class="flex flex-col items-center px-4 py-6 gap-5 self-stretch rounded-2xl border border-gray-200 bg-white shadow-table md:px-6 md:gap-5">
        <div
            class="w-full"
        >
            <!-- <DataTable
                v-model:selection="selectedFiles"
                :value="files"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                lazy
                :paginator="files?.length > 0"
                removableSort
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :first="first"
                :page="page"
                :rows="10"
                ref="dt"
                dataKey="id"
                selectionMode="multiple"
                @row-click="(event) => openDialog(event.data)"
                :totalRecords="totalRecords"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="onSort($event)"
                @filter="onFilter($event)"
                :globalFilterFields="['email']"
            > -->
            <DataTable
                v-model:first="first"
                v-model:filters="filters"
                v-model:selection="selectedFiles"
                :value="files"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                lazy
                :paginator="files?.length > 0"
                removableSort
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :rows="10"
                ref="dt"
                dataKey="id"
                selectionMode="multiple"
                :totalRecords="totalRecords"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="onSort($event)"
                @filter="onFilter($event)"
                :globalFilterFields="['email']"
            >
                <template #header>
                    <div class="flex flex-col md:flex-row gap-3 items-center self-stretch pb-3 md:pb-5">
                        <div class="relative w-full md:w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                                <IconSearch size="20" stroke-width="1.25" />
                            </div>
                            <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                            <div
                                v-if="filters['global'].value !== null && filters['global'].value !== ''"
                                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                @click="clearFilterGlobal"
                            >
                                <IconCircleXFilled size="16" />
                            </div>
                        </div>
                        <div class="w-full flex flex-col gap-3 md:flex-row">
                            <div class="w-full md:w-[272px]">
                                <!-- <DatePicker
                                    v-model="selectedDate"
                                    selectionMode="range"
                                    :manualInput="false"
                                    :minDate="minDate"
                                    :maxDate="maxDate"
                                    dateFormat="dd/mm/yy"
                                    showIcon
                                    iconDisplay="input"
                                    placeholder="yyyy/mm/dd - yyyy/mm/dd"
                                    class="w-full md:w-[272px]"
                                />
                                <div
                                    v-if="selectedDate && selectedDate.length > 0"
                                    class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                                    @click="clearDate"
                                >
                                    <IconX size="20" />
                                </div> -->
                                <Button
                                    type="button"
                                    severity="secondary"
                                    rounded
                                    @click="toggle"
                                    class="flex gap-3 items-center justify-center py-3 w-full md:w-[130px]"
                                >
                                    <IconAdjustments size="20" color="#0C111D" stroke-width="1.25" />
                                    <div class="text-sm text-gray-950 font-medium">
                                        {{ $t('public.filter') }}
                                    </div>
                                </Button>
                            </div>
                           <div class="w-full flex flex-col md:flex-row justify-end gap-2">
                                <!-- <Button
                                    v-if="selectedFiles?.length > 0"
                                    variant="primary-flat"
                                    :disabled="selectedFiles?.length === 0"
                                    @click="sendEmails()"
                                >
                                    {{ $t('public.send_email') }}
                                </Button> -->

                               <Button
                                   variant="primary-outlined"
                                   @click="exportReport"
                                   class="w-full md:w-auto"
                               >
                                   {{ $t('public.export') }}
                               </Button>
                           </div>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <Empty
                        :title="$t('public.empty_lead_title')"
                        :message="$t('public.empty_lead_message')"
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <ProgressSpinner strokeWidth="4" />
                        <span class="text-sm text-gray-700">{{ $t('public.loading_leads_data_caption') }}</span>
                    </div>
                </template>
                <template v-if="files?.length > 0">
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                    <!-- <Column
                        field="created_at"
                        sortable
                        :header="`${$t('public.date')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column> -->
                    <Column
                        field="date_added"
                        sortable
                        :header="`${$t('public.date_added')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.date_added).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column
                        field="lead_id"
                        sortable
                        :header="`${$t('public.lead_id')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.lead_id }}
                        </template>
                    </Column>
                    <Column
                        field="categories"
                        sortable
                        :header="`${$t('public.categories')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.categories }}
                        </template>
                    </Column>
                    <Column
                        field="first_name"
                        sortable
                        :header="$t('public.name')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-3 max-w-60">
                                <div class="flex flex-col items-start truncate">
                                    <div class="font-medium">
                                        {{ `${data.first_name} ${data.surname}` }}
                                    </div>
                                    <!-- <div class="text-surface-500 text-xs max-w-48 truncate">
                                        {{ data.email }}
                                    </div> -->
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="email"
                        sortable
                        :header="`${$t('public.email')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.email }}
                        </template>
                    </Column>
                    <Column
                        field="telephone"
                        sortable
                        :header="`${$t('public.telephone')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.telephone }}
                        </template>
                    </Column>
                    <Column
                        field="country"
                        sortable
                        :header="`${$t('public.country')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.country }}
                        </template>
                    </Column>
                    <Column
                        field="referrer"
                        sortable
                        :header="`${$t('public.referrer')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.referrer }}
                        </template>
                    </Column>

                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-start">
                                        <div class="flex flex-wrap items-start gap-x-2">
                                            <div class="text-sm font-semibold w-auto">
                                                {{ slotProps.data.lead_id }}
                                            </div>
                                            <div class="text-sm font-semibold w-auto">
                                                {{ slotProps.data.email }}
                                            </div>
                                            <div class="text-sm font-semibold w-auto">
                                                {{ slotProps.data.telephone }}
                                            </div>
                                        </div>

                                        <!-- <div class="text-gray-500 text-xs">
                                            {{ `${$t('public.date')}: ${dayjs(slotProps.data.created_at).format('YYYY/MM/DD')}` }}
                                        </div> -->
                                        <div class="text-gray-500 text-xs">
                                            {{ `${$t('public.date_added')}: ${dayjs(slotProps.data.date_added).format('YYYY/MM/DD')}` }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-8 w-72 py-5 px-4">
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="flex flex-col relative gap-1 self-stretch">
                    <DatePicker
                        v-model="selectedDate"
                        selectionMode="range"
                        :manualInput="false"
                        :maxDate="maxDate"
                        dateFormat="dd/mm/yy"
                        showIcon
                        iconDisplay="input"
                        placeholder="yyyy/mm/dd - yyyy/mm/dd"
                        class="w-full md:w-[272px]"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2.5 right-3 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearDate"
                    >
                        <IconX size="20" />
                    </div>
                </div>
            </div>

            <div class="flex w-full">
                <Button
                    type="button"
                    variant="primary-outlined"
                    class="flex justify-center w-full"
                    @click="clearFilter()"
                >
                    {{ $t('public.clear_all') }}
                </Button>
            </div>
        </div>
    </Popover>

    <!-- <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.lead')"
        class="dialog-xs md:dialog-lg lg:w-auto"
    >
        <div class="w-full flex flex-col gap-5">
            <div class="w-full flex flex-col gap-2">
                <div class="w-full flex flex-col md:flex-row gap-5">
                    <div class="w-full flex justify-start gap-1">
                        <span class="whitespace-nowrap py-2">{{ $t('public.bill_to') }}:</span>
                        <div class="flex flex-col px-3 py-2 text-gray-700">
                            <span class="font-bold">{{ data?.company_name }}</span>
                            <span class="uppercase">{{ [data?.address1, data?.address2].filter(Boolean).join(', ') }},</span>
                            <span class="uppercase">{{ [data?.postcode, data?.city, data?.state, data?.country].filter(Boolean).join(', ') }}.</span>
                            <span class="uppercase">{{ $t('public.tel') }}: {{ data?.phone }}</span>
                        </div>
                    </div>

                    <div class="w-full flex md:justify-end gap-1">
                        <div class="w-full md:w-auto flex-col px-3 py-2 text-gray-700">
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.no') }}.</span>
                                <span class="whitespace-nowrap font-bold">{{ data?.doc_no }}</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.date') }}</span>
                                <span class="whitespace-nowrap">{{ data?.doc_date }}</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.terms') }}</span>
                                <span class="whitespace-nowrap">{{ data?.terms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full flex flex-col gap-1">
                <div class="w-full flex flex-col border-y border-gray-300">
                    <DataTable
                        :value="leadItems"
                        dataKey="id"
                        removable-sort
                    >
                        <Column :header="'#'" class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ slotProps.index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" :header="$t('public.item_code')" class="whitespace-nowrap"/>
                        <Column field="description_dtl" :header="$t('public.description')" class="whitespace-nowrap"/>
                        <Column field="qty" :header="$t('public.qty')" class="whitespace-nowrap uppercase"/>
                        <Column field="uom" :header="$t('public.uom')" class="whitespace-nowrap uppercase"/>
                        <Column field="unit_price" :header="$t('public.unit_price') + ' ($)'" sortable class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.unit_price) }}
                            </template>
                        </Column>
                        <Column field="amount" :header="$t('public.amount') + ' ($)'" sortable class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.amount) }}
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <div class="w-full flex justify-end items-center gap-1">
                    <span class="text-gray-700 font-bold">{{ $t('public.total_amount') }}:</span>
                    <span class="text-gray-700 font-bold">$ {{ formatAmount(totalAmount) }}</span>
                </div>
            </div>

        </div>
    </Dialog> -->

</template>
