<template>
  <v-section-box title="今日人流統計" :hasContent="dailyStatisticsHasContent">
    <template #section-content>
      <div class="traffic-chart">
        <div class="statistics-box" :style="{ minWidth: '180px' }">
          <!-- <span>累積人流總數</span>
          <div>
            <div class="total_text middle" style="display: inline">
              {{ $utils.$formatNumberWithComma(accumulatedCount) }}
            </div>
            <span>人</span>
          </div>
          <span class="text-rise" v-if="latest_data && latest_data > 0"
            >{{ latest_data }}↑</span
          > -->
          <div class="statistics-item">
            <span class="top-align">{{
              $t("human_traffic_weekday_average")
            }}</span>
            <span class="statistics-value">
              {{ $utils.$formatNumberWithComma(averageWeekday) }}
            </span>
            <span class="bottom-align">人</span>
          </div>
          <div class="statistics-item">
            <span class="top-align">{{
              $t("human_traffic_weekend_average")
            }}</span>
            <span class="statistics-value">
              {{ $utils.$formatNumberWithComma(averageWeekend) }}
            </span>
            <span class="bottom-align">人</span>
          </div>
          <div class="statistics-item mt-2">
            <span class="top-align">{{
              $t("human_traffic_current_accumulated")
            }}</span>
            <span
              :class="['statistics-value larger-text', { over: overAverage }]"
              >{{ $utils.$formatNumberWithComma(accumulatedCount) }}
            </span>
            <span class="bottom-align">人</span>
          </div>
        </div>
        <div class="chart-wrapper">
          <human-traffic-bar ref="dailyTrafficBar"></human-traffic-bar>
        </div>
        <MDBTooltip direction="top" class="chart-info" v-model="tooltip_chart">
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
                  fill="#00FFCF"
                />
                <path
                  d="M10.21 10.15C10.02 10.29 9.88001 10.41 9.73001 10.52C9.35001 10.81 8.93001 10.77 8.63001 10.43C8.33001 10.09 8.35001 9.59999 8.68001 9.27999C8.72001 9.23999 8.76001 9.21 8.80001 9.18C9.58001 8.63 10.36 8.07999 11.14 7.53999C11.67 7.17999 12.34 7.48999 12.38 8.11999C12.38 8.24999 12.36 8.39999 12.32 8.52999C11.97 9.75999 11.72 11.01 11.59 12.28C11.44 13.68 11.45 15.08 11.64 16.48C11.67 16.67 11.83 16.8 12.04 16.81C12.24 16.81 12.43 16.68 12.47 16.51C12.57 16.04 12.88 15.81 13.33 15.86C13.71 15.9 14 16.24 14.01 16.67C14.03 17.66 13.45 18.52 12.56 18.79C12 18.96 11.46 18.87 10.96 18.56C10.5 18.28 10.14 17.89 9.94001 17.39C9.82001 17.08 9.80001 16.73 9.75001 16.39C9.45001 14.32 9.58001 12.28 10.18 10.27C10.18 10.25 10.19 10.22 10.21 10.14V10.15Z"
                  fill="#00FFCF"
                />
                <path
                  d="M10.36 4.86C10.36 4.19 10.92 3.65 11.59 3.66C12.24 3.67 12.8 4.25 12.79 4.91C12.78 5.55 12.19 6.11 11.55 6.1C10.91 6.09 10.35 5.51 10.35 4.87L10.36 4.86Z"
                  fill="#00FFCF"
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
            <span
              >此數據為該廁間之人流感測模組所測得之當日各時段人流統計及當日人流總數。</span
            >
          </template>
        </MDBTooltip>
      </div>
    </template>
  </v-section-box>
  <div
    :style="{
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '50px',
    }"
  >
    <v-section-box title="今日人流數據" :hasContent="dailyStatisticsHasContent">
      <template #section-content>
        <div class="traffic-pie">
          <div class="chart-wrapper">
            <human-traffic-pie ref="dailyTrafficPie"></human-traffic-pie>
          </div>
        </div>
      </template>
    </v-section-box>
    <v-section-box
      title="本月平均人流"
      :hasContent="monthlyStatisticsHasContent"
    >
      <template #section-content>
        <div class="traffic-monthly-chart">
          <div class="total-box">
            <div>
              <span class="total_text">{{
                $utils.$formatNumberWithComma(monthlyAccumulatedCount)
              }}</span
              ><span>人/總人數</span>
            </div>
            <div>
              <span class="total_text">{{
                $utils.$formatNumberWithComma(monthlyDateAccumulatedCount)
              }}</span
              ><span>人/當日</span>
            </div>
          </div>
          <div class="chart-wrapper">
            <human-traffic-chart
              ref="monthlyTrafficChart"
            ></human-traffic-chart>
          </div>
        </div>
      </template>
    </v-section-box>
  </div>
