<script setup>
import ConfirmDialog from 'primevue/confirmdialog';
import {
    Button,
} from "primevue";
import {
    SecurityIcon,
    BulbIcon,
    DeleteIcon,
    DisbandIcon,
    HideAssetMasterIcon,
    ShowAssetMasterIcon,
    PermissionIcon
} from "@/Components/Icons/brand.jsx";

import {
    SettlementIcon
} from "@/Components/Icons/solid.jsx";
</script>

<template>
    <ConfirmDialog group="headless-primary">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white rounded-3xl w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-3xl rounded-tr-3xl flex w-full p-1 justify-center [clip-path:polygon(0%_0%,_100%_0%,_100%_78%,_50%_100%,_0_78%)] bg-primary-700"
                    >
                        <div class="p-5 flex items-center justify-center">
                            <!-- Conditionally render the icon based on actionType -->
                            <BulbIcon v-if="message.actionType === 'activate_member'" class="w-16 h-16 md:w-full md:h-auto" />
                            <ShowAssetMasterIcon v-if="message.actionType === 'show_asset_master'" class="w-16 h-16 md:w-full md:h-auto" />
                            <SettlementIcon v-if="message.actionType === 'settlement'" class="w-16 h-16 md:w-full md:h-auto" />
                            <PermissionIcon v-if="message.actionType === 'permission'" class="w-16 h-16 md:w-full md:h-auto" />
                            <!-- Default icon if actionType is not specified -->
                            <BulbIcon v-if="!message.actionType" class="w-16 h-16 md:w-full md:h-auto" />
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-3 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-surface-950 text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">{{ message.message }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">
                            {{ message.text }}
                            <span class="text-surface-950 font-semibold">{{ message.dynamicText }}</span>
                            {{ message.suffix }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            type="button"
                            severity="secondary"
                            raised
                            @click="rejectCallback"
                            class="w-full"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            type="button"
                            raised
                            @click="acceptCallback"
                            class="w-full text-nowrap"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-surface">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white rounded-3xl w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-3xl rounded-tr-3xl flex w-full p-1 justify-center [clip-path:polygon(0%_0%,_100%_0%,_100%_78%,_50%_100%,_0_78%)] bg-surface-900"
                    >
                        <div class="p-5 flex items-center justify-center">
                            <PermissionIcon v-if="message.actionType === 'permission'" class="w-16 h-16 md:w-full md:h-auto" />
                            <SecurityIcon v-else class="w-16 h-16 md:w-full md:h-auto" />
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-3 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-surface-950 text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">{{ message.message }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">
                            {{ message.text }}
                            <span class="text-surface-950 font-semibold">{{ message.dynamicText }}</span>
                            {{ message.suffix }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            type="button"
                            severity="secondary"
                            raised
                            @click="rejectCallback"
                            class="w-full"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            type="button"
                            severity="secondary"
                            raised
                            @click="acceptCallback"
                            class="w-full text-nowrap"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-error">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white rounded-3xl w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-3xl rounded-tr-3xl flex w-full p-1 justify-center [clip-path:polygon(0%_0%,_100%_0%,_100%_78%,_50%_100%,_0_78%)] bg-red-600"
                    >
                        <div class="p-5 flex items-center justify-center">
                            <!-- Conditionally render the icon based on actionType -->
                            <DeleteIcon v-if="message.actionType === 'delete'" class="w-16 h-16 md:w-full md:h-auto" />
                            <DisbandIcon v-if="message.actionType === 'disband'" class="w-16 h-16 md:w-full md:h-auto" />
                            <PermissionIcon v-if="message.actionType === 'transfer_upline'" class="w-16 h-16 md:w-full md:h-auto" />
                            <!-- Default icon if actionType is not specified -->
                            <DeleteIcon v-if="!message.actionType" class="w-16 h-16 md:w-full md:h-auto" />
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-3 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-surface-950 text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">{{ message.message }}</span>
                        <span class="text-surface-700 text-xs md:text-sm">
                            {{ message.text }}
                            <span class="text-surface-950 font-semibold">{{ message.dynamicText }}</span>
                            {{ message.suffix }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            type="button"
                            severity="secondary"
                            raised
                            @click="rejectCallback"
                            class="w-full"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            type="button"
                            severity="danger"
                            raised
                            @click="acceptCallback"
                            class="w-full text-nowrap"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>