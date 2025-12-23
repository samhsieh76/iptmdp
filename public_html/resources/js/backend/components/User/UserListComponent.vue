<template>
  <v-content>
    <template #content_header
      >{{ $t("user_management") }}
      <button class="btn btn-add" @click="clickAdd" v-if="canCreate">
        {{ $t("add") + $t("user") }}
      </button>
    </template>
    <template #content>
      <v-data-table
        :url="url"
        :thead="thead"
        :columns="columns"
        :search-value="searchValue"
        ref="dataTable"
      >
        <template #searchBox>
          <div class="row mt-2 mb-2 search-box">
            <div class="col-auto">
              <input
                type="text"
                class="form-control"
                name="username"
                v-model="searchValue.username"
                :placeholder="`${$t('please_enter')}${$t('user')}`"
              />
            </div>
            <div class="col-auto">
              <input
                type="text"
                class="form-control"
                name="name"
                v-model="searchValue.name"
                :placeholder="`${$t('please_enter')}${$t('user_name')}`"
              />
            </div>
            <div class="col-auto">
              <v-select2
                class="select2 form-control"
                name="role_id"
                :options="
                  roleOptions.map((el) => {
                    return {
                      id: el.id,
                      text: el.name,
                    };
                  })
                "
                :placeholder="`${$t('please_choose')}${$t('user_role')}`"
                :settings="settings"
                v-model="searchValue.role_id"
              />
            </div>
            <div class="col-auto">
              <button class="btn btn-search" @click="clickSearch">
                {{ $t("search") }}
              </button>
            </div>
          </div>
        </template>
      </v-data-table>
    </template>
  </v-content>
</template>

<style>
.select2-container--default
  .select2-selection--single
  .select2-selection__rendered {
  line-height: 25px;
}
.select2-container {
  min-width: 200px;
}
.select2.form-control {
  min-width: 200px;
}
</style>
<script>
import Select2 from "vue3-select2-component";
import DataTable from "../DataTableComponent.vue";
import contentComponent from "../ContentComponent.vue";

export default {
  components: {
    "v-data-table": DataTable,
    "v-content": contentComponent,
    "v-select2": Select2,
  },
  data() {
    return {
      settings: {
        allowClear: true,
        language: { noResults: () => this.$t("not_found") },
      },
      thead: [
        this.$t("user"),
        this.$t("user_role"),
        this.$t("user_field_name"),
        this.$t("user_email"),
        this.$t("user_phone"),
        this.$t("created_at"),
        this.$t("action"),
      ],
      columns: [
        { data: "username" },
        { data: "role" },
        { data: "name" },
        { data: "email" },
        { data: "phone" },
        { data: "created_at" },
      ],
      searchValue: {
        name: null,
        username: null,
        role_id: null
      },
    };
  },
  props: {
    url: {
      type: String,
      required: true,
    },
    canCreate: {
      type: Boolean,
      default: false,
    },
    roleOptions: {
      type: Array,
      default: [],
    },
  },
  methods: {
    clickSearch() {
      this.$refs.dataTable.reload(false);
    },
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    },
  },
};
</script>