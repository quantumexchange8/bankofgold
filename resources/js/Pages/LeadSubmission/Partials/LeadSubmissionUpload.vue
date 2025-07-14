<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const fileInput = ref(null);
const selectedFileName = ref(null);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileUpload = (event) => {
    const target = event.target;
    const file = target.files?.[0];
    if (!file) return;

    selectedFileName.value = file.name;

    const formData = new FormData();
    formData.append('file', file);

    router.post(route('lead_submission.upload'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            // console.log('File uploaded!');
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};
</script>

<template>
    <!-- 👇 File Upload Area -->
    <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('public.upload_excel_file') }} (.csv, .xls, .xlsx, .ods)
        </label>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept=".csv,.xls,.xlsx,.ods"
            class="hidden"
            @change="handleFileUpload"
        />

        <!-- Trigger button -->
        <button
            type="button"
            @click="triggerFileInput"
            class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark"
        >
            {{ $t('public.upload_file') }}
        </button>

        <!-- Show selected file name -->
        <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('public.selected_file') }}: <span class="font-medium">{{ selectedFileName }}</span>
        </span>
    </div>
</template>
