<template>
  <v-section-box
    :title="$t('frontend_smelly_gauge_title')"
    :hasContent="hasTodayContent"
  >
    <template #section-content>
      <div class="smelly-gauge">
        <div class="total-box">
          <span>{{ $t("frontend_smelly_gauge_content") }}</span>
          <div class="total_text" :style="{ color: markColor }">
            {{ scoreMark }}
          </div>
        </div>
        <div class="chart-wrapper">
          <operation-gauge
            ref="smellyGauge"
            :grading_criteria="smellyScale"
            :label-show="false"
          ></operation-gauge>
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
              >此數據為透過該廁間之氣味感測模組，透過偵測廁間內部氣味品質，並將其轉化為分數及五個等級（以設定之感測器『最小值』以及『最大值』，將上傳數據轉換成百分比）：</span
            ><br />
            <b>一、惡臭：91% ~ 100%。</b><br />
            <b>二、刺鼻：71% ~ 90%。</b><br />
            <b>三、正常：31% ~ 705。</b><br />
            <b>四、舒適：11% ~ 30%。</b><br />
            <b>五、清香：0% ~ 10%。</b><br />
          </template>
        </MDBTooltip>
      </div>
    </template>
  </v-section-box>
  <v-section-box
    :title="$t('frontend_smelly_chart_title')"
    :hasContent="hasMonthlyContent"
  >
    <template #section-content>
      <div class="smelly-chart">
        <div class="chart-wrapper">
          <div>
            <span>{{ $t("frontend_smelly_chart_content") }}</span>
          </div>
          <level-chart
            ref="smellyChart"
            :grading_criteria="smellyScale"
          ></level-chart>
        </div>
      </div>
    </template>
  </v-section-box>
</template>

<script>
import {
  onMounted,
  ref,
  inject,
  watch,
  nextTick,
  shallowRef,
  computed,
  getCurrentInstance,
} from "vue";
import SectionBox from "../plugin/SectionBox.vue";
import OperationGauge from "../plugin/OperationGauge.vue";
import { MDBTooltip } from "mdb-vue-ui-kit";
import LevelChart from "../plugin/LevelChart.vue";

export default {
  components: {
    "v-section-box": SectionBox,
    "operation-gauge": OperationGauge,
    MDBTooltip,
    "level-chart": LevelChart,
  },
  props: {
    dataUrl: {
      type: String,
    },
  },
  setup(props) {
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();
    const sharedData = inject("sharedData");
    const smellyScale = globalProperties.$utils.smellyScale();
    const smellyGauge = shallowRef(null);
    const smellyChart = shallowRef(null);
    const tooltip_chart = ref(null);
    const hasTodayContent = ref(false);
    const hasMonthlyContent = ref(false);
    const average = ref(null);

    watch(
      () => sharedData.toilet_id,
      (newValue, oldValue) => {
        nextTick(() => {
          updateData(newValue);
        });
      }
    );

    const updateData = (toilet_id) => {
      fetchData(toilet_id, props.dataUrl)
        .then((res) => {
          hasTodayContent.value = res.average != null;
          average.value = res.average;
          hasMonthlyContent.value = res.monthly_records.length > 0;
          if (res.average != null) {
            nextTick(() => {
              smellyGauge.value?.reload(res.average);
              smellyGauge.value?.resize();
            });
          }
          if (res.monthly_records.length > 0) {
            nextTick(() => {
              smellyChart.value?.reload(res.monthly_records);
              smellyChart.value?.resize();
            });
          }
        })
        .catch((error) => {
          hasTodayContent.value = false;
          hasMonthlyContent.value = false;
          average.value = null;
        });
    };

    const fetchData = (toilet_id, url) => {
      return new Promise((resolve, reject) => {
        if (!toilet_id) {
          return reject();
        }
        axios
          .get(url, {
            params: {
              toilet_id: toilet_id,
              date: globalProperties.$utils.$getToday(),
            },
          })
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };

    const scoreMark = computed(() => {
      if (isNaN(average.value)) {
        return "";
      }
      return globalProperties.$utils.calcSmellyScore(average.value);
    });

    const markColor = computed(() => {
      if (isNaN(average.value)) {
        return "";
      }
      const grading_criteria = [
        [10, "#00FFCF"],
        [30, "#00FFCF"],
        [70, "#2BF863"],
        [90, "#FF8D00"],
        [100, "#FF5348"],
      ];
      for (let i = 0; i < grading_criteria.length; i++) {
        if (average.value <= grading_criteria[i][0]) {
          return grading_criteria[i][1];
        }
      }
      return "#FF5348";
    });

    onMounted(() => {
      updateData();
    });

    return {
      smellyGauge,
      smellyChart,
      tooltip_chart,
      smellyScale,
      scoreMark,
      markColor,
      hasTodayContent,
      hasMonthlyContent,
    };
  },
};
</script>