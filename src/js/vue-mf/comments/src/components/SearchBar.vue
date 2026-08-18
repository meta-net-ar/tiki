<script setup>
import { nextTick, watch } from "vue";
import useMessageStore from "../store";

const store = useMessageStore();

watch(
    () => store.searchQuery,
    async () => {
        await nextTick();
        store.drawMessagesTree();
    }
);

function scrollToFirstMatch() {
    document.querySelector(".message-body.search-match")?.scrollIntoView({ behavior: "smooth", block: "center" });
}
</script>
<script>
export default {
    name: 'SearchBar',
};
</script>

<template>
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="search">
                <i class="fa fa-search" style="color: #8ca5af;z-index: 9999;"></i>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Type to filter/search..."
                    :value="store.searchQuery"
                    @input="store.setSearchQuery($event.target.value)"
                    @blur="scrollToFirstMatch"
                    @keyup.enter="scrollToFirstMatch"
                >
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>