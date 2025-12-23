<template>
  <v-content>
    <template #content_header>
      <div class="col">
        {{ $t("api_and_server") }}
      </div>
      <div class="col btn-container">
        <button class="btn btn-common" @click="downloadFile" v-if="canDownload">{{ $t('download_api_document') }}</button>
        <span class="bewteen-btn" v-if="canDownload && canRequest"></span>
        <button class="btn btn-add" @click="clickRequest" v-if="canRequest">{{ $t('location_request_permission') }}</button>
      </div>
    </template>
    <template #content>
      <v-data-table
        :url="url"
        :thead="thead"
        :columns="columns"
        :search-value="searchValue"
        ref="dataTable"
        @child-mounted="handleChildMounted"
      >
        <template #searchBox>
          <div class="row mt-2 mb-2 search-box">
            <div class="col-auto" v-if="userLevel != '1'">
              <input
                type="text"
                class="form-control"
                name="location"
                v-model="searchValue.location"
                :placeholder="`${$t('please_enter')}${$t('location_name')}`"
              />
            </div>
            <div class="col-auto">
              <v-select2
                class="select2 form-control"
                name="status"
                :options="
                  Object.entries(statusOptions).map((el) => {
                    return {
                      id: el[0],
                      text: el[1],
                    };
                  })
                "
                :placeholder="`${$t('please_choose')}${$t(
                  'location_supplier_status'
                )}`"
                :settings="settings"
                v-model="searchValue.status"
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

import $ from "jquery";

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
        this.$t("supplier"),
        this.$t("api_and_serve_created_at"),
        this.$t("api_and_serve_status"),
        this.$t("action"),
      ],
      columns: [
        { data: "location.name", visible: this.userLevel != "1"  },
        { data: "supplier.name", visible: this.userLevel != "0"  },
        { data: "created_at" },
        {
          data: "status",
          render: (data, topic, row) => {
            let switch_btn = `<label class="switch">`;
            switch_btn =
              switch_btn +
              `<input type="checkbox" value="${row.id}" name="permission_${
                row.id
              }" ${this.canEdit != "1" ? "disabled" : ""} ${
                data == "1" ? "checked" : ""
              }>`;
            switch_btn = switch_btn + `<span class="slider"></span></label>`;
            return switch_btn;
          },
        },
      ],
      searchValue: {
        location: null,
        status: null,
      },
    };
  },
  props: {
    url: {
      type: String,
      required: true,
    },
    permissionUrl: {
      type: String,
      required: true,
    },
    requestUrl: {
      type: String
    },
    statusOptions: {
      type: Object,
      required: true,
    },
    canEdit: {
      type: Boolean,
      default: false,
    },
    canDownload: {
      type: Boolean,
      default: false
    },
    canRequest: {
      type: Boolean,
      default: false
    },
    downloadUrl: {
      type: String
    },
    userLevel: {
      type: String
    }
  },
  mounted() {},
  methods: {
    clickSearch() {
      this.$refs.dataTable.reload(false);
    },
    updateLocationPermission(id, checked) {
      return new Promise((resolve, reject) => {
        const requestType = "post";
        let options = {
          url: this.permissionUrl,
          method: requestType,
          data: {
            id: id,
            checked: checked,
          },
        };
        axios
          .request(options)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            console.log(error);
            reject(error);
          });
      });
    },
    handleChildMounted() {
      let self = this;
      this.$nextTick(() => {
        $(`#dataTable`).on("change", "input[type='checkbox']", function () {
          let id = $(this).val();
          let checked = $(this).is(":checked");
          if (!self.canEdit) {
            self.$toast.warning("無法更改場域授權！");
            self.$refs.dataTable.reload();
            return;
          }
          if (!confirm("確認更改場域授權？")) {
            self.$refs.dataTable.reload();
            return;
          }
          self
            .updateLocationPermission(id, checked)
            .then((res) => {
              if ("messages" in res) {
                self.$toast.success(res.messages);
              }
              self.$refs.dataTable.reload();
            })
            .catch(self.$utils.$errorHandler);
        });
      });
    },
    downloadFile() {
      /* axios({
        url: this.downloadUrl, // 伺服器端下載路由的 URL
        method: 'GET',
        responseType: 'blob', // 設定回應型態為 blob
      }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'sensor_API_document_V1.pdf'); // 設定下載檔案的名稱
        document.body.appendChild(link);
        link.click();
        link.remove();
      }).catch(error => {
        console.error(error);
      }); */

      fetch(`${this.downloadUrl}`, {
        method: "GET",
      })
        .then(async (response) => {
          const encodedFilename = response.headers
            .get("Content-Disposition")
            .split("filename*=UTF-8''")[1];
          const filename = decodeURIComponent(encodedFilename);
          return response.blob().then((blob) => ({ filename, blob }));
        })
        .then(({ filename, blob }) => {
          // 創建下載連結
          const downloadLink = document.createElement("a");
          downloadLink.href = URL.createObjectURL(blob);
          downloadLink.download = filename;
          downloadLink.click();
          // 釋放創建的下載連結
          URL.revokeObjectURL(downloadLink.href);
        })
        .catch((error) => {
          console.log(error);
          this.$toast.error("下載文件失敗");
        });
    },
    clickRequest() {
      // console.log('click request');
      this.$root.$refs.settingRequestModal.$refs.modal.openRequestModal(this.requestUrl);
    }
  },
};
</script>