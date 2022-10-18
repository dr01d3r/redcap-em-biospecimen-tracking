<template>
    <b-overlay variant="light"
               blur="50px"
               spinner-variant="dark"
               spinner-small
               opacity="0.95"
               :show="isOverlayed"
               rounded="sm">
        <template #overlay>
            <loader />
        </template>

        <div class="projhdr">
            <i class="fas fa-vials text-dark"></i>&nbsp;Reporting Dashboard
        </div>

        <template v-if="!isObjectEmpty(errors)">
            <div class="alert alert-danger p-4">
                <h1 class="display-4">Critical Errors Exist!</h1>
                <p class="lead mb-0">This dashboard has been disabled until all critical errors have been resolved.</p>
                <hr/>
                <ul>
                    <template v-for="(v, k) in errors">
                        <li v-html="v"></li>
                    </template>
                </ul>
            </div>
        </template>

        <!-- MAIN CONTENT AREA -->
        <b-pagination
                v-model="currentPage"
                :total-rows="rows"
                :per-page="perPage"
                aria-controls="specimen-search"
        ></b-pagination>
        <b-table
                id="specimen-search"
                :items="sortedSpecimens"
                :fields="specimenFields"
                :per-page="perPage"
                :current-page="currentPage"
                responsive
        ></b-table>

        <template v-if="debugMsg != null">
            <pre class="well">{{ debugOutput }}</pre>
        </template>
    </b-overlay>
</template>

<script>
    // Loader
    import loader from '../loader.vue'

    export default {
        components: {
            loader
        },
        data() {
            return {
                config: {},
                errors: {},
                debugMsg: null,
                isOverlayed: false,
                specimens: [],
                perPage: 50,
                currentPage: 1,
            }
        },
        methods: {
            async initializeDashboard() {
                this.isOverlayed = true;
                this.axios({
                    url: OrcaBiospecimenTracking().url,
                    params: {
                        action: 'initialize-report-dashboard'
                    }
                })
                .then(response => {
                    this.config = response.data.config;
                    this.specimens = response.data.specimens ?? [];
                    // debug
                    // this.debugMsg = response;
                })
                .catch(e => {
                    let errorMsg = 'An unknown error occurred';
                    if (e.response.data) {
                        errorMsg = e.response.data;
                    }
                    if (typeof errorMsg === 'string') {
                        errorMsg = [ errorMsg ];
                    }
                    this.errors = Object.assign(this.errors, errorMsg);
                })
                .finally(() => {
                    setTimeout(() => {
                        this.isOverlayed = false;
                    }, 250);
                });
            }
        },
        computed: {
            debugOutput: function() {
                return JSON.stringify(this.debugMsg, null, '\t');
            },
            sortedSpecimens: function() {
                if (this.specimens.sort) {
                    return this.specimens.sort((a, b) => {
                        if (a.name < b.name) { return -1; }
                        if (a.name > b.name) { return 1; }
                        return 0;
                    });
                }
                return this.specimens;
            },
            specimenFields: function() {
                if (this.config && this.config.specimen_fields) {
                    return this.config.specimen_fields;
                }
                return [];
            },
            rows: function() {
                return this.specimens.length
            }
        },
        mounted() {
            this.$nextTick(function () {
                this.initializeDashboard();
            });
        }
    }
</script>

<style scoped>

</style>