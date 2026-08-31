<script setup>
import useCommentsUiStore from "../store/commentsUi";

const store = useCommentsUiStore();
</script>
<script>
export default {
    name: 'PinnedSearchBar',
};
</script>

<template>
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="search">
                <i class="fa fa-search" style="color: #8ca5af;z-index: 9999;"></i>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Type to filter pinned messages..."
                    :value="store.pinnedSearchQuery"
                    @input="store.setPinnedSearchQuery($event.target.value)"
                >
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
