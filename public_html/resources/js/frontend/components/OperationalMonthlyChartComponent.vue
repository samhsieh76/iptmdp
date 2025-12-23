<template>
  <v-section-box
    :title="$t('frontend_history_operation_status')"
    :hasContent="true"
  >
    <template #section-content>
      <div class="operation-status-chart">
        <div class="chart-wrapper">
          <div>
            <span>本月營運圖表</span>
          </div>
          <operation-chart
            ref="operateChart"
            :grading_criteria="grading_criteria"
          ></operation-chart>
        </div>
      </div>
    </template>
  </v-section-box>
</template>

<script>
import { onMounted, ref, inject, watch, nextTick, shallowRef } from "vue";
import SectionBox from "../plugin/SectionBox.vue";
import OperationChart from "../plugin/OperationChart.vue";

export default {
  components: {
    "v-section-box": SectionBox,
    "operation-chart": OperationChart,
  },
  setup() {
    const sharedData = inject("sharedData");
    const grading_criteria = [
      [10, "劣"],
      [30, "低"],
      [50, "中"],
      [70, "良"],
      [90, "優"],
    ];
    const operateChart = shallowRef(null);
    watch(
      () => sharedData.operational_data,
      (newValue, oldValue) => {
        nextTick(() => {
          handleDataChange(newValue);
        });
      }
    );

    const handleDataChange = (res) => {
      operateChart.value?.reload(res.monthly_records || []);
    };

    onMounted(() => {
      handleDataChange(sharedData.operational_data);
    });
    return {
      operateChart,
      grading_criteria,
    };
  },
};
</script>