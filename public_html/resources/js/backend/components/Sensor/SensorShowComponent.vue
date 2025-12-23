<template>
  <v-content :sensor_menus="sensor_menus">
    <template #content_header>
      <div class="card sensor-data">
        <div class="card-header">
          {{ sensor_type }} -
          <a :href="locationIndexUrl" v-if="canLocationsIndex">{{
            $t("backend")
          }}</a>
          <span v-if="!canLocationsIndex">{{ $t("backend") }}</span>
        </div>
        <div class="card-body">
          <div class="toilet-data" v-if="location != null">
            <div class="icon-location"></div>
            <a :href="toiletIndexUrl" v-if="canToiletsIndex">{{
              location.name
            }}</a>
            <span v-if="!canToiletsIndex">{{ location.name }}</span>
            ｜
            <a :href="toiletShowUrl" v-if="canToiletsShow">{{ toilet.name }}</a>
            <span v-if="!canToiletsShow">{{ toilet.name }}</span>
            ｜
            {{ $t("toilet_type_options")[toilet.type] }}
          </div>
          {{ sensor.name }}
        </div>
      </div>
      <div class="right-side">
        <button class="btn btn-add" @click="clickAdd" v-if="canCreate">
          {{ $t("add") }}
        </button>
        <table>
          <thead>
            <tr>
              <th>{{ $t("search_log") }}</th>
              <th v-if="canToggleNotification">
                {{ $t("is_notification") }}
              </th>
              <th v-if="canSendNotification">
                {{ $t("notification_message") }}
              </th>
              <th>{{ $t("data_chart") }}</th>
              <th v-if="canDownload">{{ $t("export_report") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="date-interval">
                <div
                  class="radio-group"
                  v-for="(option, index) in searchLogOptions"
                  :key="index"
                >
                  <input
                    :id="index"
                    type="radio"
                    :value="index"
                    v-model="searchValue.date_interval"
                    class="radio-input"
                  />
                  <label class="radio-label" :for="index">
                    <span class="radio-button"></span>
                    <span class="radio-text">{{ option }}</span>
                  </label>
                </div>
                <div class="date-group">
                  <VDatePicker
                    transparent
                    borderless
                    no-datetime
                    class="date-picker"
                    color="epa-color"
                    :masks="masks"
                    v-model="range"
                    locale="en"
                    :max-date="maxDate"
                    is-range
                  >
                    <template #default="{ inputValue, inputEvents }">
                      <div class="flex justify-center items-center">
                        <input
                          type="text"
                          :value="formatDate(inputValue.start)"
                          v-on="inputEvents.start"
                          :style="{
                            pointerEvents: range_disabled ? 'none' : 'auto',
                          }"
                        />
                        <label>—</label>
                        <input
                          type="text"
                          :value="formatDate(inputValue.end)"
                          v-on="inputEvents.end"
                          :style="{
                            pointerEvents: range_disabled ? 'none' : 'auto',
                          }"
                        />
                      </div>
                    </template>
                  </VDatePicker>
                  <button class="btn btn-search" @click="clickSearch"></button>
                </div>
              </td>
              <td v-if="canToggleNotification">
                <label class="switch">
                  <input
                    type="checkbox"
                    id="notification"
                    @change="changeNotification"
                    :checked="sensor.is_notification"
                    :disabled="!canToggleNotification"
                  />
                  <span class="slider"></span>
                </label>
              </td>
              <td v-if="canSendNotification">
                <button
                  class="btn btn-notification"
                  @click="clickSendNotification"
                ></button>
              </td>
              <td>
                <button
                  class="btn btn-toggle-chart"
                  @click="toggleDisplayMode"
                  v-if="isTable"
                ></button>
                <button
                  class="btn btn-toggle-data"
                  @click="toggleDisplayMode"
                  v-else
                ></button>
              </td>
              <td v-if="canDownload">
                <button class="btn btn-export" @click="downloadFile"></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
    <template #content>
      <v-data-table
        ref="dataTable"
        :url="url"
        :thead="thead"
        :columns="columns"
        :search-value="searchValue"
        :btn-simple="true"
        :order="[[orderTarget, 'desc']]"
        :has-action="false"
        :column-defs="[
          {
            targets: -1,
            orderable: false,
          },
        ]"
        @child-mounted="handleChildMounted"
        v-if="isTable"
      >
      </v-data-table>
      <v-log-bar
        ref="logChart"
        :url="chartDataUrl"
        :search-value="searchValue"
        :unit="chartUnit"
        :max="chartMax"
        :splitNumber="chartSplitNumber"
        v-else-if="isBar"
      >
      </v-log-bar>
      <v-log-chart
        ref="logChart"
        :url="chartDataUrl"
        :search-value="searchValue"
        :unit="chartUnit"
        :max="chartMax"
        :splitNumber="chartSplitNumber"
        v-else
      >
      </v-log-chart>
    </template>
  </v-content>
