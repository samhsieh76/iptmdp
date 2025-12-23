<template>
  <settingModal
    ref="modal"
    id="roleSettingModal"
    form_id="roleSettingForm"
    :data-src="dataSrc"
    :defaultSrc="defaultSrc"
    :storeUrl="storeUrl"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("role")}`
    }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="name">{{ $t("role_name") }}</label
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
        <label for="level">{{ $t("role_level") }}</label
        ><span class="text-danger">*</span>
        <input
          type="number"
          class="form-control"
          v-model="dataSrc.level"
          id="level"
          name="level"
        />
      </div>
      <div class="form-group">
        <label for="group_id">{{ $t("role_group") }}</label
        ><span class="text-danger">*</span>
        <select
          name="group_id"
          id="group_id"
          class="form-select"
          v-model="dataSrc.group_id"
        >
          <option
            :value="roleGroup.id"
            v-for="roleGroup in roleGroupOptions"
            v-bind:key="roleGroup.id"
          >
            {{ roleGroup.name }}
          </option>
        </select>
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
    roleGroupOptions: {
      type: Array,
    },
    storeUrl: {
      type: String
    }
  },
  data() {
    return {
      dataSrc: {},
      defaultSrc: {
        name: "",
        level: null,
        group_id: null,
      },
    };
  },
  methods: {
    updateResource(res) {
      this.dataSrc.name = res.name;
      this.dataSrc.level = res.level;
      this.dataSrc.group_id = res.group_id;
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