<script>
export default {
    name: 'App'
}
</script>
<script setup>
import SchemaBuilder from './components/SchemaBuilder.vue'
const props = defineProps({
    schemajson: {
        type: String,
        required: false
    },
    trackerfieldMap: {
        type: Array,
        required: false
    }
})
</script>

<template>
    <div>
        <SchemaBuilder :schemajson="schemajson" :trackerfieldMap="trackerfieldMap" />
    </div>
</template>
