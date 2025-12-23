<template>
  <div class="echarts-box" :style="{ width: '100%', height: '100%' }">
    <div
      class="score-mark"
      :style="{ position: 'absolute', left: '10px', display: 'flex' }"
    >
      <h1 :style="{ color: markColor }">{{ scoreMark }}</h1>
      <div class="smelly-warning" v-if="showWarning"></div>
    </div>
    <div
      ref="chartRef"
      id="smelly_gauge"
      :style="{ width: '100%', height: '100%' }"
    ></div>
  </div>
</template>

<script>
import * as echarts from "echarts";
import {
  shallowRef,
  onMounted,
  onUnmounted,
  ref,
  getCurrentInstance,
  computed,
} from "vue";

export default {
  setup() {
    let chartRef = shallowRef(null);
    let chartInstance = null;
    let option = ref(null);
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();

    const smellyScale = globalProperties.$utils.smellyScale();
    const smelly = ref(null);
    const showWarning = ref(false);

    const initChart = () => {
      const dom = chartRef.value;
      chartInstance = echarts.init(dom, null, {
        renderer: "canvas",
        useDirtyRect: false,
      });
      option.value = {
        series: [
          {
            type: "gauge",
            startAngle: 180,
            endAngle: 0,
            center: ["50%", "80%"],
            radius: "100%",
            min: 0,
            max: 100,
            axisLine: {
              lineStyle: {
                width: 5,
                color: [
                  [
                    0.5,
                    new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                      { offset: 0, color: "#98D5E7" },
                      { offset: 0.5, color: "#A6D8D5" },
                      { offset: 1, color: "#C9DEA9" },
                    ]),
                  ],
                  [
                    1,
                    new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                      { offset: 0, color: "#C9DEA9" },
                      { offset: 0.5, color: "#F0B9A5" },
                      { offset: 1, color: "#FEAAA4" },
                    ]),
                  ],
                ],
              },
            },
            pointer: {
              itemStyle: {
                color: "#B5B5B6",
              },
              length: "55%",
              width: 7,
              offsetCenter: [0, "1%"],
            },
            anchor: {
              show: true,
              showAbove: true,
              size: 16,
              itemStyle: {
                borderWidth: 5,
                borderColor: "#B5B5B6",
              },
            },
            axisTick: {
              show: false,
            },
            splitLine: {
              show: false,
            },
            axisLabel: {
              show: false,
            },
            title: {
              offsetCenter: [0, "20%"],
              color: "#AFAFAF",
              fontSize: 14,
            },
            detail: {
              show: false,
            },
            data: [
              {
                value: null,
                name: globalProperties.$t("smelly_average_value"),
              },
            ],
          },
          {
            type: "gauge",
            startAngle: 180,
            endAngle: 0,
            center: ["50%", "80%"],
            radius: "90%",
            min: 0,
            max: 100,
            itemStyle: {
              color: "#E27170",
            },
            progress: {
              show: true,
              width: 20,
            },
            pointer: {
              show: false,
            },
            axisLine: {
              show: true,
              lineStyle: {
                width: 20,
              },
            },
            axisTick: {
              show: false,
            },
            splitLine: {
              show: false,
            },
            axisLabel: {
              show: false,
            },
            detail: {
              show: false,
            },
            data: [
              {
                value: null,
              },
            ],
          },
          {
            type: "gauge",
            startAngle: 162,
            endAngle: 18,
            center: ["50%", "80%"],
            radius: "100%",
            min: 10,
            max: 90,
            splitNumber: 4,
            axisLine: {
              show: false,
            },
            pointer: {
              show: false,
            },
            axisTick: {
              show: false,
            },
            splitLine: {
              show: true,
              length: 12,
              distance: -13.5,
              lineStyle: {
                color: "#9FA0A0",
                opacity: [0, 1],
                width: 1,
              },
            },
            axisLabel: {
              color: "#AFAFAF",
              fontSize: 14,
              distance: -30,
              formatter: (value) => {
                for (let i = 0; i < smellyScale.length; i++) {
                  if (value == smellyScale[i][0]) {
                    return smellyScale[i][1];
                  }
                }
                return "";
              },
            },
            detail: {
              show: false,
            },
            data: [
              {
                value: null,
              },
            ],
          },
        ],
      };
      chartInstance.setOption(option.value);
    };

    const updateData = (smelly_value) => {
      smelly.value = smelly_value;
      if (chartInstance == null) {
        initChart();
      }
      for (let i = 0; i < option.value.series.length; i++) {
        option.value.series[i].data[0].value = smelly_value;
        if (i == 1) {
          option.value.series[i].itemStyle.color = globalProperties.$utils.calcSmellyColor(smelly_value);
          showWarning.value = smelly_value > 70;
        }
      }
      if (option.value && typeof option.value === "object") {
        chartInstance.setOption(option.value);
      }
    };

    onMounted(() => {
      // initChart();
    });

    onUnmounted(() => {
      if (chartInstance) {
        chartInstance.dispose();
      }
    });

    const resize = () => {
      if (chartInstance) {
        chartInstance.resize();
      }
    };

    const scoreMark = computed(() => {
      return globalProperties.$utils.calcSmellyScore(smelly.value);
    });

    const markColor = computed(() => {
      return globalProperties.$utils.calcSmellyColor(smelly.value);
    });

    return {
      chartRef,
      option,
      resize,
      updateData,
      scoreMark,
      markColor,
      smelly,
      showWarning,
    };
  },
};
</script>