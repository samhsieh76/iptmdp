<template>
  <v-content>
    <template #content_header>{{ $t('role_management') }}<button class="btn btn-add" @click="clickAdd" v-if="canCreate">{{ $t('add') }}</button></template>
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
                name="name"
                v-model="searchValue.name"
                :placeholder="`${$t('please_enter')}${$t('role_name')}`"
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
<script>
import DataTable from "../DataTableComponent.vue";
import contentComponent from "../ContentComponent.vue";

export default {
  components: {
    "v-data-table": DataTable,
    "v-content": contentComponent,
  },
  data() {
    return {
      thead: [
        this.$t("role_name"),
        this.$t("role_level"),
        this.$t("role_group"),
        this.$t("action"),
      ],
      columns: [
        { data: "name" },
        { data: "level" },
        { data: "role_group" },
      ],
      searchValue: {
        name: null,
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
      default: false
    },
  },
  methods: {
    clickSearch() {
      this.$refs.dataTable.reload(false);
    },
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    }
  },
};
</script>