</template>
<script>
import DataTable from "../DataTableComponent.vue";
import contentComponent from "../ContentComponent.vue";
import LogChart from "../../plugin/LogChart.vue";
import LogBar from "../../plugin/LogBar.vue";
import moment from "moment";

export default {
  components: {
    "v-content": contentComponent,
    "v-data-table": DataTable,
    "v-log-chart": LogChart,
    "v-log-bar": LogBar,
  },
  data() {
    return {
      searchValue: {
        date_interval: "1",
        start_date: null,
        end_date: null,
      },
      columns: [],
      showDatePicker: false,
      isTable: true,
      masks: {
        title: "YYYY" + "年 " + "MM" + "月",
        weekdays: "WWW" + ".",
        navMonths: "MM" + "月",
      },
      maxDate: "",
      range: {
        start: null,
        end: null,
      },
    };
  },
  created() {
    const today = new Date();
    this.maxDate = today.toISOString().substring(0, 10);
  },
  mounted() {
    let is_adequate_options = this.$t("is_adequate_options");
    let loadingText = this.$t("calc_loading");
    this.columns = this.fields.map((el) => {
      let render = null;
      switch (el) {
        case "value":
          switch (this.sensor_type) {
            case this.$t("hand_lotion_sensors"):
              render = function (data, topic, row) {
                if (data == null) {
                  return "-";
                }
                if (data == 0 || data == 1) {
                  return is_adequate_options[data];
                }
                return loadingText;
              };
              break;
            case this.$t("toilet_paper_sensors"):
            case this.$t("relative_humidity_sensors"):
            case this.$t("temperature_sensors"):
              let unit = this.chartUnit ?? "";
              render = function (data, topic, row) {
                if (data == null) {
                  return "-";
                }
                return data == -1 ? loadingText : `${data}${unit}`;
              };
              break;
            case this.$t("smelly_sensors"):
              render = (data, topic, row) => {
                if (data == -1) {
                  return loadingText;
                }
                return `<span style="color: ${this.$utils.calcSmellyColor(
                  data
                )}">${this.$utils.calcSmellyScore(data)}</span>`;
              };
              break;
            default:
              render = function (data, topic, row) {
                if (data == null) {
                  return "-";
                }
                return data == -1 ? loadingText : data;
              };
              break;
          }
          break;
        case "data":
        case "updated_at":
          render = function (data, topic, row) {
            return data ?? "-";
          };
          break;
        case "delete_options":
          render = (data, type, row) => {
            if (data.length == 0) {
              return "-";
            }
            let res = data
              .map((el) => {
                return `<button class="btn btn-${el.action} simple" url="${
                  el.url
                }" action="${el.action}">${
                  this.btnSimple ? "" : el.label
                }</button>`;
              })
              .join("");
            return `<div class="datatable-action">${res}</div>`;
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

    // 初始化時間區間
    this.getDateByRange().then(({ start, end }) => {
      this.searchValue.start_date = start.valueOf();
      this.searchValue.end_date = end.valueOf();
    });
    window.addEventListener("resize", this.handleResize);
  },
  unmounted() {
    window.removeEventListener("resize", this.handleResize);
  },
  props: {
    sensor_type: {
      type: String,
    },
    sensor_menus: {
      type: Array,
      default: [],
    },
    canCreate: {
      type: Boolean,
      default: false,
    },
    canLocationsIndex: {
      type: Boolean,
      default: false,
    },
    canToiletsIndex: {
      type: Boolean,
      default: false,
    },
    canToiletsShow: {
      type: Boolean,
      default: false,
    },
    // 已經沒有此功能了
    canSendNotification: {
      type: Boolean,
      default: false,
    },
    canDownload: {
      type: Boolean,
      default: false,
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
    chartDataUrl: {
      type: String,
    },
    toggleNotificationUrl: {
      type: String,
    },
    canToggleNotification: {
      type: Boolean,
      default: false,
    },
    sendNotificationUrl: {
      type: String,
    },
    locationIndexUrl: {
      type: String,
    },
    toiletIndexUrl: {
      type: String,
    },
    toiletShowUrl: {
      type: String,
    },
    location: {
      type: Object,
    },
    toilet: {
      type: Object,
    },
    sensor: {
      type: Object,
    },
    searchLogOptions: {
      type: Object,
      required: true,
    },
    downloadUrl: {
      type: String,
    },
    chartMax: {
      type: String,
    },
    chartUnit: {
      type: String,
    },
    chartSplitNumber: {
      type: String,
    },
    orderTarget: {
      type: Number,
      default: 2,
    },
    isBar: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    async clickSearch() {
      try {
        const result = await this.getDateByRange();
        const { start, end } = result;
        this.searchValue.start_date = start.valueOf();
        this.searchValue.end_date = end.valueOf();

        this.$nextTick(() => {
          if (this.isTable) {
            this.$refs.dataTable.reload(false);
          } else {
            this.$refs.logChart.reload();
          }
        });
      } catch (error) {
        console.log(error);
        this.$toast.error(this.$t("date_range_required"));
        return;
      }
    },
    clickAdd() {
      this.$root.$refs.settingModal.$refs.modal.openCreateModal();
    },
    changeNotification() {
      let input_notification = document.getElementById("notification");
      let is_notification = input_notification.checked;
      this.toggleNotification(this.sensor.id, is_notification)
        .then((res) => {
          if ("messages" in res) {
            this.$toast.success(res.messages);
          }
          this.$refs.dataTable.reload();
        })
        .catch(this.$utils.$errorHandler);
    },
    clickSendNotification() {
      this.sendNotification(this.sensor.id)
        .then((res) => {
          if ("messages" in res) {
            this.$toast.success(res.messages);
          }
        })
        .catch(this.$utils.$errorHandler);
    },
    sendNotification(id) {
      return new Promise((resolve, reject) => {
        const requestType = "post";
        let options = {
          url: this.sendNotificationUrl.replace("sensor_id", id),
          method: requestType,
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
    handleChildMounted() {},
    formatDate(date) {
      if (date == null) {
        return null;
      }
      const formattedDate = new Date(date).toLocaleDateString("zh-TW", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      });
      return formattedDate;
    },
    async downloadFile() {
      const params = new URLSearchParams();
      // let startdate, enddate;
      try {
        let { start, end } = await this.getDateByRange();
        params.append("start_date", start.valueOf());
        params.append("end_date", end.valueOf());
        /* startdate = start.local().format("YMMDD");
        enddate = end.local().format("YMMDD"); */
      } catch (error) {
        console.log(error);
        this.$toast.error(this.$t("date_range_required"));
        return;
      }

      fetch(`${this.downloadUrl}?${params.toString()}`, {
        method: "GET",
      })
        .then(async (response) => {
          console.log(response);
          if (!response.ok) {
            throw response.statusText;
          }
          /* const encodedFilename = response.headers
            .get("Content-Disposition")
            .split("filename*=UTF-8''")[1];
          const filename = decodeURIComponent(encodedFilename);
          return response.blob().then((blob) => ({ filename, blob })); */
          return response.blob().then((blob) => blob);
        })
        .then((blob) => {
          // 文件名稱--先都只放當日...
          // let file_name = `${this.location.name}_${this.toilet.name}_${this.$t("toilet_type_options")[this.toilet.type]}_${this.sensor_type}_`;
          let file_name = `${this.sensor_type}${this.$t("sensor_log")}_${
            this.location.name
          }_${this.sensor.name}_`;
          /* if(startdate == enddate) {
            file_name = file_name + startdate;
          } else {
            file_name = file_name + startdate + "_" + enddate;
          } */
          file_name =
            file_name + moment().startOf("day").local().format("YMMDD");
          let filename = file_name + ".xlsx";
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
    getDateByRange() {
      return new Promise((resolve, reject) => {
        let start, end;
        switch (this.searchValue.date_interval) {
          case "2":
            start = moment().subtract(1, "day").startOf("day");
            end = moment().subtract(1, "day").endOf("day");
            break;
          case "3":
            const weekday = moment().day();
            start = moment().subtract(weekday, "days").startOf("day");
            end = moment()
              .add(6 - weekday, "days")
              .endOf("day");
            break;
          case "4":
            start = moment().startOf("month").startOf("day");
            end = moment().endOf("month").endOf("day");
            break;
          case "5":
            start = moment(this.range.start).startOf("day");
            end = moment(this.range.end).endOf("day");
            if (end.diff(start, "months", true) > 6) {
              this.$toast.error(this.$t("date_range_exceed"));
              reject();
            }
            break;
          case "1":
          default:
            start = moment().startOf("day");
            end = moment().endOf("day");
            break;
        }
        if (start && end) {
          resolve({ start, end });
        } else {
          reject();
        }
      });
    },
    toggleDisplayMode() {
      this.isTable = !this.isTable;
      this.clickSearch();
    },
    handleResize() {
      this.$refs.logChart?.resize();
    },
  },
  computed: {
    range_disabled() {
      return this.searchValue.date_interval != 5;
    },
  },
  watch: {
    "searchValue.date_interval": {
      handler(newValue, oldValue) {
        if (newValue != 5) {
          this.range = {
            start: null,
            end: null,
          };
        }
      },
    },
  },
};
</script>