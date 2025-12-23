<template>
  <settingModal
    ref="modal"
    id="SettingModal"
    form_id="SettingForm"
    :data-src="dataSrc"
    :storeUrl="storeUrl"
    :hasFile="false"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${sensor_name}${$t("sensor")}`
    }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="name">{{ $t("location") }}</label><span class="text-danger">*</span>
        <div class="form-control">{{ location.name }}</div>
      </div>
      <div class="form-group">
        <label for="name">{{ $t("toilet") }}</label><span class="text-danger">*</span>
        <div class="form-control">{{ toilet.name }}-{{ typeOptions[toilet.type]}}</div>
      </div>
      <div class="form-group">
        <label for="name">{{ $t("sensor_name") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.name"
          id="name"
          name="name"
          :placeholder="$t('sensor_name_placeholder')"
        />
      </div>
      <div class="form-group" v-for="(extra_field, index) in extraFields" :key="index">
        <label>{{ extra_field.display_name ?? $t(extra_field.name) }}</label><span class="text-danger" v-if="extra_field.required">*</span><br>
        <label class="switch" for="is_notification" v-if="extra_field.type == 'notification'">
            <input type="checkbox" id="is_notification" name="is_notification" v-model="dataSrc.is_notification">
            <span class="slider"></span>
        </label>
        <input
          v-else-if="extra_field.type != 'number'"
          class="form-control"
          :type="extra_field.type"
          :id="extra_field.name"
          :name="extra_field.name"
          v-model="dataSrc[extra_field.name]"
        />
        <input
          v-else
          step="0.01"
          class="form-control"
          :type="extra_field.type"
          :id="extra_field.name"
          :name="extra_field.name"
          v-model="dataSrc[extra_field.name]"
        />
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
    typeOptions: {
      type: Object,
    },
    extraFields: {
      type: Array,
      default: [],
    },
    sensor_name: {
      type: String
    }
  },
  created() {
    Object.entries(this.extraFields).forEach(([index, field]) => {
      this.defaultSrc[field.name] = field.default || null;
    });
    this.resetData();
  },
  data() {
    return {
      dataSrc: {},
      defaultSrc: {
        name: "",
        is_notification: true
      },
    };
  },
  methods: {
    updateResource(res) {
      this.dataSrc.name = res.name ?? null;
      Object.entries(this.extraFields).forEach(([index, field]) => {
        if (field.type == 'notification') {
          this.dataSrc[field.name] = res[field.name] == 1;
        } else {
          this.dataSrc[field.name] = res[field.name] ?? null;
        }
      });
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        this.dataSrc[field] = value;
      });
    },
  },
};
</script>