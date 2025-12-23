<template>
  <v-content>
    <template #content_header>
      <div class="col">
        {{ $t("backend") }}
      </div>
      <div class="col btn-container">
        <button class="btn btn-add" @click="clickAdd" v-if="canCreate">
          {{ $t("add") + $t("location") }}
        </button>
        <button class="btn btn-add" @click="clickRequest" v-if="canRequest">
          {{ $t("location_request_permission") }}
        </button>
      </div>
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
                name="name"
                v-model="searchValue.name"
                :placeholder="`${$t('please_enter')}${$t('location_name')}`"
              />
            </div>
            <div class="col-auto" v-if="countySelectOptions.length > 1">
              <v-select2
                class="select2 form-control"
                name="county_id"
                :options="countySelectOptions"
                :placeholder="`${$t('please_choose')}${$t('location_county')}`"
                :settings="settings"
                v-model="searchValue.county_id"
              />
            </div>
            <div class="col-auto">
              <input
                type="text"
                class="form-control"
                name="address"
                v-model="searchValue.address"
                :placeholder="`${$t('please_enter')}${$t(
                  'location_management_address'
                )}`"
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
        this.$t("location_name"),
        this.$t("location_business_hours"),
        this.$t("location_county"),
        this.$t("location_management_address"),
        this.$t("created_at"),
        this.$t("action"),
      ],
      columns: [
        { data: "name" },
        { data: "business_hours" },
        { data: "county" },
        { data: "address" },
        { data: "created_at" },
      ],
      searchValue: {
        name: null,
        county: null,
        address: null,
      },
    };
  },
  props: {
    url: {
      type: String,
      required: true,
    },
    requestUrl: {
      type: String,
    },
    countyOptions: {
      type: Object,
      required: true,
    },
    canCreate: {
      type: Boolean,
      default: false,
    },
    canRequest: {
      type: Boolean,
      default: false,
    },
    locations: {
      type: Array,
    },
  },
  methods: {
    clickSearch() {
      this.$refs.dataTable.reload(false);
    },
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    },
    clickRequest() {
      this.$root.$refs.settingRequestModal.$refs.modal.openRequestModal(
        this.requestUrl
      );
    },
  },
  computed: {
    countySelectOptions() {
      const countyOptions = this.countyOptions;
      const countyMap = new Map();

      // 使用 forEach 迴圈遍歷 locations 陣列
      this.locations.forEach((location) => {
        let county = countyOptions[location.county_id];
        // 如果 countyMap 中不存在這個 county_id，則將它加入 Map 中
        if (!countyMap.has(location.county_id)) {
          countyMap.set(location.county_id, county);
        }
      });
      // 將 Map 轉換成陣列並返回
      const uniqueCounties = Array.from(countyMap, ([id, text]) => ({
        id,
        text,
      }));
      return uniqueCounties;
    },
  },
};
</script>