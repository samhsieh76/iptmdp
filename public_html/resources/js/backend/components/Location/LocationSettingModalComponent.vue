<template>
  <settingModal
    ref="modal"
    id="locationSettingModal"
    form_id="locationSettingForm"
    :data-src="dataSrc"
    :storeUrl="storeUrl"
    :hasFile="true"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("location")}`
    }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="name">{{ $t("location_name") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.name"
          id="name"
          name="name"
        />
      </div>
      <div class="form-group">
        <label for="administration_id">{{
          $t("location_administration")
        }}</label><span class="text-danger">*</span>
        <select
          name="administration_id"
          id="administration_id"
          class="form-select"
          v-model="dataSrc.administration_id"
        >
          <option
            :value="user.id"
            v-for="user in userOptions"
            v-bind:key="user.id"
          >
            {{ user.name }}
          </option>
        </select>
      </div>
      <div class="form-group">
        <label for="address">{{ $t("location_address") }}</label><span class="text-danger">*</span>
        <div class="row">
          <div class="col-md-4">
            <select
              name="county_id"
              id="county_id"
              class="form-select"
              v-model="dataSrc.county_id"
            >
              <option
                :value="index"
                v-for="(county, index) in countyOptions"
                :key="index"
              >
                {{ county }}
              </option>
            </select>
          </div>
          <div class="col-md-8">
            <input
              type="text"
              class="form-control"
              v-model="dataSrc.address"
              id="address"
              name="address"
            />
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="row">
          <div class="col">
            <label for="latitude">{{ $t("location_latitude") }}</label>
            <input
              type="text"
              class="form-control"
              v-model="dataSrc.latitude"
              id="latitude"
              name="latitude"
              :placeholder="$t('location_latitude_placeholder')"
            />
          </div>
          <div class="col">
            <label for="longitude">{{ $t("location_longitude") }}</label>
            <input
              type="text"
              class="form-control"
              v-model="dataSrc.longitude"
              id="longitude"
              name="longitude"
              :placeholder="$t('location_longitude_placeholder')"
            />
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="business_hours">{{ $t("location_business_hours") }}</label>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.business_hours"
          id="business_hours"
          name="business_hours"
        />
      </div>
      <div class="form-group">
        <label for="image">{{ $t("location_image") }}</label>
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

<script>
import settingModal from "../SettingModalComponent";
export default {
  components: {
    settingModal,
  },
  props: {
    countyOptions: {
      type: Object,
    },
    userOptions: {
      type: Array,
    },
    storeUrl: {
      type: String,
    },
  },
  mounted() {
    this.dataSrc.image = "";
  },
  data() {
    return {
      file_name: this.$t("location_upload_img"),
      image_url: null,
      dataSrc: {},
      defaultSrc: {
        county_id: null,
        administration_id: null,
        name: null,
        address: null,
        longitude: null,
        latitude: null,
        business_hours: null,
        parent_id: null,
        image: null,
      },
    };
  },
  methods: {
    updateResource(res) {
      this.dataSrc.county_id = res.county_id ?? null;
      this.dataSrc.administration_id = res.administration_id ?? null;
      this.dataSrc.name = res.name;
      this.dataSrc.address = res.address ?? null;
      this.dataSrc.longitude = res.longitude ?? null;
      this.dataSrc.latitude = res.latitude ?? null;
      this.dataSrc.business_hours = res.business_hours ?? null;
      this.image_url = res.image ?? null;
      this.file_name = this.$t("location_upload_img");
    },
    updateFile() {
      if(this.$refs.image.files[0].size > (2 * 1024 * 1024)) {
        this.$toast.error(this.$t('image_size_limit'));
        return;
      }
      this.dataSrc.image = this.$refs.image.files[0];
      if(this.$refs.image.files && this.$refs.image.files[0]) {
        let file = this.$refs.image.files[0];
        this.image_url = URL.createObjectURL(file);
      }
      if(this.$refs.image.files[0] == undefined) {
        this.file_name = this.$t("location_upload_img");
      } else {
        this.file_name = this.$refs.image.files[0].name;
      }
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        if(field in this.dataSrc) {
          this.dataSrc[field] = value;
        }
      });
      this.image_url = null;
      this.file_name = this.$t("location_upload_img");
    },
  },
};
</script>