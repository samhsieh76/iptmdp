<template>
  <v-content>
    <template #content_header
      ><slot name="breadcrumb"></slot>
      <div class="d-flex">
        <a class="btn btn-view-data" :href="sensorsUrl" v-if="canSensor">{{ $t("view_data") }}</a>
        <span class="bewteen-btn" v-if="canSensor && canCreate"></span>
        <button class="btn btn-add" @click="clickAdd" v-if="canCreate">{{ $t("add") + $t("toilet")}}</button>
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
                name="code"
                v-model="searchValue.code"
                :placeholder="`${$t('please_enter')}${$t('toilet_code')}`"
              />
            </div>
            <div class="col-auto">
              <input
                type="text"
                class="form-control"
                name="name"
                v-model="searchValue.name"
                :placeholder="`${$t('please_enter')}${$t('toilet_name')}`"
              />
            </div>
            <div class="col-auto">
              <v-select2
                class="select2 form-control"
                name="type"
                :options="
                  Object.entries(typeOptions).map((el) => {
                    return {
                      id: el[0],
                      text: el[1],
                    };
                  })
                "
                :placeholder="`${$t('please_choose')}${$t('toilet_type')}`"
                :settings="settings"
                v-model="searchValue.type"
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
.bewteen-btn {
  border: 0.15rem solid #E8EBF0;
  margin: 0.2rem 1.2rem;
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
        this.$t("toilet_code"),
        this.$t("toilet_name"),
        this.$t("toilet_type"),
        this.$t("toilet_device_key"),
        this.$t("created_at"),
        this.$t("action"),
      ],
      columns: [
        { data: "code" },
        { data: "name" },
        {
          data: "type",
          render: (data, type, row) => {
            return this.typeOptions[data];
          },
        },
        { data: "device_key", visible: this.userLevel < 1 },
        { data: "created_at" },
      ],
      searchValue: {
        code: null,
        name: null,
        type: null,
      },
    };
  },
  props: {
    url: {
      type: String,
      required: true,
    },
    sensorsUrl: {
      type: String
    },
    typeOptions: {
      type: Object,
      required: true,
    },
    canSensor: {
      type: Boolean,
      default: false
    },
    canCreate: {
      type: Boolean,
      default: false
    },
    userLevel: {
      type: String
    }
  },
  methods: {
    clickSearch() {
      this.$refs.dataTable.reload(false);
    },
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    },
  },
  computed: {
    selectTypeOptions() {
      let res = [];
      for (let key in this.typeOptions) {
        res.push({
          id: key,
          value: this.typeOptions[key],
        });
      }
      return res;
    },
  },
};
</script>