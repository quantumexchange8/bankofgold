<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import InputError from '@/Components/InputError.vue';

const fileInput = ref(null)
const selectedFileName = ref(null)

const form = useForm({
    file: null, // will be filled by user
})

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileUpload = (event) => {
    const file = event.target.files?.[0]
    if (!file) return

    form.file = file
    selectedFileName.value = file.name

    form.post(route('lead_submission.upload'), {
        preserveScroll: true,
        forceFormData: true, // 👈 important for file upload
        onSuccess: () => {
            form.reset()
            // selectedFileName.value = null
            // fileInput.value.value = null // 👈 reset file input
        },
    })
}
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

        <!-- Server-side validation errors -->
        <InputError :message="form.errors.file" />
    </div>
</template>
