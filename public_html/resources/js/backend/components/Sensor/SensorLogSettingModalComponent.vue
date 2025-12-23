<template>
    <settingModal ref="modal" id="SettingModal" form_id="SettingForm" :data-src="dataSrc" :storeUrl="storeUrl"
        :hasFile="false">
        <template #title="titleProps">{{
            `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("sensor_log")}`
        }}</template>
        <template #modal-content>
            <div class="form-group">
                <label>{{ $t("sensor_log_toilet_info") }}</label>
                <div class="form-control">{{ location.name }}-{{ toilet.name }}-{{ $t("toilet_type_options")[toilet.type] }}</div>
            </div>
            <div class="form-group">
                <label>{{ $t("sensor_log_sensor") }}</label>
                <div class="form-control" read-only>{{ sensor.name }}</div>
            </div>
            <div class="form-group">
                <label for="raw_data">{{ $t("sensor_log_raw_data") }}</label><span class="text-danger">*</span>
                <input :step="step" class="form-control" type="number" id="raw_data"
                    name="raw_data" v-model="dataSrc.raw_data" />
            </div>
        </template>
    </settingModal>
</template>

<script>
import settingModal from "../SettingModalComponent";
export default {
    components: {
        settingModal,
    },
    props: {
        storeUrl: {
            type: String,
        },
        location: {
            type: Object,
        },
        toilet: {
            type: Object,
        },
        sensor: {
            type: Object,
        },
        step: {
            type: String,
        }
    },
    data() {
        return {
            dataSrc: {},
            defaultSrc: {
                raw_data: null,
            },
        };
    },
    methods: {
        updateResource(res) {
            this.dataSrc.raw_data = res.raw_data ?? null;
        },
        resetData() {
            Object.entries(this.defaultSrc).forEach(([field, value]) => {
                this.dataSrc[field] = value;
            });
        },
    },
};
</script>