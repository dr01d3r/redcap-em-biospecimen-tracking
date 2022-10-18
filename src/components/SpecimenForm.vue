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

        <template v-if="debugMsg != null">
            <pre class="well">{{ debugOutput }}</pre>
        </template>
        <template v-if="errors.length">
            <div class="alert alert-danger p-4">
                <h1 class="display-4">Critical Errors Exist!</h1>
                <p class="lead mb-0">This dashboard has been disabled until all critical errors have been resolved.</p>
                <hr/>
                <ul>
                    <template v-for="error in errors">
                        <li>{{ error }}</li>
                    </template>
                </ul>
            </div>
        </template>

        <!-- MAIN CONTENT AREA -->
        <div class="form-row">
            <!-- specimen-id display -->
            <div class="form-group col mb-0">
                <label class="col-form-label">Specimen ID</label>
                <template v-if="mode==='edit'">
                    <b-form-input
                            ref="specimen_id_input"
                            readonly="readonly"
                            v-model="specimen.name"
                            :state="v$.specimen.name.$error ? false : null"
                    ></b-form-input>
                </template>
                <template v-else>
                    <b-form-input
                            ref="specimen_id_input"
                            autocomplete="off"
                            @keyup.enter="specimenScanned"
                            @blur="specimenScanned"
                            v-model="specimen.name"
                            :state="v$.specimen.name.$error ? false : null"
                    ></b-form-input>
                </template>
                <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                         v-if="v$.specimen.name.$error"
                         show
                >
                    <strong>Validation Error:</strong>
                    <ul class="mb-0">
                        <li v-for="error of v$.specimen.name.$errors" :key="error.$uid">{{ error.$message }}</li>
                    </ul>
                </b-alert>
            </div>
            <!-- csid -->
            <div class="form-group col-3 mb-0">
                <label class="col-form-label">
                    CSID&nbsp;(
                    <input type="checkbox" v-model="csidOverride" tabindex="-1" />
                    <i class="fas fa-info-circle" v-b-popover.hover="'Ignore required validation'"></i>
                    )
                </label>
                <b-form-input
                        ref="csid_input"
                        autocomplete="off"
                        @keyup.enter="csidScanned"
                        @blur="csidScanned"
                        v-model="specimen.csid"
                        :state="v$.specimen.csid.$error ? false : null"
                ></b-form-input>
                <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                         v-if="v$.specimen.csid.$error"
                         show
                >
                    <strong>Validation Error:</strong>
                    <ul class="mb-0">
                        <li v-for="error of v$.specimen.csid.$errors" :key="error.$uid">{{ error.$message }}</li>
                    </ul>
                </b-alert>
            </div>
            <!-- cuid -->
            <div class="form-group col-3 mb-0">
                <label class="col-form-label">
                    CUID&nbsp;(
                    <input type="checkbox" v-model="cuidOverride" tabindex="-1" />
                    <i class="fas fa-info-circle" v-b-popover.hover="'Ignore required validation'"></i>
                    )
                </label>
                <b-form-input
                        ref="cuid_input"
                        autocomplete="off"
                        @keyup.enter="cuidScanned"
                        @blur="cuidScanned"
                        v-model="specimen.cuid"
                        :state="v$.specimen.cuid.$error ? false : null"
                ></b-form-input>
                <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                         v-if="v$.specimen.cuid.$error"
                         show
                >
                    <strong>Validation Error:</strong>
                    <ul class="mb-0">
                        <li v-for="error of v$.specimen.cuid.$errors" :key="error.$uid">{{ error.$message }}</li>
                    </ul>
                </b-alert>
            </div>
        </div>
        <div class="form-row">
            <!-- specimen.date_time_collected -->
            <div class="form-group col-9 mb-0">
                <label class="col-form-label">Collected Date</label>
                <b-form-datepicker
                        ref="date_collected"
                        v-model="specimen.date_collected"
                        :state="v$.specimen.date_collected.$error ? false : null"
                        @input="v$.specimen.time_collected.$touch()"
                ></b-form-datepicker>
            </div>
            <div class="form-group col-3 mb-0">
                <label class="col-form-label">Collected Time</label>
                <b-form-input
                        ref="time_collected"
                        placeholder="i.e. 1600 or 16:00"
                        autocomplete="off"
                        v-model="specimen.time_collected"
                        :state="v$.specimen.time_collected.$error ? false : null"
                        @input="v$.specimen.date_collected.$touch()"
                        @keyup.enter="focusNext"
                        @keydown.enter="focusNext"
                ></b-form-input>
            </div>
            <div class="col-12" v-if="v$.specimen.date_collected.$error || v$.specimen.time_collected.$error">
                <b-alert variant="danger" class="mt-1 mb-0" show>
                    <strong>Validation Error:</strong>
                    <ul class="mb-0">
                        <li v-for="error of v$.specimen.date_collected.$errors" :key="error.$uid">{{ error.$message }}</li>
                        <li v-for="error of v$.specimen.time_collected.$errors" :key="error.$uid">{{ error.$message }}</li>
                    </ul>
                </b-alert>
            </div>
        </div>
        <div class="form-row">
            <!-- specimen mhn fields -->
            <div class="form-group col-6 mb-0">
                <div class="form-row">
                    <div class="col-6">
                        <label class="col-form-label">MHN</label>
                        <b-form-input
                                ref="mhn_input"
                                autocomplete="off"
                                v-model="specimen.mhn"
                                :state="v$.specimen.mhn_verify.$error ? false : null"
                                @keyup.enter="focusNext"
                                @keydown.enter="focusNext"
                                @input="v$.specimen.mhn_verify.$touch()"
                        ></b-form-input>
                    </div>
                    <div class="col-6">
                        <label class="col-form-label">MHN (verify)</label>
                        <b-form-input
                                ref="mhn_verify"
                                autocomplete="off"
                                v-model="specimen.mhn_verify"
                                :state="v$.specimen.mhn_verify.$error ? false : null"
                                @keyup.enter="focusNext"
                                @keydown.enter="focusNext"

                        ></b-form-input>
                    </div>
                    <div class="col-12" v-if="v$.specimen.mhn_verify.$error">
                        <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                                 show
                        >
                            <strong>Validation Error:</strong>
                            <ul class="mb-0">
                                <li v-for="error of v$.specimen.mhn_verify.$errors" :key="error.$uid">{{ error.$message }}</li>
                            </ul>
                        </b-alert>
                    </div>
                </div>
            </div>
            <!-- specimen.volume -->
            <div class="form-group col-3 mb-0">
                <label class="col-form-label">Volume ({{ unitDisplay }})</label>
                <b-form-input
                        ref="volume_input"
                        autocomplete="off"
                        v-model="specimen.volume"
                        :state="v$.specimen.volume.$error ? false : null"
                        @keyup.enter="focusNext"
                        @keydown.enter="focusNext"
                ></b-form-input>
                <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                         v-if="v$.specimen.volume.$error"
                         show
                >
                    <strong>Validation Error:</strong>
                    <ul class="mb-0">
                        <li v-for="error of v$.specimen.volume.$errors" :key="error.$uid">{{ error.$message }}</li>
                    </ul>
                </b-alert>
            </div>
        </div>
        <div v-bind:class="'form-row border border-left-0 border-right-0 mt-2 pb-2' + batchModeClass">
            <div class="col">
                <div class="form-row">
                    <!-- specimen.date_time_processed -->
                    <div class="form-group col-9 mb-0">
                        <label class="col-form-label">Processed Date</label>
                        <b-form-datepicker
                                ref="date_processed"
                                v-model="specimen.date_processed"
                                :state="v$.specimen.date_processed.$error ? false : null"
                                @input="v$.specimen.time_processed.$touch()"
                        ></b-form-datepicker>
                    </div>
                    <div class="form-group col-3 mb-0">
                        <label class="col-form-label">Processed Time</label>
                        <b-form-input
                                ref="time_processed"
                                placeholder="i.e. 1600 or 16:00"
                                autocomplete="off"
                                v-model="specimen.time_processed"
                                :state="v$.specimen.time_processed.$error ? false : null"
                                @input="v$.specimen.date_processed.$touch()"
                                @keyup.enter="focusNext"
                                @keydown.enter="focusNext"
                        ></b-form-input>
                    </div>
                    <div class="col-12" v-if="v$.specimen.date_processed.$error || v$.specimen.time_processed.$error">
                        <b-alert variant="danger" class="mt-1 mb-0" show>
                            <strong>Validation Error:</strong>
                            <ul class="mb-0">
                                <li v-for="error of v$.specimen.date_processed.$errors" :key="error.$uid">{{ error.$message }}</li>
                                <li v-for="error of v$.specimen.time_processed.$errors" :key="error.$uid">{{ error.$message }}</li>
                            </ul>
                        </b-alert>
                    </div>
                </div>
                <div class="form-row">
                    <!-- specimen.date_time_frozen -->
                    <div class="form-group col-9 mb-0">
                        <label class="col-form-label">Frozen Date</label>
                        <b-form-datepicker
                                ref="date_frozen"
                                v-model="specimen.date_frozen"
                                :state="v$.specimen.date_frozen.$error ? false : null"
                                @input="v$.specimen.time_frozen.$touch()"
                        ></b-form-datepicker>
                    </div>
                    <div class="form-group col-3 mb-0">
                        <label class="col-form-label">Frozen Time</label>
                        <b-form-input
                                ref="time_frozen"
                                placeholder="i.e. 1600 or 16:00"
                                autocomplete="off"
                                v-model="specimen.time_frozen"
                                :state="v$.specimen.time_frozen.$error ? false : null"
                                @input="v$.specimen.date_frozen.$touch()"
                                @keyup.enter="focusNext"
                                @keydown.enter="focusNext"
                        ></b-form-input>
                    </div>
                    <div class="col-12" v-if="v$.specimen.date_frozen.$error || v$.specimen.time_frozen.$error">
                        <b-alert variant="danger" class="mt-1 mb-0" show>
                            <strong>Validation Error:</strong>
                            <ul class="mb-0">
                                <li v-for="error of v$.specimen.date_frozen.$errors" :key="error.$uid">{{ error.$message }}</li>
                                <li v-for="error of v$.specimen.time_frozen.$errors" :key="error.$uid">{{ error.$message }}</li>
                            </ul>
                        </b-alert>
                    </div>
                </div>
                <div class="form-row">
                    <!-- comment field -->
                    <div class="form-group col mb-0">
                        <label class="col-form-label">Comments</label>
                        <b-form-input
                                ref="comment"
                                autocomplete="off"
                                v-model="specimen.comment"
                                :state="v$.specimen.comment.$error ? false : null"
                                @keyup.enter="focusNext"
                                @keydown.enter="focusNext"
                        ></b-form-input>
                        <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                                 v-if="v$.specimen.comment.$error"
                                 show
                        >
                            <strong>Validation Error:</strong>
                            <ul class="mb-0">
                                <li v-for="error of v$.specimen.comment.$errors" :key="error.$uid">{{ error.$message }}</li>
                            </ul>
                        </b-alert>
                    </div>
                    <!-- specimen.tech_initials -->
                    <div class="form-group col-3 mb-0">
                        <label class="col-form-label">Processed Tech</label>
                        <b-form-input
                                ref="tech_initials"
                                autocomplete="off"
                                v-model="specimen.tech_initials"
                                :state="v$.specimen.tech_initials.$error ? false : null"
                        ></b-form-input>
                        <b-alert variant="danger" class="mt-1 mb-0 px-3 py-2"
                                 v-if="v$.specimen.tech_initials.$error"
                                 show
                        >
                            <strong>Validation Error:</strong>
                            <ul class="mb-0">
                                <li v-for="error of v$.specimen.tech_initials.$errors" :key="error.$uid">{{ error.$message }}</li>
                            </ul>
                        </b-alert>
                    </div>
                </div>
            </div>
        </div>
        <!-- empty spacer row -->
        <div class="form-row mt-3"></div>
        <b-alert variant="warning" class="px-3 py-2"
                 v-if="Object.keys(warnings).length"
                 show
        >
            <strong>CAUTION:</strong> - The following warnings exist - review before saving!
            <ul class="mb-0">
                <li v-for="(v, k) in warnings">{{ v }}</li>
            </ul>
        </b-alert>
        <div class="form-row">
            <div class="col mb-0">
                <b-button block variant="success" @click.prevent="trySaveSpecimen">
                    <i class="fas fa-save"></i>&nbsp;Save
                </b-button>
            </div>
            <template v-if="resetDisable !== 'true'">
                <div class="col mb-0">
                    <b-button block variant="danger" @click.prevent="tryResetSpecimen(false)">
                        <i class="fas fa-undo"></i>&nbsp;Reset
                    </b-button>
                </div>
            </template>
            <template v-if="batchDisable !== 'true'">
                <div class="col-3 mb-0">
                    <b-button block :pressed.sync="batchEnabled" variant="warning" class="text-white">
                        <i class="fas fa-sync"></i>&nbsp;Batch Mode: {{ batchMode }}
                    </b-button>
                </div>
            </template>
        </div>
    </b-overlay>
