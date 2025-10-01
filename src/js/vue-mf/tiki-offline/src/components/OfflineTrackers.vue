<template>
    <div class="offline-trackers">
        <h2>Offline tracker items</h2>
        <OfflineTracker
            v-for="(tracker, index) in trackers"
            :tracker="tracker"
            :index="index"
        >
            <FormAddTrackerItem :formFields=tracker.fields :trackerId=tracker.trackerId />
            <!--Todo: Add table style in @vue-mf/styleguide-->
            <div class="table-responsive large-table-no-wrap">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th v-for="field in visibleFields(tracker.fields)">{{ field.name }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in tracker.items">
                            <td v-for="field in visibleFields(tracker.fields)">{{ renderFieldOutput(item, field) }}</td>
                            <td>
                                <a href="#" @click.prevent="handleEditItem(tracker.trackerId, index)"><div class="iconmenu"><span class="icon icon-edit fa-fw fas fa-edit"></span><span class="iconmenutext"> Edit</span></div></a>
                                <a href="#" @click.prevent="handleDeleteItem(tracker.trackerId, index)"><div class="iconmenu"><span class="icon icon-delete fa-fw fas fa-times"></span><span class="iconmenutext"> Delete</span></div></a>
                            </td>
                        </tr> 
                    </tbody>
                </table>
            </div>
        </OfflineTracker>
    </div>
</template>

<script>
    import { inject, toRaw } from 'vue'
    import store from '../store'
    import OfflineTracker from "./OfflineTracker.vue"
    import FormAddTrackerItem from "./Forms/FormAddTrackerItem.vue"
    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker,
            FormAddTrackerItem,
        },
        computed: {
            trackers () {
                return store.getters.getOfflineTrackers()
            },
        },
        mounted() {
            if (jqueryTiki.jquery_timeago) {
                $("time.timeago").tikiTimeago()
            }
        },
        setup() {
            const dataSync = inject('dataSync')
            return { dataSync }
        },
        methods: {
            visibleFields (fields) {
                return fields.filter(field => field.isTblVisible == 'y')
            },
            handleEditItem (trackerId, index) {
                store.commit('changeEditedItem', {trackerId: trackerId, itemId: index})
                $('#formAddTrackerItemModal' + trackerId).modal('show')
            },
            handleDeleteItem (trackerId, index) {
                $('body').confirmationDialog({
                    title: tr("Delete this item?"),
                    message: tr('Please confirm you want to remove this item from the temporary local storage.'),
                    success: () => {
                        try {
                            store.commit('deleteItem', {trackerId: trackerId, itemId: index})
                            this.dataSync(toRaw(store.getters.getOfflineTrackers()))
                        } catch (err) {
                            $.pwaFeedback(err, 'error', 'Error')
                        }
                    }
                })
            },
            renderFieldOutput (item, field) {
                return store.getters.renderFieldOutput(item, field);
            }
        },
    }
</script>