</template>

<script>
import {
  onMounted,
  ref,
  inject,
  watch,
  nextTick,
  shallowRef,
  getCurrentInstance,
} from "vue";
import SectionBox from "../plugin/SectionBox.vue";
import HumanTrafficBar from "../plugin/HumanTrafficBar.vue";
import HumanTrafficPie from "../plugin/HumanTrafficPie.vue";
import HumanTrafficChart from "../plugin/HumanTrafficChart.vue";
import { MDBTooltip } from "mdb-vue-ui-kit";

export default {
  components: {
    "v-section-box": SectionBox,
    "human-traffic-bar": HumanTrafficBar,
    "human-traffic-pie": HumanTrafficPie,
    "human-traffic-chart": HumanTrafficChart,
    MDBTooltip,
  },
  props: {
    dailyUrl: {
      type: String,
    },
    monthlyUrl: {
      type: String,
    },
  },
  setup(props) {
    const sharedData = inject("sharedData");
    const dailyTrafficBar = shallowRef(null);
    const dailyTrafficPie = shallowRef(null);
    const monthlyTrafficChart = shallowRef(null);
    const accumulatedCount = ref(null);
    const averageWeekday = ref(null);
    const averageWeekend = ref(null);
    const overAverage = ref(null);
    const latest_data = ref(null);
    const dailyStatisticsHasContent = ref(false);
    const monthlyStatisticsHasContent = ref(false);
    const monthlyAccumulatedCount = ref(false);
    const monthlyDateAccumulatedCount = ref(false);

    const tooltip_chart = ref(null);

    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();

    watch(
      () => sharedData.toilet_id,
      (newValue, oldValue) => {
        handleToiletChange(newValue);
      }
    );

    const handleToiletChange = (toilet_id) => {
      fetchDailyTraffic(toilet_id)
        .then((res) => {
          dailyStatisticsHasContent.value = true;

          accumulatedCount.value = res.accumulatedCount;
          averageWeekday.value = res.averageWeekday;
          averageWeekend.value = res.averageWeekend;
          overAverage.value = res.overAverage;
          latest_data.value = res.latest_data;
          nextTick(() => {
            dailyTrafficBar.value?.reload(res);
            dailyTrafficBar.value?.resize();

            dailyTrafficPie.value?.reload(res);
            dailyTrafficPie.value?.resize();
          });
        })
        .catch((error) => {
          dailyStatisticsHasContent.value = false;
        });

      fetchMonthlyTraffic(toilet_id)
        .then((res) => {
          monthlyStatisticsHasContent.value = true;

          monthlyAccumulatedCount.value = res.accumulatedCount;
          monthlyDateAccumulatedCount.value = res.count;
          nextTick(() => {
            monthlyTrafficChart.value?.reload(res.data, res.start, res.end);
            monthlyTrafficChart.value?.resize();
          });
        })
        .catch((error) => {
          monthlyStatisticsHasContent.value = false;
        });
    };

    const fetchDailyTraffic = (toilet_id) => {
      return new Promise((resolve, reject) => {
        if (!toilet_id) {
          return reject();
        }
        axios
          .get(props.dailyUrl, {
            params: {
              date: globalProperties.$utils.$getToday(),
              toilet_id: toilet_id,
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

    const fetchMonthlyTraffic = (toilet_id) => {
      return new Promise((resolve, reject) => {
        if (!toilet_id) {
          return reject();
        }
        axios
          .get(props.monthlyUrl, {
            params: {
              date: globalProperties.$utils.$getToday(),
              toilet_id: toilet_id,
            },
          })
          .then((res) => {
            if (res.accumulatedCount == null) {
              return reject();
            }
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };

    onMounted(() => {
      handleToiletChange(sharedData.toilet_id);
    });
    return {
      dailyTrafficBar,
      dailyTrafficPie,
      monthlyTrafficChart,
      latest_data,
      accumulatedCount,
      dailyStatisticsHasContent,
      monthlyStatisticsHasContent,
      monthlyAccumulatedCount,
      monthlyDateAccumulatedCount,
      averageWeekday,
      averageWeekend,
      overAverage,
      tooltip_chart,
    };
  },
};
</script>