</template>

<script>
    // QS
    import qs from 'qs';
    // DatePicker
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    // Luxon
    import { DateTime, Interval } from 'luxon';
    // Vuelidate
    import useVuelidate from '@vuelidate/core'
    import {
        helpers,
        required,
        requiredUnless,
        sameAs
    } from '@vuelidate/validators'
    // Loader
    import loader from '../loader.vue'

    const notInTheFuture = (param) => helpers.withParams(
        { type: 'notInTheFuture', value: param },
        (value, vm, model) => param === null || DateTime.fromFormat(param, model.luxonDateFormatFrom) <= DateTime.now()
    );

    const notBeforeCollected = (param) => helpers.withParams(
        { type: 'notBeforeCollected', value: param },
        (value, vm, model) =>
            param === null ||
            model.dateTimeCollected === null ||
            DateTime.fromFormat(param, model.luxonDateFormatFrom) >= DateTime.fromFormat(model.dateTimeCollected, model.luxonDateFormatFrom)
    );

    const notBeforeProcessed = (param) => helpers.withParams(
        { type: 'notBeforeProcessed', value: param },
        (value, vm, model) =>
            param === null ||
            model.dateTimeProcessed === null ||
            DateTime.fromFormat(param, model.luxonDateFormatFrom) >= DateTime.fromFormat(model.dateTimeProcessed, model.luxonDateFormatFrom)
    );

    const specimenMatchesBox = (value, vm, model) => {
        let specimen_match = value.match(model.config.specimen_name_regex);
        // ignore if it's not a match - either empty or base regex validation failed
        if (specimen_match === null) return true;
        let is_valid = true;
        // since [sample_type] is required, it can be parsed out of the nomenclature loop
        if (model.box_info.sample_type !== null) {
            is_valid = is_valid && model.box_info.sample_type === specimen_match.groups['sample_type'];
        }
        // debug
        // model.debugMsg = [ model.box_info.box_name_parsed ];
        for (const [key, value] of Object.entries(model.box_info.box_name_parsed)) {
            // debug
            // model.debugMsg.push('key='+key+', value='+value);
            let a, b;
            switch(key) {
                case "visit":
                case "aliquot_number":
                    a = parseInt(specimen_match.groups[key]);
                    b = parseInt(value);
                    // debug
                    // model.debugMsg.push('key='+key+'|a='+a+'|b='+b);
                    // ensure they both parsed as integer, then compare
                    // a value of '00' allows any values
                    is_valid = is_valid && !isNaN(a) && !isNaN(b) && (b === 0 || a === b);
                    break;
                default:
                    // ignore groups not specifically targeted for this validation
                    break;
            }
        }
        return is_valid;
    };

    export default {
        setup: () => {
            return {
                v$: useVuelidate({
                    $lazy: true,
                    $autoDirty: true,
                    $stopPropagation: true
                })
            }
        },
        components: {
            qs,
            DatePicker,
            loader
        },
        props: {
            'reset-disable': {
                type: String,
                required: false
            },
            'batch-disable': {
                type: String,
                required: false
            },
            mode: {
                type: String,
                required: true
            },
            box_record_id: {
                type: String,
                required: true
            },
            box_info: {
                type: Object,
                required: true
            },
            config: {
                type: Object,
                required: true
            }
        },
        validations() {
            return {
                specimen: {
                    name: {
                        required: helpers.withMessage('Specimen ID is required', required),
                        regexMatch: helpers.withMessage('Value provided does not match the required nomenclature!',
                            (value) => this.isEmpty(value) || value.match(this.config.specimen_name_regex)
                        ),
                        specimenMatchesBox: helpers.withMessage('Box nomenclature mismatch - one or more parts do not align with the current box', specimenMatchesBox)
                    },
                    csid: {
                        requiredUnless: helpers.withMessage('CSID is required', requiredUnless(this.csidOverride)),
                        csidFormat: helpers.withMessage('Must be exactly 10 digits', helpers.regex(this.csidFormat))
                    },
                    cuid: {
                        requiredUnless: helpers.withMessage('CUID is required', requiredUnless(this.cuidOverride)),
                        cuidFormat: helpers.withMessage('Must be 8 alphanumeric in length and contain at least 1 alpha character', helpers.regex(this.cuidFormat))
                    },
                    date_collected: {
                        required: helpers.withMessage('Collected Date is required', required),
                        notInTheFuture: helpers.withMessage('Collected Date/Time cannot be in the future', notInTheFuture(this.dateTimeCollected)),
                    },
                    time_collected: {
                        required: helpers.withMessage('Collected Time is required', required),
                        timeFormat: helpers.withMessage('Invalid Time Format', helpers.regex(this.timeFormat))
                    },
                    date_processed: {
                        required: helpers.withMessage('Processed Date is required', required),
                        notInTheFuture: helpers.withMessage('Processed Date/Time cannot be in the future', notInTheFuture(this.dateTimeProcessed)),
                        notBeforeCollected: helpers.withMessage('Processed Date/Time must be after Collected Date/Time', notBeforeCollected(this.dateTimeProcessed)),
                    },
                    time_processed: {
                        required: helpers.withMessage('Processed Time is required', required),
                        timeFormat: helpers.withMessage('Invalid Time Format', helpers.regex(this.timeFormat))
                    },
                    date_frozen: {
                        required: helpers.withMessage('Frozen Date is required', required),
                        notInTheFuture: helpers.withMessage('Frozen Date/Time cannot be in the future', notInTheFuture(this.dateTimeFrozen)),
                        notBeforeProcessed: helpers.withMessage('Frozen Date/Time must be after Processed Date/Time', notBeforeProcessed(this.dateTimeFrozen)),
                    },
                    time_frozen: {
                        required: helpers.withMessage('Frozen Time is required', required),
                        timeFormat: helpers.withMessage('Invalid Time Format', helpers.regex(this.timeFormat))
                    },
                    mhn_verify: {
                        sameAsMHN: helpers.withMessage('MHN values must match!', sameAs(this.specimen.mhn))
                    },
                    volume: {
                        required: helpers.withMessage('Volume is required', required),
                        volumeFormat: helpers.withMessage('Invalid Format', helpers.regex(this.volumeFormat))
                    },
                    tech_initials: {
                        required: helpers.withMessage('Tech Initials is required', required)
                    },
                    comment: {}
                }
            }
        },
        data() {
            return {
                dateFormat: 'YYYY-MM-DD HH:mm',
                luxonDateFormatFrom: 'yyyy-MM-dd HH:mm',
                maxDate: DateTime.now().toFormat('yyyy-MM-dd'),
                limitCollToProc: 30,
                limitProcToFroz: 10,
                timeFormat: /^(?<hour>[01][0-9]|2[0-3])(?<separator>:)?(?<minute>[0-5][0-9])$/,
                csidFormat: /^\d{10}$/,
                cuidFormat: /^(?=.*([a-zA-Z]+))[a-zA-Z0-9]{8}$/,
                volumeFormat: /^(\d+)(\.\d)$/,
                // primary dataset
                specimen: {
                    record_id: null,
                    name: null,
                    csid: null,
                    cuid: null,
                    date_collected: null,
                    time_collected: null,
                    mhn_verify: null,
                    date_processed: null,
                    time_processed: null,
                    date_frozen: null,
                    time_frozen: null,
                    mhn: null,
                    volume: this.config.default_volume ?? null,
                    tech_initials: null,
                    comment: null,
                    box_position: null
                },
                // server-side validation support
                vuelidateExternalResults: {
                    specimen: {
                        name: [],
                        csid: [],
                        cuid: []
                    }
                },
                errors: [],
                warnings: {},
                isOverlayed: false,
                focusOverride: false,
                csidOverride: false,
                cuidOverride: false,
                lastFocused: null,
                batchEnabled: false,
                debugMsg: null
            }
        },
        watch: {
            'specimen.date_collected': function(newVal, oldVal) {
                setTimeout(() => {
                    this.focusElement('time_collected');
                }, 150);
                // trigger soft time validations
                this.checkDateTimeIntervals();
            },
            'specimen.date_processed': function(newVal, oldVal) {
                setTimeout(() => {
                    this.focusElement('time_processed');
                }, 150);
                // trigger soft time validations
                this.checkDateTimeIntervals();
            },
            'specimen.date_frozen': function(newVal, oldVal) {
                setTimeout(() => {
                    this.focusElement('time_frozen');
                }, 150);
                // trigger soft time validations
                this.checkDateTimeIntervals();
            },
            'specimen.time_collected': function(newTime, oldTime) {
                if (newTime == null) return;
                // modify to include the ':' separator, if necessary
                let newTimeMatches = newTime.match(this.timeFormat);
                if (newTimeMatches && !newTimeMatches.groups.separator) {
                    this.specimen.time_collected = [newTimeMatches.groups.hour, newTimeMatches.groups.minute].join(':');
                }
                // trigger soft time validations
                this.checkDateTimeIntervals();
            },
            'specimen.time_processed': function(newTime, oldTime) {
                if (newTime == null) return;
                // modify to include the ':' separator, if necessary
                let newTimeMatches = newTime.match(this.timeFormat);
                if (newTimeMatches && !newTimeMatches.groups.separator) {
                    this.specimen.time_processed = [newTimeMatches.groups.hour, newTimeMatches.groups.minute].join(':');
                }
                // trigger soft time validations
                this.checkDateTimeIntervals();
            },
            'specimen.time_frozen': function(newTime, oldTime) {
                if (newTime == null) return;
                // modify to include the ':' separator, if necessary
                let newTimeMatches = newTime.match(this.timeFormat);
                if (newTimeMatches && !newTimeMatches.groups.separator) {
                    this.specimen.time_frozen = [newTimeMatches.groups.hour, newTimeMatches.groups.minute].join(':');
                }
                // trigger soft time validations
                this.checkDateTimeIntervals();
            }
        },
        computed: {
            dateTimeCollected: {
                get: function() {
                    if (this.specimen.date_collected && this.specimen.time_collected && this.specimen.time_collected.match(this.timeFormat)) {
                        return this.specimen.date_collected + ' ' + this.specimen.time_collected;
                    } else {
                        return null;
                    }
                },
                set: function(newValue) {
                    let parts = newValue.split(' ');
                    this.specimen.date_collected = parts[0];
                    this.specimen.time_collected = parts[1];
                }
            },
            dateTimeProcessed: {
                get: function() {
                    if (this.specimen.date_processed && this.specimen.time_processed && this.specimen.time_processed.match(this.timeFormat)) {
                        return this.specimen.date_processed + ' ' + this.specimen.time_processed;
                    } else {
                        return null;
                    }
                },
                set: function(newValue) {
                    let parts = newValue.split(' ');
                    this.specimen.date_processed = parts[0];
                    this.specimen.time_processed = parts[1];
                }
            },
            dateTimeFrozen: {
                get: function() {
                    if (this.specimen.date_frozen && this.specimen.time_frozen && this.specimen.time_frozen.match(this.timeFormat)) {
                        return this.specimen.date_frozen + ' ' + this.specimen.time_frozen;
                    } else {
                        return null;
                    }
                },
                set: function(newValue) {
                    let parts = newValue.split(' ');
                    this.specimen.date_frozen = parts[0];
                    this.specimen.time_frozen = parts[1];
                }
            },
            batchMode: function() {
                return this.batchEnabled ? 'On' : 'Off';
            },
            batchModeClass: function() {
                return this.batchEnabled ? ' alert-warning border-warning' : '';
            },
            unitDisplay: function() {
                let units = '';
                if (this.box_info && this.box_info.sample_type) {
                    units = this.config['sample_type_units'][this.box_info.sample_type];
                }
                return units;
            },
            debugOutput: function() {
                return JSON.stringify(this.debugMsg, null, '\t');
            }
        },
        methods: {
            async post(action, data, callback, doOverlay = true, doFocusOverride = false) {
                if (doOverlay === true) {
                    this.isOverlayed = true;
                }
                if (doFocusOverride === true) {
                    this.focusOverride = true;
                }
                data = Object.assign({
                    redcap_csrf_token: OrcaBiospecimenTracking().redcap_csrf_token,
                    action: action
                }, data);
                this.axios({
                    url: OrcaBiospecimenTracking().url,
                    method: 'post',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: qs.stringify(data)
                })
                .then(response => {
                    if (response.data) {
                        callback(response.data);
                    }
                })
                .catch(e => {
                    let errorMsg = 'An unknown error occurred';
                    if (e.response && e.response.data) {
                        errorMsg = e.response.data;
                    }
                    this.toast(
                        errorMsg,
                        'Action Failed',
                        'danger'
                    );
                })
                .finally(() => {
                    if (doOverlay === true) {
                        setTimeout(() => {
                            this.isOverlayed = false;
                        }, 250);
                    }
                });
            },
            async loadSpecimen(record_id) {
                if (!record_id) {
                    return;
                }
                this.isOverlayed = true;
                this.focusOverride = true;
                this.resetSpecimen();
                const data = {
                    redcap_csrf_token: OrcaBiospecimenTracking().redcap_csrf_token,
                    action: 'get-specimen',
                    specimen_record_id: record_id
                };
                this.axios({
                    url: OrcaBiospecimenTracking().url,
                    method: 'post',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: qs.stringify(data)
                })
                .then(response => {
                    if (response.data) {
                        // must set each individual property
                        // trying to set the entire specimen will break everything!
                        this.specimen.record_id = response.data.record_id;
                        this.specimen.name = response.data.name;
                        this.specimen.csid = response.data.csid;
                        this.specimen.cuid = response.data.cuid;
                        this.specimen.mhn = response.data.mhn;
                        this.specimen.mhn_verify = response.data.mhn;
                        this.specimen.volume = response.data.volume;
                        this.specimen.tech_initials = response.data.tech_initials;
                        this.specimen.comment = response.data.comment;
                        this.specimen.box_position = response.data.box_position;

                        this.dateTimeCollected = response.data.date_time_collected;
                        this.dateTimeProcessed = response.data.date_time_processed;
                        this.dateTimeFrozen = response.data.date_time_frozen;

                        this.v$.specimen.$reset();
                        setTimeout(() => {
                            this.resetFocus();
                        }, 250);
                    }
                })
                .catch(e => {
                    let errorMsg = 'An unknown error occurred';
                    if (e.response.data) {
                        errorMsg = e.response.data;
                    }
                    this.toast(
                        errorMsg,
                        'Specimen Load Failed',
                        'danger'
                    );
                })
                .finally(() => {
                    setTimeout(() => {
                        this.isOverlayed = false;
                    }, 250);
                });
            },
            async searchSpecimen(search_value) {
                this.isOverlayed = true;
                this.focusOverride = true;
                this.resetSpecimen(true, true);
                const data = {
                    redcap_csrf_token: OrcaBiospecimenTracking().redcap_csrf_token,
                    action: 'search-specimen',
                    search_value: search_value
                };
                this.axios({
                    url: OrcaBiospecimenTracking().url,
                    method: 'post',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: qs.stringify(data)
                })
                .then(response => {
                    if (response.data) {
                        this.searchSpecimenCallback(response.data);
                    }
                })
                .catch(e => {
                    let errorMsg = 'An unknown error occurred';
                    if (e.response && e.response.data) {
                        errorMsg = e.response.data;
                    }
                    this.toast(
                        errorMsg,
                        'Specimen Search Failed',
                        'danger'
                    );
                })
                .finally(() => {
                    setTimeout(() => {
                        this.isOverlayed = false;
                    }, 250);
                });
            },
            searchSpecimenCallback: function(data) {
                switch (data.match_type) {
                    case "exact":
                        if (data.specimen.box_record_id === this.box_record_id) {
                            Object.assign(this.vuelidateExternalResults, {
                                specimen: {
                                    name: [ 'Cannot process specimen as it already exists on this box!' ]
                                }
                            });
                            this.$nextTick(() => {
                                this.v$.specimen.name.$validate();
                            });
                        } else {
                            // specimen move, validate target well position
                            /*
                                data.plate.box_name
                                data.specimen.box_position
                                data.specimen.box_record_id
                             */
                            const h = this.$createElement;
                            const ack = h('table', { class: [ 'table', 'text-center', 'm-0' ] }, [
                                h('thead', [
                                    h('tr', [
                                        h('th', 'From'),
                                        h('th', [
                                            h('i', { class: [ 'fas', 'fa-angle-double-right' ] })
                                        ]),
                                        h('th', 'To')
                                    ])
                                ]),
                                h('tbody', [
                                    h('tr', [
                                        h('td', data.plate.box_name),
                                        h('th', 'BOX'),
                                        h('td', this.box_info.box_name)
                                    ]),
                                    h('tr', [
                                        h('td', data.specimen.box_position),
                                        h('th', 'POS'),
                                        h('td', this.specimen.box_position)
                                    ])
                                ]),
                            ]);
                            this.$bvModal.msgBoxConfirm([ack], {
                                titleHtml: `Moving: [<span class='text-monospace text-danger'>${data.specimen.name}</span>]`,
                                headerClass: 'alert-secondary',
                                headerBorderVariant: 'secondary',
                                footerClass: 'alert-secondary',
                                footerBorderVariant: 'secondary',
                                bodyClass: 'p-0',
                                okTitle: 'Confirm',
                                cancelTitle: 'Cancel',
                                centered: true
                            })
                            .then(value => {
                                if (value === true) {
                                    // move specimen
                                    data.specimen.box_record_id = this.box_record_id;
                                    data.specimen.box_position = this.specimen.box_position;
                                    this.saveSpecimen(data.specimen);
                                } else {
                                    this.resetSpecimen(false, true);
                                }
                            })
                            .catch(e => {
                                let errorMsg = 'An unknown error occurred';
                                if (e.response.data) {
                                    errorMsg = e.response.data;
                                }
                                this.toast(
                                    errorMsg,
                                    'Add Specimen Failed',
                                    'danger'
                                );
                            });
                        }
                        this.focusOverride = false;
                        break;
                    case "participant":
                        // TODO for temporary box_type ('00'), error if specimen lives in another box
                        this.specimen.mhn = data.specimen.mhn;
                        this.specimen.mhn_verify = data.specimen.mhn;
                        this.toast(
                            'Pre-filling MHN based on Participant-only match',
                            'Participant Match (' + data.specimen.name + ')',
                            'info',
                            10000
                        );
                        this.focusOverride = false;
                        this.focusElement('csid_input');
                        break;
                    case "full":
                        this.specimen.mhn = data.specimen.mhn;
                        this.specimen.mhn_verify = data.specimen.mhn;
                        this.dateTimeCollected = data.specimen.date_time_collected;
                        this.dateTimeProcessed = data.specimen.date_time_processed;
                        this.dateTimeFrozen = data.specimen.date_time_frozen;
                        this.specimen.tech_initials = data.specimen.tech_initials;
                        this.specimen.comment = data.specimen.comment;
                        this.toast(
                            'Pre-filling all fields based on Participant/Visit/SampleType match.',
                            'Full Match (' + data.specimen.name + ')',
                            'info',
                            10000
                        );
                        this.focusOverride = false;
                        // blind disable batch mode since we're copying data into batch fields
                        this.batchEnabled = false;
                        // offset next focus by 250ms due to date-picker offset of 150ms /sadface
                        setTimeout(() => {
                            this.focusElement('csid_input');
                        }, 250);
                        break;
                    default:
                        this.toast(
                            'New Specimen',
                            'No Match Found',
                            'info'
                        );
                        this.focusOverride = false;
                        this.focusElement('csid_input');
                        break;
                }
                this.v$.specimen.name.$reset();
            },
            validateCSIDCallback: function(data) {
                if (data.isValid !== true) {
                    Object.assign(this.vuelidateExternalResults, {
                        specimen: {
                            csid: data.errors
                        }
                    });
                    this.$nextTick(() => {
                        this.v$.specimen.csid.$validate();
                    });
                } else {
                    this.focusElement('cuid_input');
                }
            },
            validateCSID: function(specimen, csid) {
                this.post('validate-csid', { specimen: specimen, csid: csid }, this.validateCSIDCallback);
            },
            validateCUIDCallback: function(data) {
                if (data.isValid !== true) {
                    Object.assign(this.vuelidateExternalResults, {
                        specimen: {
                            cuid: data.errors
                        }
                    });
                    this.$nextTick(() => {
                        this.v$.specimen.cuid.$validate();
                    });
                } else {
                    this.focusOverride = false;
                    this.focusElement('date_collected');
                }
            },
            validateCUID: function(cuid) {
                this.post('validate-cuid', { cuid: cuid }, this.validateCUIDCallback);
            },
            specimenScanned: function() {
                if (this.v$.specimen.name.$dirty && !this.v$.specimen.name.$error) {
                    // ensure there's a valid well position available
                    let response = this.$parent.$parent.getWellForSpecimen(this.specimen.name);
                    if (response.result === true) {
                        this.specimen.box_position = response.wellPosition;
                        this.searchSpecimen(this.specimen.name);
                    } else {
                        Object.assign(this.vuelidateExternalResults, response.errors);
                    }
                }
            },
            cuidScanned: function(e) {
                if (this.v$.specimen.cuid.$dirty && !this.v$.specimen.cuid.$error) {
                    this.v$.specimen.cuid.$reset();
                    this.validateCUID(this.specimen.cuid);
                }
            },
            csidScanned: function(e) {
                if (!this.v$.specimen.name.$error
                    && this.v$.specimen.csid.$dirty && !this.v$.specimen.csid.$error
                ) {
                    this.v$.specimen.csid.$reset();
                    this.validateCSID(this.specimen.name, this.specimen.csid);
                }
            },
            resetSpecimen: function(preserveName = false, preservePosition = false) {
                this.specimen = {
                    record_id: null,
                    name: preserveName ? this.specimen.name : null,
                    csid: null,
                    cuid: null,
                    date_collected: null,
                    time_collected: null,
                    mhn: null,
                    mhn_verify: null,
                    date_processed: this.batchEnabled ? this.specimen.date_processed : null,
                    time_processed: this.batchEnabled ? this.specimen.time_processed : null,
                    date_frozen: this.batchEnabled ? this.specimen.date_frozen : null,
                    time_frozen: this.batchEnabled ? this.specimen.time_frozen : null,
                    volume: this.config.default_volume ?? null,
                    tech_initials: this.batchEnabled ? this.specimen.tech_initials : null,
                    comment: this.batchEnabled ? this.specimen.comment : null,
                    box_position: preservePosition ? this.specimen.box_position : null
                };
                this.csidOverride = false;
                this.cuidOverride = false;
                this.v$.specimen.$reset();
            },
            tryResetSpecimen: function(preserveName = false) {
                this.resetSpecimen(preserveName);
                setTimeout(() => {
                    this.resetFocus();
                }, 250);
            },
            saveSpecimen: function(specimen) {
                // set overlay
                this.isOverlayed = true;
                // build data
                const data = {
                    redcap_csrf_token: OrcaBiospecimenTracking().redcap_csrf_token,
                    action: 'save-specimen',
                    specimen: specimen
                };
                // submit requests
                this.axios({
                    url: OrcaBiospecimenTracking().url,
                    method: 'post',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: qs.stringify(data)
                })
                .then(response => {
                    if (response.data) {
                        this.focusOverride = true;
                        // push back to root so it can be added to specimen list
                        this.$emit('specimenSaved', response.data);
                        this.tryResetSpecimen();
                    }
                })
                .catch(e => {
                    let errorMsg = 'An unknown error occurred';
                    if (e.response.data) {
                        errorMsg = e.response.data;
                    }
                    this.toast(
                        errorMsg,
                        'Add Specimen Failed',
                        'danger'
                    );
                })
                .finally(() => {
                    setTimeout(() => {
                        this.isOverlayed = false;
                    }, 250);
                });
            },
            async trySaveSpecimen() {
                let isValid = await this.v$.specimen.$validate();
                if (isValid) {
                    let okToSave = true;
                    if (Object.keys(this.warnings).length) {
                        const h = this.$createElement;
                        // modal body create
                        let warnings = [];
                        for (const [key, value] of Object.entries(this.warnings)) {
                            warnings.push(h('li', value));
                        }
                        const ack = h('div', void 0, [
                            h('p', { class: [ 'lead' ] }, 'The following warnings exist.  Please review prior to saving this specimen.'),
                            h('ul', void 0, warnings)
                        ]);
                        // show confirmation dialog
                        await this.$bvModal.msgBoxConfirm([ack], {
                            size: 'lg',
                            title: `Save Warning Acknowledgement`,
                            headerBgVariant: 'warning',
                            headerBorderVariant: 'secondary',
                            headerTextVariant: 'dark',
                            okTitle: 'Save',
                            cancelTitle: 'Cancel',
                            okVariant: 'success',
                            footerClass: 'alert-warning',
                            footerBorderVariant: 'warning',
                            footerTextVariant: 'light',
                            centered: true
                        })
                        .then(value => {
                            if (value !== true) {
                                // do not save specimen
                                okToSave = false;
                            }
                        })
                        .catch(e => {
                            // if something failed, don't save
                            okToSave = false;
                            let errorMsg = 'An unknown error occurred';
                            if (e.response.data) {
                                errorMsg = e.response.data;
                            }
                            this.toast(
                                errorMsg,
                                'Add Specimen Failed',
                                'danger'
                            );
                        });
                    }
                    if (okToSave) {
                        this.saveSpecimen({
                            record_id: this.specimen.record_id,
                            name: this.specimen.name,
                            csid: this.specimen.csid,
                            cuid: this.specimen.cuid,
                            date_time_collected: this.dateTimeCollected,
                            date_time_processed: this.dateTimeProcessed,
                            date_time_frozen: this.dateTimeFrozen,
                            mhn: this.specimen.mhn,
                            volume: this.specimen.volume,
                            tech_initials: this.specimen.tech_initials,
                            comment: this.specimen.comment,
                            box_record_id: this.box_record_id,
                            box_position: this.specimen.box_position
                        });
                    }
                }
            },
            // interactivity support
            resetFocus: function() {
                this.focusOverride = false;
                this.focusElement('specimen_id_input');
            },
            focusElement: function(refName) {
                if (!this.focusOverride && this.$refs[refName]) {
                    this.$nextTick(() => {
                        this.$refs[refName].focus();
                    });
                }
            },
            focusNext: function(e) {
                // avoid unnecessary processing time if focus change is disabled
                if (this.focusOverride) return;
                // process focus change
                switch (e.type) {
                    case 'keydown':
                        this.lastFocused = e.target.id;
                        break;
                    case 'keyup':
                        if (this.lastFocused === e.target.id) {
                            this.lastFocused = null;
                            let id_ref = null;
                            for (const [key, value] of Object.entries(this.$refs)) {
                                if (e.target.id === value.$el.id) {
                                    id_ref = key;
                                    break;
                                }
                            }
                            switch (id_ref) {
                                case 'csid_input':      this.focusElement('cuid_input'); break;
                                case 'cuid_input':      this.focusElement('date_collected'); break;
                                case 'time_collected':  this.focusElement('mhn_input'); break;
                                case 'mhn_input':       this.focusElement('mhn_verify'); break;
                                case 'mhn_verify':      this.focusElement('volume_input'); break;
                                case 'volume_input':    this.focusElement('date_processed'); break;
                                case 'time_processed':  this.focusElement('date_frozen'); break;
                                case 'time_frozen':     this.focusElement('comment'); break;
                                case 'comment':         this.focusElement('tech_initials'); break;
                            }
                        }
                        break;
                }
            },
            checkDateTimeIntervals: function() {
                const dt1 = this.dateTimeCollected;
                const dt2 = this.dateTimeProcessed;
                const dt3 = this.dateTimeFrozen;
                // first reset warnings
                if (this.warnings.hasOwnProperty("coll_to_proc_fail")) {
                    delete this.warnings["coll_to_proc_fail"];
                }
                if (this.warnings.hasOwnProperty("prod_to_froz_fail")) {
                    delete this.warnings["prod_to_froz_fail"];
                }
                // collected -> processed >= 30 minutes
                if (dt1 !== null && dt2 !== null) {
                    const diff1 = Interval.fromDateTimes(
                        DateTime.fromFormat(dt1, this.luxonDateFormatFrom),
                        DateTime.fromFormat(dt2, this.luxonDateFormatFrom)
                    );
                    if (diff1.length("minutes") < this.limitCollToProc) {
                        this.warnings["coll_to_proc_fail"] = "Collected to Processed time ("
                            + diff1.toDuration(['hours', 'minutes']).toHuman()
                            + ") must be at least " + this.limitCollToProc + " minutes";
                    }
                }
                // processed -> frozen <= 10 minutes
                if (dt2 !== null && dt3 !== null) {
                    const diff2 = Interval.fromDateTimes(
                        DateTime.fromFormat(dt2, this.luxonDateFormatFrom),
                        DateTime.fromFormat(dt3, this.luxonDateFormatFrom)
                    );
                    if (diff2.length("minutes") < this.limitProcToFroz) {
                        this.warnings["prod_to_froz_fail"] = "Processed to Frozen time ("
                            + diff2.toDuration(['hours', 'minutes']).toHuman()
                            + ") must be at least " + this.limitProcToFroz + " minutes";
                    }
                }
            }
        }
    }
</script>

<style scoped>
    .alert-warning {
        border-color: #ffc107 !important;
    }
    .alert ul {
        padding-inline-start: 15px;
    }
</style>