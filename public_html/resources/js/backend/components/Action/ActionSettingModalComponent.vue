<template>
  <settingModal
    ref="modal"
    id="actionSettingModal"
    form_id="actionSettingForm"
    :data-src="dataSrc"
    :defaultSrc="defaultSrc"
    :storeUrl="storeUrl"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("actions")}`
    }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="name">{{ $t("action_name") }}</label
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
        <label for="display_name">{{ $t("action_display_name") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.display_name"
          id="display_name"
          name="display_name"
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
      type: String
    }
  },
  data() {
    return {
      dataSrc: {},
      defaultSrc: {
        name: "",
        display_name: "",
      },
    };
  },
  methods: {
    updateResource(res) {
      this.dataSrc.name = res.name;
      this.dataSrc.display_name = res.display_name;
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        if (field in this.dataSrc) {
          this.dataSrc[field] = value;
        }
      });
    },
  },
};
</script>