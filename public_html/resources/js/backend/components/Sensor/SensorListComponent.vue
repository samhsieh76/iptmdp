<template>
  <v-content :sensor_menus="sensor_menus">
    <template #content_header
      ><slot name="breadcrumb"></slot>
      <button class="btn btn-add" @click="clickAdd" v-if="canCreate">
        {{ $t("add") + $t("sensor") }}
      </button>
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
      </v-data-table>
    </template>
  </v-content>
</template>
<script>
import DataTable from "../DataTableComponent.vue";
import contentComponent from "../ContentComponent.vue";
import $ from "jquery";
export default {
  components: {
    "v-content": contentComponent,
    "v-data-table": DataTable,
  },
  data() {
    return {
      searchValue: {},
      columns: [],
    };
  },
  mounted() {
    this.columns = this.fields.map((el) => {
      let render = null;
      switch (el) {
        case "latest_raw_data":
        case "latest_value":
          render = (data, topic, row) => {
            if (this.valueOptions != null) {
              return data != null
                ? `${this.valueOptions?.[data]}${this.valueUnit}`
                : "-";
            }
            return data ? `${data}${this.valueUnit}` : "-";
          };
          break;
        case "latest_updated_at":
          render = function (data, topic, row) {
            if (data) {
              const date = new Date(data);
              const formattedTime = `${date.getFullYear()}/${(
                date.getMonth() + 1
              )
                .toString()
                .padStart(2, "0")}/${date
                .getDate()
                .toString()
                .padStart(2, "0")} ${date
                .getHours()
                .toString()
                .padStart(2, "0")}:${date
                .getMinutes()
                .toString()
                .padStart(2, "0")}:${date
                .getSeconds()
                .toString()
                .padStart(2, "0")}`;
              return formattedTime;
            } else {
              return "-";
            }
          };
          break;
        case "is_notification":
          render = (data, topic, row) => {
            let switch_btn = `<label class="switch" id="notification_${row.id}">`;
            switch_btn += `<input type="checkbox" value="${
              row.id
            }" name="notification" ${data == "1" ? "checked" : ""} ${
              !this.canToggleNotification ? "disabled" : ""
            }>`;
            switch_btn += `<span class="slider"></span></label>`;
            return switch_btn;
          };
          break;
        default:
          break;
      }
      return {
        data: el,
        render: render,
      };
    });
  },
  props: {
    sensor_menus: {
      type: Array,
      default: [],
    },
    valueOptions: {
      type: Array,
    },
    canCreate: {
      type: Boolean,
    },
    fields: {
      type: Array,
      default: [],
    },
    thead: {
      type: Array,
    },
    url: {
      type: String,
    },
    toggleNotificationUrl: {
      type: String,
    },
    canToggleNotification: {
      type: Boolean,
      default: false,
    },
    valueUnit: {
      type: String,
      default: "",
    },
  },
  methods: {
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    },
    initializeDataTable() {
      let self = this;
      $("#dataTable").on(
        "change",
        "input[type='checkbox'][name='notification']",
        function () {
          let id = $(this).val();
          let is_notification = $(this).is(":checked");
          self
            .toggleNotification(id, is_notification)
            .then((res) => {
              if ("messages" in res) {
                self.$toast.success(res.messages);
              }
              self.$refs.dataTable.reload();
            })
            .catch(self.$utils.$errorHandler);
        }
      );
    },
    toggleNotification(id, is_notification) {
      return new Promise((resolve, reject) => {
        const requestType = "put";
        let options = {
          url: this.toggleNotificationUrl.replace("sensor_id", id),
          method: requestType,
          data: {
            is_notification: is_notification,
          },
        };
        axios
          .request(options)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    },
    handleChildMounted() {
      this.initializeDataTable();
    },
  },
};
</script>