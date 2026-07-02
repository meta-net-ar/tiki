<script setup lang="ts">
import { ref } from "vue";
import KeyDashboardPage from "./components/KeyDashboardPage.vue";
import AdminKeyCreationPage from "./components/AdminKeyCreationPage.vue";
import KeyShareManagementPage from "./components/KeyShareManagementPage.vue";

type View = "dashboard" | "create" | "edit" | "shares";

const currentView = ref<View>("dashboard");
const selectedKeyId = ref<number | null>(null);
const selectedKeyName = ref("");

function navigate(view: View, keyId?: number, keyName?: string) {
    currentView.value = view;
    selectedKeyId.value = keyId ?? null;
    selectedKeyName.value = keyName ?? "";
}
</script>

<template>
    <KeyDashboardPage
        v-if="currentView === 'dashboard'"
        @create="navigate('create')"
        @edit="(id, name) => navigate('edit', id, name)"
        @shares="(id, name) => navigate('shares', id, name)"
    />
    <AdminKeyCreationPage v-else-if="currentView === 'create'" mode="create" @back="navigate('dashboard')" @saved="navigate('dashboard')" />
    <AdminKeyCreationPage
        v-else-if="currentView === 'edit'"
        mode="edit"
        :key-id="selectedKeyId"
        :key-name="selectedKeyName"
        @back="navigate('dashboard')"
        @saved="navigate('dashboard')"
    />
    <KeyShareManagementPage v-else-if="currentView === 'shares'" :key-id="selectedKeyId" :key-name="selectedKeyName" @back="navigate('dashboard')" />
</template>
