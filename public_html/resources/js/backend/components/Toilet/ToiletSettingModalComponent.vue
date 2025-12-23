<template>
  <settingModal
    ref="modal"
    id="toiletSettingModal"
    form_id="toiletSettingForm"
    :data-src="dataSrc"
    :storeUrl="storeUrl"
    :hasFile="true"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("toilet")}`
    }}</template>
    <template #modal-content="modalProps">
      <div class="form-group">
        <label for="location">{{ $t("location") }}</label
        ><span class="text-danger">*</span>
        <div class="form-control">{{ location.name }}</div>
      </div>
      <!-- 不顯示 device key -->
      <div class="form-group" v-if="!modalProps.isCreate && false">
        <label for="device_key">{{ $t("toilet_device_key") }}</label
        ><span class="text-danger">*</span>
        <div class="form-control d-flex justify-content-between">
          {{ dataSrc.device_key }}
          <i
            class="icon-copy"
            v-on:click.stop="copyDeviceKey(dataSrc.device_key)"
          ></i>
        </div>
      </div>
      <div class="form-group">
        <label for="code">{{ $t("toilet_code") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.code"
          id="code"
          name="code"
        />
      </div>
      <div class="form-group">
        <label for="name">{{ $t("toilet_name") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.name"
          id="name"
          name="name"
          :placeholder="$t('toilet_name_placeholder')"
        />
      </div>
      <div class="form-group">
        <label for="type">{{ $t("toilet_type") }}</label
        ><span class="text-danger">*</span>
        <select
          name="type"
          id="type"
          class="form-select"
          v-model="dataSrc.type"
        >
          <option
            :value="index"
            v-for="(type, index) in typeOptions"
            :key="index"
          >
            {{ type }}
          </option>
        </select>
      </div>
      <div class="form-group">
        <div class="row notification-period-input">
          <div class="col">
            <label for="notification_start">{{ $t("toilet_notification_start") }}</label><span class="text-danger">*</span><br>
            <VDatePicker v-model="notification_start" :rules="rules" :time-accuracy="2" mode="Time" :hide-time-header="true" :is24hr="true"/>
          </div>
          <div class="col">
            <label for="notification_end">{{ $t("toilet_notification_end") }}</label><span class="text-danger">*</span><br>
            <VDatePicker v-model="notification_end" :rules="rules" :time-accuracy="2" mode="Time" :hide-time-header="true" :is24hr="true"/>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="alert_token">{{ $t("toilet_alert_token") }}</label>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.alert_token"
          id="alert_token"
          name="alert_token"
        />
      </div>
      <div class="form-group">
        <label for="image">{{ $t("toilet_image") }}</label>
        <label class="file_label btn btn-upload row">
          <input
            type="file"
            accept=".jpg,.jpeg,.png"
            id="image"
            name="image"
            ref="image"
            @change="updateFile()"
          />
          <span class="col-2 choose-text">{{ $t("choose_file") }}</span>
          <span class="col-10 file_name">{{ file_name }}</span>
        </label>
        <img
          class="preview_img"
          id="preview_img"
          :src="this.image_url"
          v-if="this.image_url"
        />
      </div>
    </template>
  </settingModal>
</template>

<style>
.notification-period-input .vc-time-select-group, .notification-period-input .vc-base-select select {
  border: none;
}
</style>
<script>
import settingModal from "../SettingModalComponent";
import moment from "moment";
export default {
  components: {
    settingModal,
  },
  props: {
    location: {
      type: Object,
    },
    typeOptions: {
      type: Object,
    },
    storeUrl: {
      type: String,
    },
  },
  mounted() {
    this.resetData()
  },
  data() {
    const now = new Date();  // 獲取當前日期和時間
    return {
      file_name: this.$t("toilet_upload_img"),
      image_url: null,
      notification_start: new Date(now.getFullYear(), now.getMonth(), now.getDate(), 9, 0),
      notification_end: new Date(now.getFullYear(), now.getMonth(), now.getDate(), 18, 0),
      dataSrc: {

      },
      defaultSrc: {
        name: null,
        code: null,
        type: null,
        alert_token: null,
        image: ""
      },
      rules: {
        minutes: { interval: 30, isValid: true },
      }
    };
  },
  methods: {
    updateResource(res) {
      const now = new Date();  // 獲取當前日期和時間
      this.dataSrc.name = res.name ?? null;
      this.dataSrc.code = res.code ?? null;
      this.dataSrc.type = res.type;
      this.dataSrc.alert_token = res.alert_token ?? null;
      this.dataSrc.device_key = res.device_key ?? null;
      const [start_hours, start_minutes] = res.notification_start.split(":");
      const [end_hours, end_minutes] = res.notification_end.split(":");
      const start_date = new Date();
      const end_date = new Date();
      start_date.setHours(start_hours ?? 9);
      start_date.setMinutes(start_minutes, 0);
      end_date.setHours(end_hours ?? 18);
      end_date.setMinutes(end_minutes, 0);

      this.notification_start = start_date;
      this.notification_end = end_date;
      this.image_url = res.image ?? null;
      this.file_name = this.$t("toilet_upload_img");
    },
    updateFile() {
      if (this.$refs.image.files[0].size > 2 * 1024 * 1024) {
        this.$toast.error(this.$t("image_size_limit"));
        return;
      }
      this.dataSrc.image = this.$refs.image.files[0];
      if (this.$refs.image.files && this.$refs.image.files[0]) {
        let file = this.$refs.image.files[0];
        this.image_url = URL.createObjectURL(file);
      }
      if (this.$refs.image.files[0] == undefined) {
        this.file_name = this.$t("toilet_upload_img");
      } else {
        this.file_name = this.$refs.image.files[0].name;
      }
    },
    resetData() {
      const now = new Date();  // 獲取當前日期和時間
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        if (field in this.dataSrc) {
          this.dataSrc[field] = value;
        }
      });
      this.notification_start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 9, 0);
      this.notification_end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 18, 0);
      this.image_url = null;
      this.file_name = this.$t("toilet_upload_img");
    },
    copyDeviceKey(device_key) {
      navigator.clipboard
        .writeText(device_key)
        .then(() => {
          this.$toast.success("複製成功");
        })
        .catch((error) => {
          this.$toast.error("複製失敗");
          console.error("Failed to copy text:", error);
        });
    },
  },
  watch: {
    notification_start: {
      handler(newValue, oldValue) {
        this.dataSrc.notification_start = newValue ? moment(newValue).format('HH:mm'): null;
      },
    },
    notification_end: {
      handler(newValue, oldValue) {
        this.dataSrc.notification_end = newValue ? moment(newValue).format('HH:mm'): null;
      },
    },
  },
};
</script>