<template>
    <div class="dropdown">
        <button
            ref="button"
            type="button"
            :class="['btn', `btn-${variant}`, { 'btn-sm': sm }]"
            @click="handleToggleMenu"
        >
            <slot name="dropdown-button" />
        </button>
        <div
            ref="dropdown"
            v-if="showMenu"
            v-click-outside="onClickOutside"
            class="dropdown-menu d-block"
            aria-labelledby="dropdownMenuButton"
        >
            <slot name="dropdown-menu" />
        </div>
    </div>
</template>

<script>
import vClickOutside from 'click-outside-vue3'

export default {
    name: 'Dropdown',
    directives: {
        clickOutside: vClickOutside.directive
    },
    beforeUnmount() {
        if (this.popper) this.destroyPopper()
    },
    props: {
        variant: {
            type: String,
            required: false,
            default: 'primary',
        },
        sm: {
            type: Boolean
        }
    },
    data: function () {
        return {
            showMenu: false,
            popper: null
        }
    },
    methods: {
        initPopper: function () {
            this.popper = new window.bootstrap.Dropdown(this.$refs.button, {
                offset: [0, 5],
                popperConfig: { placement: 'bottom-start' }
            })
        },
        destroyPopper: function () {
            if (this.popper) {
                this.popper.dispose()
                this.popper = null
            }
        },
        handleToggleMenu: function () {
            this.showMenu = !this.showMenu
            if (this.showMenu) {
                this.$nextTick(() => {
                    this.initPopper()
                })
            } else if (this.popper) {
                this.destroyPopper()
            }
        },
        onClickOutside(event) {
            this.showMenu = false
            if (this.popper) this.destroyPopper()
        }
    },
}
</script>

<style scoped>
</style>
