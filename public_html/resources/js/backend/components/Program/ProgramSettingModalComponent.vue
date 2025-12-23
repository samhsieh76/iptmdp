<template>
  <settingModal ref="modal" id="programSettingModal" form_id="programSettingForm" :data-src="dataSrc"
    :defaultSrc="defaultSrc" :storeUrl="storeUrl">
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("program")}`
    }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="name">{{ $t("program_name") }}</label><span class="text-danger">*</span>
        <input type="text" class="form-control" v-model="dataSrc.name" id="name" name="name" />
      </div>
      <div class="form-group">
        <label for="display_name">{{ $t("program_display_name") }}</label><span class="text-danger">*</span>
        <input type="text" class="form-control" v-model="dataSrc.display_name" id="display_name" name="display_name" />
      </div>
      <div class="form-group">
        <label for="actions">{{ $t("actions") }}</label><span class="text-danger">*</span>
        <div class="form-check" v-for="action in actionOptions" :key="action.id">
          <input type="checkbox" class="form-check-input" v-model="dataSrc.actions" :value="action.id" :id="'action_' + action.id" />
          <label v-bind:for="'action_' + action.id" class="form-check-label">{{ action.display_name }}</label>
        </div>
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
    actionOptions: {
      type: Array,
    },
    storeUrl: {
      type: String
    }
  },
  data() {
    return {
      dataSrc: {
      },
      defaultSrc: {
        name: "",
        display_name: "",
        actions: []
      },
    };
  },
  methods: {
    updateResource(res) {
      this.dataSrc.name = res.name;
      this.dataSrc.display_name = res.display_name;
      let has_actions = [];
      res.actions.forEach( function ( item, index, array)  {
        has_actions.push(item.id);
      });
      this.dataSrc.actions = has_actions;
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        this.dataSrc[field] = value;
      });
    },
  },
};
</script>