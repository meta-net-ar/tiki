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
console.log('Schema JSON:', props.schemajson);
console.log('Tracker Field Map:', props.trackerfieldMap);
</script>

<template>
    <div>
        <h1>Schema Builder</h1>
        <SchemaBuilder :schemajson="schemajson" :trackerfieldMap="trackerfieldMap" />
    </div>
</template>
