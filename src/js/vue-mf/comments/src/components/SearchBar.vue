<script setup>

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
                <i class="fa fa-search" style="color: #8ca5af;"></i>
                <input type="text" class="form-control" placeholder="Type to filter/search...">
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

.search{
    position: relative;
    box-shadow: 0 0 40px rgba(51, 51, 51, .1);
}
.search input, .search input:focus{
    height: 40px;
    text-indent: 25px;
    box-shadow: inset 3px 4px 5px rgba(0, 0, 0, 0.2);
}

.search input::placeholder {
    color: #8ca5af;
    opacity: 1; /* Firefox */
}

.search input::-ms-input-placeholder { /* Edge 12-18 */
    color: #8ca5af;
}

.search .fa-search{
    position: absolute;
    top: 14px;
    left: 16px;
}
</style>