<template>
  <v-section-box :title="$t('frontend_toilet_paper_usage')" :hasContent="hasTodayContent">
    <template #section-content>
      <div class="toilet-paper-usage-gauge">
        <div class="total-box">
          <span>{{ $t("frontend_toilet_paper_remaining_amount") }}</span>
          <div>
            <div class="total_text" style="display: inline">
              {{ average ?? '' }}
            </div>
            <span>%</span>
          </div>
        </div>
        <div class="chart-wrapper">
          <operation-gauge ref="toiletPaperGauge" :grading_criteria="grading_criteria"></operation-gauge>
        </div>
        <MDBTooltip direction="top" class="chart-info" v-model="tooltip_chart">
          <template #reference>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <g clip-path="url(#clip0_130_67)">
                <path
                  d="M12.26 23.11H10.89C10.89 23.11 10.8 23.08 10.75 23.08C8.73 22.93 6.86 22.31 5.18 21.17C2.34 19.23 0.64 16.55 0.11 13.14C0.06 12.84 0.03 12.53 0 12.22C0 11.78 0 11.33 0 10.89C0.01 10.82 0.03 10.76 0.04 10.69C0.11 10.2 0.15 9.7 0.25 9.21C1.59 2.88 7.79 -1.12 14.1 0.280003C18.67 1.29 22.26 5.09 22.99 9.72C23.05 10.1 23.09 10.48 23.14 10.86V12.26C23.14 12.26 23.11 12.36 23.11 12.4C22.99 14.03 22.56 15.57 21.77 16.99C19.87 20.38 16.99 22.4 13.13 23C12.84 23.05 12.54 23.07 12.25 23.11H12.26ZM21.28 11.56C21.28 6.22 16.95 1.87 11.61 1.87C6.23 1.87 1.88 6.19 1.88 11.55C1.88 16.89 6.21 21.22 11.58 21.24C16.91 21.26 21.28 16.9 21.28 11.56Z"
                  fill="#00FFCF" />
                <path
                  d="M10.21 10.15C10.02 10.29 9.88001 10.41 9.73001 10.52C9.35001 10.81 8.93001 10.77 8.63001 10.43C8.33001 10.09 8.35001 9.59999 8.68001 9.27999C8.72001 9.23999 8.76001 9.21 8.80001 9.18C9.58001 8.63 10.36 8.07999 11.14 7.53999C11.67 7.17999 12.34 7.48999 12.38 8.11999C12.38 8.24999 12.36 8.39999 12.32 8.52999C11.97 9.75999 11.72 11.01 11.59 12.28C11.44 13.68 11.45 15.08 11.64 16.48C11.67 16.67 11.83 16.8 12.04 16.81C12.24 16.81 12.43 16.68 12.47 16.51C12.57 16.04 12.88 15.81 13.33 15.86C13.71 15.9 14 16.24 14.01 16.67C14.03 17.66 13.45 18.52 12.56 18.79C12 18.96 11.46 18.87 10.96 18.56C10.5 18.28 10.14 17.89 9.94001 17.39C9.82001 17.08 9.80001 16.73 9.75001 16.39C9.45001 14.32 9.58001 12.28 10.18 10.27C10.18 10.25 10.19 10.22 10.21 10.14V10.15Z"
                  fill="#00FFCF" />
                <path
                  d="M10.36 4.86C10.36 4.19 10.92 3.65 11.59 3.66C12.24 3.67 12.8 4.25 12.79 4.91C12.78 5.55 12.19 6.11 11.55 6.1C10.91 6.09 10.35 5.51 10.35 4.87L10.36 4.86Z"
                  fill="#00FFCF" />
              </g>
              <defs>
                <clipPath id="clip0_130_67">
                  <rect width="23.15" height="23.11" fill="white" />
                </clipPath>
              </defs>
            </svg>
          </template>
          <template #tip>
            <span>此數據為透過該廁間之廁紙感測模組，透過偵測內部廁紙之剩餘量，並將其轉化為分數及五個等級，以100分為滿分
              分級如下：</span><br />
            <b>一、滿：91 ~ 100分。</b><br />
            <b>二、良：71 ~ 90分。</b><br />
            <b>三、中：31 ~ 70分。</b><br />
            <b>四、低：11 ~ 30分。</b><br />
            <b>五、不足：0 ~ 10分。</b><br />
          </template>
        </MDBTooltip>
      </div>
    </template>
  </v-section-box>
  <v-section-box :title="$t('frontend_toilet_paper_usage_amount')" :hasContent="hasMonthlyContent">
    <template #section-content>
      <div class="toilet-paper-usage-chart">
        <div class="chart-wrapper">
          <div>
            <span>{{ $t("frontend_toilet_paper_operation_chart") }}</span>
          </div>
          <level-chart ref="toiletPaperChart" :grading_criteria="[
            [10, '不足'],
            [30, '低'],
            [50, '中'],
            [70, '良'],
            [90, '滿'],
          ]"></level-chart>
        </div>
      </div>
    </template>
  </v-section-box>
  <v-section-box :title="$t('frontend_toilet_paper_refill_record')" :hasContent="hasRefillContent">
    <template #section-content>
      <div class="table-block">
        <div :class="{ 'table-control': true, disabled: refillPageIndex == 0 }" @click="handleRefillPrevClick()">
          <svg width="17" height="29" viewBox="0 0 17 29" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 27L3 14.5009L15 2" stroke-width="3" stroke-miterlimit="10" />
          </svg>
        </div>
        <div class="table-content">
          <table>
            <thead>
              <th>{{ $t('frontend_number') }}</th>
              <th>{{ $t('frontend_sensor_name') }}</th>
              <th>{{ $t('frontend_refill_datetime') }}</th>
            </thead>
            <tbody>
              <tr v-for="(record, index) in refillRecords[refillPageIndex]" :key="index">
                <td>{{ refillPageIndex * pageLength + index + 1 }}</td>
                <td>{{ record.sensor_name }}</td>
                <td>{{ record.datetime }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div :class="{
          'table-control': true,
          disabled: refillPageIndex >= refillRecords.length - 1
        }" @click="handleRefillNextClick()">
          <svg width="17" height="29" viewBox="0 0 17 29" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 2L14 14.4991L2 27" stroke-width="3" stroke-miterlimit="10" />
          </svg>
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
    refillDataUrl: {
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
    const grading_criteria = [
      [10, "不足"],
      [30, "低"],
      [70, "中"],
      [90, "良"],
      [100, "滿"],
    ];
    const toiletPaperGauge = shallowRef(null);
    const toiletPaperChart = shallowRef(null);
    const tooltip_chart = ref(null);
    const hasTodayContent = ref(false);
    const hasMonthlyContent = ref(false);
    const hasRefillContent = ref(false);
    const average = ref(null);

    const pageLength = 5;
    const refillRecords = ref([]);
    const refillPageIndex = ref(0);

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
              toiletPaperGauge.value?.reload(res.average);
              toiletPaperGauge.value?.resize();
            });
          }
          if (res.monthly_records.length > 0) {
            nextTick(() => {
              toiletPaperChart.value?.reload(res.monthly_records);
              toiletPaperChart.value?.resize();
            });
          }
        })
        .catch((error) => {
          hasTodayContent.value = false;
          hasMonthlyContent.value = false;
          average.value = null;
        });

      fetchData(toilet_id, props.refillDataUrl)
        .then((res) => {
          if (res.length <= 0) {
            throw "res length 0";
          }
          hasRefillContent.value = true;
          const records = Array.from(
            { length: Math.ceil(res.length / pageLength) },
            (_, index) =>
              res.slice(index * pageLength, index * pageLength + pageLength)
          );
          refillPageIndex.value = 0;
          refillRecords.value = records;
        })
        .catch((error) => {
          hasRefillContent.value = false;
          refillRecords.value = [];
          refillPageIndex.value = 0;
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
    const handleRefillPrevClick = () => {
      if (refillPageIndex.value !== null && refillPageIndex.value > 0) {
        refillPageIndex.value--;
      }
    };
    const handleRefillNextClick = () => {
      if (
        refillPageIndex.value !== null &&
        refillPageIndex.value < refillRecords.value.length - 1
      ) {
        refillPageIndex.value++;
      }
    };

    /* const scoreMark = computed(() => {
      if (isNaN(average.value)) {
        return "";
      }
      for (let i = 0; i < grading_criteria.length; i++) {
        if (average.value <= grading_criteria[i][0]) {
          return grading_criteria[i][1];
        }
      }
      return "";
    }); */

    onMounted(() => {
      updateData();
    });

    return {
      toiletPaperGauge,
      grading_criteria,
      // scoreMark,
      tooltip_chart,
      toiletPaperChart,
      refillRecords,
      refillPageIndex,
      pageLength,
      average,

      hasTodayContent,
      hasRefillContent,
      hasMonthlyContent,

      handleRefillPrevClick,
      handleRefillNextClick,
    };
  },
};
</script>