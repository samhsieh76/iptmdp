<template>
  <settingModal
    ref="modal"
    id="userPasswordSettingModal"
    form_id="selfPasswordSettingForm"
    :data-src="dataSrc"
  >
    <template #title>{{ `${$t("edit")}${$t("password")}` }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="old_password">{{ $t("old_password") }}</label
        ><span class="text-danger">*</span>
        <input
          type="password"
          class="form-control"
          v-model="dataSrc.old_password"
          id="password"
        />
      </div>
      <div class="form-group">
        <label for="password">{{ $t("password") }}</label
        ><span class="text-danger">*</span>
        <input
          type="password"
          class="form-control"
          v-model="dataSrc.new_password"
          id="password"
        />
      </div>
      <div class="form-group">
        <label for="confirm_password">{{ $t("confirm_password") }}</label
        ><span class="text-danger">*</span>
        <input
          type="password"
          class="form-control"
          v-model="dataSrc.confirm_password"
          id="confirm_password"
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
  data() {
    return {
      dataSrc: {},
      defaultSrc: {
        old_password: null,
        new_password: null,
        confirm_password: null,
      },
    };
  },
  methods: {
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