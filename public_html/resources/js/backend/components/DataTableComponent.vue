<template>
  <slot name="searchBox"></slot>
  <div class="row" v-if="shouldMount">
    <div class="col-12 table-responsive">
      <data-table
        :columns="current_columns"
        :options="options"
        :id="id"
        ref="datatable"
      >
        <thead>
          <tr>
            <th v-for="(table_th, index) in thead" :key="index">
              {{ table_th }}
            </th>
          </tr>
        </thead>
      </data-table>
    </div>
  </div>
</template>

<style>
@import "datatables.net-dt";
.datatable {
  background: #fff;
  border-radius: 20px;
}
.datatable th,
.datatable td {
  white-space: nowrap;
}
.dataTables_wrapper .dataTables_paginate {
  display: flex;
  align-items: center;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
  display: flex;
  border: none;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button:active {
  background: transparent;
  border: none;
  box-shadow: none;
}
.current_page {
  color: #2fa7cd;
}
table.dataTable thead > tr > th.sorting:before,
table.dataTable thead > tr > th.sorting:after,
table.dataTable thead > tr > th.sorting_asc:before,
table.dataTable thead > tr > th.sorting_asc:after,
table.dataTable thead > tr > th.sorting_desc:before,
table.dataTable thead > tr > th.sorting_desc:after,
table.dataTable thead > tr > th.sorting_asc_disabled:before,
table.dataTable thead > tr > th.sorting_asc_disabled:after,
table.dataTable thead > tr > th.sorting_desc_disabled:before,
table.dataTable thead > tr > th.sorting_desc_disabled:after,
table.dataTable thead > tr > td.sorting:before,
table.dataTable thead > tr > td.sorting:after,
table.dataTable thead > tr > td.sorting_asc:before,
table.dataTable thead > tr > td.sorting_asc:after,
table.dataTable thead > tr > td.sorting_desc:before,
table.dataTable thead > tr > td.sorting_desc:after,
table.dataTable thead > tr > td.sorting_asc_disabled:before,
table.dataTable thead > tr > td.sorting_asc_disabled:after,
table.dataTable thead > tr > td.sorting_desc_disabled:before,
table.dataTable thead > tr > td.sorting_desc_disabled:after {
  font-size: 1em !important;
}
table.dataTable thead > tr > th.sorting:before,
table.dataTable thead > tr > th.sorting_asc:before,
table.dataTable thead > tr > th.sorting_desc:before,
table.dataTable thead > tr > th.sorting_asc_disabled:before,
table.dataTable thead > tr > th.sorting_desc_disabled:before,
table.dataTable thead > tr > td.sorting:before,
table.dataTable thead > tr > td.sorting_asc:before,
table.dataTable thead > tr > td.sorting_desc:before,
table.dataTable thead > tr > td.sorting_asc_disabled:before,
table.dataTable thead > tr > td.sorting_desc_disabled:before {
  bottom: calc(50% + 0.1em);
}
</style>
<script>
import DataTable from "datatables.net-vue3";
import DataTablesCore from "datatables.net";

import $ from "jquery";

DataTable.use(DataTablesCore);

export default {
  emits: ["child-mounted"],
  components: {
    "data-table": DataTable,
  },
  props: {
    id: {
      type: String,
      default: "dataTable",
    },
    order: {
      type: Array,
      default: [],
    },
    url: {
      type: String,
      required: true,
    },
    thead: {
      type: Array,
      required: true,
    },
    hasAction: {
      type: Boolean,
      default: true,
    },
    columns: {
      type: Array,
      required: true,
      default: [],
    },
    searchValue: {
      type: Object,
      default: {},
    },
    columnDefs: {
      type: Array,
      default: [],
    },
    btnSimple: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      dt: null,
      tableHeight: 500,
      shouldMount: false,
    };
  },
  computed: {
    options() {
      let options = {
        processing: true,
        serverSide: true,
        searching: false,
        paging: true,
        lengthChange: false,
        info: false,
        language: this.$t("datatable_language"),
        pageLength: 20,
        scrollY: `${this.tableHeight}px`,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: this.current_columnDefs,
        // 自訂分頁渲染
        fnDrawCallback: function (settings) {
          const pagination = $(this)
            .closest(".dataTables_wrapper")
            .find(".dataTables_paginate");
          const pageInfo =
            settings._iDisplayStart / settings._iDisplayLength + 1;
          const totalRecords = settings.fnRecordsDisplay();
          const totalPages =
            totalRecords > 0
              ? Math.ceil(totalRecords / settings._iDisplayLength)
              : 1;

          // 替換文字為圖示
          pagination.find(".previous").html('<i class="icon page-left"></i>');
          pagination.find(".next").html('<i class="icon page-right"></i>');

          pagination
            .find(".previous")
            .after(
              `<span class="paginate_info"><span class="current_page">${pageInfo}</span>/${totalPages}</span>`
            );
          pagination
            .find(".paginate_info")
            .siblings()
            .not(".previous, .next")
            .remove();
        },
        ajax: {
          url: this.url,
          data: (d) => {
            for (const [key, value] of Object.entries(this.searchValue)) {
              d[key] = value;
            }
          },
        },
      };
      if (this.order.length > 0) {
        options.order = this.order;
      }
      return options;
    },
    current_columns() {
      if (this.hasAction) {
        return [
          ...this.columns,
          ...[
            {
              data: "options",
              render: (data, type, row) => {
                if (data.length == 0) {
                  return "-";
                }
                let res = data
                  .map((el) => {
                    return `<button class="btn btn-${el.action} ${
                      this.btnSimple ? "simple" : ""
                    }" url="${el.url}" action="${el.action}">${
                      this.btnSimple ? "" : el.label
                    }</button>`;
                  })
                  .join("");
                return `<div class="datatable-action">${res}</div>`;
              },
            },
          ],
        ];
      }
      return this.columns;
    },
    current_columnDefs() {
      if (this.hasAction) {
        return [
          ...this.columnDefs,
          ...[
            {
              targets: -1,
              orderable: false,
            },
          ],
        ];
      }
      return this.columnDefs;
    },
  },
  mounted() {
    this.$nextTick(() => {
      this.calculateHeight();
      this.shouldMount = true;
    });
  },
  updated() {
    if (this.shouldMount && !this.dt) {
      this.dt = this.$refs.datatable.dt;
      this.initializeDataTable();
      this.$emit("child-mounted");
    }
  },
  methods: {
    reload(stateSave = true) {
      if (stateSave) {
        this.dt.ajax.reload(null, false);
      } else {
        this.dt.ajax.reload();
      }
    },
    handleButtonClick(action, Url) {
      switch (action) {
        case "edit":
          this.$root.$refs.settingModal.$refs.modal.openEditModal(Url);
          break;
        case "password":
          this.$root.$refs.settingPasswordModal.$refs.modal.openPasswordModal(
            Url
          );
          break;
        case "restore":
          this.handleRestoreClick(Url);
          break;
        case "delete":
          this.handleDeleteClick(Url);
          break;
        case "disable":
          this.handleDeleteClick(Url, "停用");
          break;
        case "permission":
        case "show":
        case "full":
        case "data":
          window.location = Url;
          break;
        default:
          break;
      }
    },
    handleRestoreClick(Url) {
      if (!confirm("確定要恢復?")) {
        return false;
      }
      this.deleteResource(Url)
        .then((res) => {
          if ("messages" in res) {
            this.$toast.success(res.messages);
          }
          this.reload();
        })
        .catch(this.$utils.$errorHandler);
    },
    handleDeleteClick(Url, action = "刪除") {
      if (!confirm(`確定要${action}?`)) {
        return false;
      }
      this.deleteResource(Url)
        .then((res) => {
          if ("messages" in res) {
            this.$toast.success(res.messages);
          }
          this.reload();
        })
        .catch(this.$utils.$errorHandler);
    },
    deleteResource(url) {
      return new Promise((resolve, reject) => {
        axios
          .delete(url)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            console.log(error);
            reject(error);
          });
      });
    },
    calculateHeight() {
      let wrapper = document.getElementsByClassName("content-wrapper");
      let header = document.getElementsByClassName("content-header");
      let searchBox = document.getElementsByClassName("search-box");
      let searchBoxOffset = 0;
      if (searchBox.length > 0) {
        searchBoxOffset = searchBox[0].offsetHeight + 16;
      }
      this.tableHeight =
        wrapper[0].offsetHeight -
        (32 + 56 + 108) -
        header[0].offsetHeight -
        searchBoxOffset;
      if (this.tableHeight < 330) this.tableHeight = 330;
    },
    initializeDataTable() {
      let self = this;
      $(`#dataTable`).on("click", "button", function () {
        self.handleButtonClick($(this).attr("action"), $(this).attr("url"));
      });
    },
  },
};
</script>