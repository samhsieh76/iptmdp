
<template>
  <v-content>
    <template #content>
      <div class="grid-container">
        <div class="card card-info">
          <div
            class="bg-image hover-overlay ripple ripple-surface-light"
            data-mdb-ripple-color="light"
          >
            <img :src="location.image" class="img-fluid" />
            <a>
              <div
                class="mask"
                style="background-color: rgba(251, 251, 251, 0.15)"
              ></div>
            </a>
          </div>
          <div class="card-body">
            <div class="card-title">
              <svg
                width="21"
                height="29"
                viewBox="0 0 21 29"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M10.4976 28.9807C10.4533 28.9131 10.4188 28.8696 10.3843 28.8213L1.36525 15.3501C1.05019 14.8477 0.795503 14.3112 0.606269 13.7514C-0.315709 11.1923 -0.172217 8.3803 1.00568 5.92414C2.18358 3.46798 4.30111 1.56529 6.89986 0.627957C8.32581 0.118268 9.84706 -0.0843636 11.3601 0.0338532C14.0519 0.250834 16.5547 1.47626 18.3483 3.45534C20.1418 5.43442 21.0881 8.01485 20.9902 10.6601C20.9355 12.2319 20.5087 13.7698 19.7433 15.1521C19.6709 15.2783 19.592 15.4009 19.5068 15.5192L10.5962 28.8648L10.4976 28.9807ZM3.94281 10.2785C3.93891 11.552 4.32072 12.798 5.0399 13.8587C5.75908 14.9193 6.78326 15.7469 7.98273 16.2367C9.1822 16.7265 10.503 16.8564 11.7779 16.6099C13.0527 16.3635 14.2242 15.7518 15.1441 14.8523C16.064 13.9528 16.6908 12.806 16.9451 11.5572C17.1995 10.3083 17.0699 9.01355 16.5729 7.8369C16.0758 6.66025 15.2337 5.65464 14.153 4.94742C13.0724 4.24019 11.802 3.86318 10.5025 3.86413C9.64198 3.86286 8.7896 4.0278 7.99412 4.34953C7.19864 4.67126 6.47564 5.14346 5.86645 5.73915C5.25726 6.33485 4.77381 7.04236 4.44375 7.82124C4.11368 8.60013 3.94346 9.43513 3.94281 10.2785Z"
                  fill="white"
                />
              </svg>

              {{ location.name }}
            </div>
            <hr />
            <div class="sensor-box" v-if="sensorCounts">
              <div
                class="item"
                v-for="(item, sensor) in sensorCounts"
                :key="sensor"
              >
                {{ $t(`${sensor}_count`).replace(":num", item.count) }}
              </div>
            </div>
          </div>
        </div>

        <div class="toilet-info d-flex" style="grid-column: span 3">
          <div style="margin-right: 2rem">
            <span class="title">{{ $t("toilet_name") }}</span
            ><br />
            <span class="value"
              >{{ toilet.name }} - {{ typeOptions[toilet.type] }}</span
            >
          </div>
          <div>
            <span class="title">{{ $t("toilet_code") }}</span
            ><br />
            <span class="value">{{ toilet.code }}</span>
          </div>
        </div>
        <div class="card card-sensor">
          <div class="card-header">
            <div class="card-title">{{ $t("toilet_paper_sensors") }}</div>
            <div class="card-tools">
              <div
                class="btn-show-info"
                v-if="sensorCounts && sensorCounts.toilet_paper.showUrl"
              >
                <a :href="sensorCounts.toilet_paper.showUrl">{{
                  $t("detailed_data")
                }}</a>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <paper-gauge
              v-show="toiletPaperHasData"
              ref="toiletPaperGauge"
            ></paper-gauge>
            <div class="empty_data" v-show="!toiletPaperHasData">
              {{ $t("dashboard_empty_data") }}
            </div>
          </div>
        </div>
        <div class="card card-sensor human_traffic">
          <div class="card-header">
            <div class="card-title">{{ $t("human_traffic_sensors") }}</div>
            <div class="card-tools">
              <div
                class="btn-show-info"
                v-if="sensorCounts && sensorCounts.human_traffic.showUrl"
              >
                <a :href="sensorCounts.human_traffic.showUrl">{{
                  $t("detailed_data")
                }}</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <human-traffic-chart
              v-show="humanTrafficHasData"
              ref="humanTrafficChart"
            ></human-traffic-chart>
            <div class="empty_data" v-show="!humanTrafficHasData">
              {{ $t("dashboard_empty_data") }}
            </div>
          </div>
        </div>
        <div class="calendar card">
          <VDatePicker
            transparent
            borderless
            no-datetime
            class="date-picker"
            color="epa-color"
            :masks="masks"
            v-model="chooseDate"
            locale="en"
            is-required
            :min-date="minDate"
            :max-date="maxDate"
          ></VDatePicker>
        </div>
        <div class="card card-sensor">
          <div class="card-header">
            <div class="card-title">{{ $t("smelly_sensors") }}</div>
            <div class="card-tools">
              <div
                class="btn-show-info"
                v-if="sensorCounts && sensorCounts.smelly.showUrl"
              >
                <a :href="sensorCounts.smelly.showUrl">{{
                  $t("detailed_data")
                }}</a>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <smelly-gauge
              v-show="smellyHasData"
              ref="smellyGauge"
            ></smelly-gauge>
            <div class="empty_data" v-show="!smellyHasData">
              {{ $t("dashboard_empty_data") }}
            </div>
          </div>
        </div>
        <div class="card card-sensor hand-lotion">
          <div class="card-header">
            <div class="card-title">{{ $t("hand_lotion_sensors") }}</div>
            <div class="card-tools">
              <div
                class="btn-show-info"
                v-if="sensorCounts && sensorCounts.hand_lotion.showUrl"
              >
                <a :href="sensorCounts.hand_lotion.showUrl">{{
                  $t("detailed_data")
                }}</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div v-if="handLotionHasData">
              <div class="statistics-box">
                <div class="statistics-item">
                  <span>{{ $t("hand_lotion_total") }}</span>
                  <h1>
                    {{
                      (hand_lotion.full_count ?? 0) +
                      (hand_lotion.empty_count ?? 0)
                    }}
                  </h1>
                </div>
                <div class="statistics-item">
                  <span>{{ $t("hand_lotion_empty_num") }}</span>
                  <h1 class="text-insufficient">
                    {{ hand_lotion.empty_count }}
                  </h1>
                </div>
              </div>
              <div v-if="hand_lotion.empty_sensors.length > 0">
                <span>{{ $t("hand_lotion_empty_location") }}</span>
                <table class="sensor-list">
                  <thead>
                    <th>{{ $t("frontend_number") }}</th>
                    <th>{{ $t("ID") }}</th>
                    <th>{{ $t("frontend_sensor_name") }}</th>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(empty_sensor, index) in hand_lotion.empty_sensors"
                      :key="empty_sensor.id"
                    >
                      <td>{{ index + 1 }}</td>
                      <td>{{ empty_sensor.id }}</td>
                      <td>{{ empty_sensor.name }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="empty_data" v-else>
              {{ $t("dashboard_empty_data") }}
            </div>
          </div>
        </div>
        <div class="card card-sensor temp-humidity">
          <div class="card-header">
            <div class="card-title">{{ $t("temp_humidity_sensors") }}</div>
            <div class="card-tools">
              <div
                class="btn-show-info"
                v-if="
                  sensorCounts &&
                  (sensorCounts.temperature.showUrl ||
                    sensorCounts.relative_humidity.showUrl)
                "
              >
                <a :href="sensorCounts.temperature.showUrl">{{
                  $t("detailed_data")
                }}</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="grid-template" v-if="tempHasData || humidityHasData">
              <div class="sensor-item" v-if="tempHasData">
                <span class="title">{{ $t("temperature") }}</span>
                <div class="sensor-info">
                  <span
                    class="sensor-grading"
                    :style="{ color: currentTemp?.color }"
                    >{{ currentTemp?.text }}</span
                  >
                  <span class="sensor-value">{{ currentTemp?.value }}˚C</span>
                </div>
              </div>
              <div class="empty_data" v-else>
                {{ $t("dashboard_empty_data") }}
              </div>
              <hr />
              <div class="sensor-item" v-if="humidityHasData">
                <span class="title">{{ $t("relative_humidity") }}</span>
                <div class="sensor-info">
                  <span
                    class="sensor-grading"
                    :style="{ color: currentHumidity?.color }"
                    >{{ currentHumidity?.text }}</span
                  >
                  <span class="sensor-value">{{ currentHumidity?.value }}%</span>
                </div>
              </div>
              <div class="empty_data" v-else>
                {{ $t("dashboard_empty_data") }}
              </div>
              <MDBTooltip
                direction="top"
                class="chart-info"
                v-model="tooltip_chart"
              >
                <template #reference>
                  <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <g clip-path="url(#clip0_130_67)">
                      <path
                        d="M12.26 23.11H10.89C10.89 23.11 10.8 23.08 10.75 23.08C8.73 22.93 6.86 22.31 5.18 21.17C2.34 19.23 0.64 16.55 0.11 13.14C0.06 12.84 0.03 12.53 0 12.22C0 11.78 0 11.33 0 10.89C0.01 10.82 0.03 10.76 0.04 10.69C0.11 10.2 0.15 9.7 0.25 9.21C1.59 2.88 7.79 -1.12 14.1 0.280003C18.67 1.29 22.26 5.09 22.99 9.72C23.05 10.1 23.09 10.48 23.14 10.86V12.26C23.14 12.26 23.11 12.36 23.11 12.4C22.99 14.03 22.56 15.57 21.77 16.99C19.87 20.38 16.99 22.4 13.13 23C12.84 23.05 12.54 23.07 12.25 23.11H12.26ZM21.28 11.56C21.28 6.22 16.95 1.87 11.61 1.87C6.23 1.87 1.88 6.19 1.88 11.55C1.88 16.89 6.21 21.22 11.58 21.24C16.91 21.26 21.28 16.9 21.28 11.56Z"
                        fill="#3FB1D5"
                      />
                      <path
                        d="M10.21 10.15C10.02 10.29 9.88001 10.41 9.73001 10.52C9.35001 10.81 8.93001 10.77 8.63001 10.43C8.33001 10.09 8.35001 9.59999 8.68001 9.27999C8.72001 9.23999 8.76001 9.21 8.80001 9.18C9.58001 8.63 10.36 8.07999 11.14 7.53999C11.67 7.17999 12.34 7.48999 12.38 8.11999C12.38 8.24999 12.36 8.39999 12.32 8.52999C11.97 9.75999 11.72 11.01 11.59 12.28C11.44 13.68 11.45 15.08 11.64 16.48C11.67 16.67 11.83 16.8 12.04 16.81C12.24 16.81 12.43 16.68 12.47 16.51C12.57 16.04 12.88 15.81 13.33 15.86C13.71 15.9 14 16.24 14.01 16.67C14.03 17.66 13.45 18.52 12.56 18.79C12 18.96 11.46 18.87 10.96 18.56C10.5 18.28 10.14 17.89 9.94001 17.39C9.82001 17.08 9.80001 16.73 9.75001 16.39C9.45001 14.32 9.58001 12.28 10.18 10.27C10.18 10.25 10.19 10.22 10.21 10.14V10.15Z"
                        fill="#3FB1D5"
                      />
                      <path
                        d="M10.36 4.86C10.36 4.19 10.92 3.65 11.59 3.66C12.24 3.67 12.8 4.25 12.79 4.91C12.78 5.55 12.19 6.11 11.55 6.1C10.91 6.09 10.35 5.51 10.35 4.87L10.36 4.86Z"
                        fill="#3FB1D5"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_130_67">
                        <rect width="23.15" height="23.11" fill="white" />
                      </clipPath>
                    </defs>
                  </svg>
                </template>
                <template #tip>
                  <span>
                    溫度分級如下：<br>
                    <b>一、寒冷：8°C 以下。</b><br>
                    <b>二、涼冷：8°C~13°C。</b><br>
                    <b>三、涼爽：13°C~18°C。</b><br>
                    <b>四、舒適：18°C~23°C。</b><br>
                    <b>五、溫暖：23°C~29°C。</b><br>
                    <b>六、暖熱：29°C~35°C。</b><br>
                    <b>七、炎熱：35°C 以上。</b><br>
                    濕度分級如下：<br><b>一、乾燥：0%~40%。</b><br><b>二、合宜：40%~70%。</b><br><b>三、潮濕：70%~100%</b></span>
                </template>
              </MDBTooltip>
            </div>
            <div class="empty_data" v-else>
              {{ $t("dashboard_empty_data") }}
            </div>
          </div>
        </div>
      </div>
    </template>
  </v-content>
</template>
<script>
import {
  onMounted,
  ref,
  watch,
  nextTick,
  shallowRef,
  getCurrentInstance,
} from "vue";
import contentComponent from "../ContentComponent.vue";
import ToiletPaperGauge from "../../plugin/ToiletPaperGauge";
import SmellyGauge from "../../plugin/SmellyGauge";
import RangeIndicator from "../../plugin/RangeIndicator";
import HumanTrafficChart from "../../plugin/HumanTrafficChart";
import moment from "moment";
import { MDBTooltip } from "mdb-vue-ui-kit";

export default {
  components: {
    "v-content": contentComponent,
    "paper-gauge": ToiletPaperGauge,
    "smelly-gauge": SmellyGauge,
    "range-indicator": RangeIndicator,
    "human-traffic-chart": HumanTrafficChart,
    MDBTooltip,
  },
  props: {
    location: {
      type: Object,
    },
    toilet: {
      type: Object,
    },
    isRequired: {
      type: Boolean,
      default: true,
    },
    typeOptions: {
      type: Object,
    },
    sensorCounts: {
      type: Object,
    },
    toiletPaperDataUrl: {
      type: String,
    },
    humanTrafficDataUrl: {
      type: String,
    },
    smellyDataUrl: {
      type: String,
    },
    handLotionDataUrl: {
      type: String,
    },
    tempHumidityDataUrl: {
      type: String,
    },
  },
  setup(props) {
    const masks = {
      title: "YYYY" + "年 " + "MM" + "月",
      weekdays: "WWW" + ".",
      navMonths: "MM" + "月",
    };
    let chooseDate = ref(new Date());
    let minDate = "";
    let maxDate = "";
    const hand_lotion = {
      empty_count: "-",
      full_count: "-",
      empty_sensors: [],
    };
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();
    const toiletPaperGauge = shallowRef(null);
    const humanTrafficChart = shallowRef(null);
    const smellyGauge = shallowRef(null);
    const tooltip_chart = ref(null);

    let currentTemp = ref(false);
    let currentHumidity = ref(false);
    let toiletPaperHasData = ref(false);
    let humanTrafficHasData = ref(false);
    let smellyHasData = ref(false);
    let tempHasData = ref(false);
    let handLotionHasData = ref(false);
    let humidityHasData = ref(false);

    const today = new Date();
    minDate = moment(today).subtract(6, "months").format("YYYY-MM-DD");
    maxDate = moment(today).format("YYYY-MM-DD");

    onMounted(() => {
      nextTick(() => {
        window.addEventListener("resize", handleResize);
        handleUpdateData();
      });
    });

    const handleResize = () => {
      toiletPaperGauge.value?.resize();
      humanTrafficChart.value?.resize();
      smellyGauge.value?.resize();
    };

    const handleUpdateData = () => {
      updateToiletPaperData();
      updateHumanTrafficData();
      updateSmellyData();
      updateHandLotionData();
      updateTempHumidityData();
    };

    // 更新資料
    const updateToiletPaperData = () => {
      getToiletPaperData()
        .then((res) => {
          toiletPaperHasData.value = true;
          nextTick(() => {
            toiletPaperGauge.value?.updateData(res);
          });
        })
        .catch((error) => {
          toiletPaperHasData.value = false;
        });
    };
    const updateHumanTrafficData = () => {
      getHumanTrafficData()
        .then((res) => {
          humanTrafficHasData.value = true;
          nextTick(() => {
            humanTrafficChart.value?.updateData(res);
          });
        })
        .catch((error) => {
          humanTrafficHasData.value = false;
        });
    };
    const updateSmellyData = () => {
      getSmellyData()
        .then((res) => {
          smellyHasData.value = true;
          nextTick(() => {
            smellyGauge.value?.updateData(res);
          });
        })
        .catch((error) => {
          smellyHasData.value = false;
        });
    };
    const updateHandLotionData = () => {
      getHandLotionData()
        .then((res) => {
          handLotionHasData.value = true;
          hand_lotion.empty_count = res.empty_count ?? 0;
          hand_lotion.full_count = res.full_count ?? 0;
          hand_lotion.empty_sensors = res.empty_sensors ?? [];
        })
        .catch((error) => {
          handLotionHasData.value = false;
        });
    };
    const updateTempHumidityData = () => {
      getTempHumidityData()
        .then((res) => {
          tempHasData.value = res.temp_average_value != null;
          humidityHasData.value = res.humidity_average_value != null;
          nextTick(() => {
            if (tempHasData.value) {
              currentTemp.value = globalProperties.$utils.tempGradingCriteria(
                res.temp_average_value
              );
            }
            if (humidityHasData.value) {
              currentHumidity.value = globalProperties.$utils.humidityGradingCriteria(
                res.humidity_average_value
              );
            }
          });
        })
        .catch((error) => {
          currentTemp.value = {};
          currentHumidity.value = {};
          tempHasData.value = false;
          humidityHasData.value = false;
        });
    };
    // 取得資料
    const getToiletPaperData = () => {
      return new Promise((resolve, reject) => {
        axios
          .get(props.toiletPaperDataUrl, {
            params: {
              date: getChooseDate(),
            },
          })
          .then((res) => {
            if (res.average_value == null) {
              return reject();
            }
            resolve(res.average_value);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };
    const getHumanTrafficData = () => {
      return new Promise((resolve, reject) => {
        axios
          .get(props.humanTrafficDataUrl, {
            params: {
              date: getChooseDate(),
            },
          })
          .then((res) => {
            if (res.latest_data == null) {
              return reject();
            }
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };
    const getSmellyData = () => {
      return new Promise((resolve, reject) => {
        axios
          .get(props.smellyDataUrl, {
            params: {
              date: getChooseDate(),
            },
          })
          .then((res) => {
            if (res.average_value == null) {
              return reject();
            }
            resolve(res.average_value);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };
    const getHandLotionData = () => {
      return new Promise((resolve, reject) => {
        axios
          .get(props.handLotionDataUrl, {
            params: {
              date: getChooseDate(),
            },
          })
          .then((res) => {
            if (res.empty_count + res.full_count == 0) {
              return reject();
            }
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };
    const getTempHumidityData = () => {
      return new Promise((resolve, reject) => {
        axios
          .get(props.tempHumidityDataUrl, {
            params: {
              date: getChooseDate(),
            },
          })
          .then((res) => {
            if (
              res.temp_average_value == null &&
              res.humidity_average_value == null
            ) {
              return reject();
            }
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };

    const handleDateChange = () => {
      handleUpdateData();
    };

    const getChooseDate = () => {
      if (chooseDate.value) {
        let year = chooseDate.value.getFullYear();
        let month = chooseDate.value.getMonth() + 1;
        month = String(month).padStart(2, "0");
        let date = chooseDate.value.getDate();
        date = String(date).padStart(2, "0");
        return `${year}-${month}-${date}`;
      }
      return null;
    };

    watch(
      () => chooseDate.value,
      (newValue, oldValue) => {
        handleUpdateData();
      }
    );

    return {
      masks,
      chooseDate,
      minDate,
      maxDate,
      hand_lotion,
      toiletPaperGauge,
      humanTrafficChart,
      smellyGauge,
      currentTemp,
      currentHumidity,
      toiletPaperHasData,
      humanTrafficHasData,
      smellyHasData,
      tempHasData,
      handLotionHasData,
      humidityHasData,
      tooltip_chart
    };
  },
};
</script>