<template>
  <div
    :style="{
      width: '100%',
      height: '100%',
      gridRow: '1',
    }"
  >
    <div ref="chartRef" :style="{ width: '100%', height: '100%' }"></div>
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
} from "vue";

export default {
  props: {
    grading_criteria: {
      type: Array,
    },
    labelShow: {
      type: Boolean,
      default: true,
    },
  },
  setup(props) {
    let chartRef = shallowRef(null);
    let chartInstance = null;
    let option = ref(null);
    const grading_criteria = props.grading_criteria;
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();

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
            min: 0,
            max: 100,
            splitNumber: 10,
            startAngle: 180,
            endAngle: 0,
            center: ["50%", "90%"],
            radius: "125",
            axisLine: {
              lineStyle: {
                width: 20,
                color: [[1, "#3F3F3F"]],
              },
            },
            splitLine: {
              show: false,
            },
            axisTick: {
              show: false,
            },
            axisLabel: {
              distance: -50,
              color: "#fff",
              fontSize: 14,
              show: props.labelShow,
            },
            progress: {
              show: true,
              overlap: false,
              roundCap: false,
              clip: false,
              itemStyle: {
                borderWidth: 0,
                color: new echarts.graphic.LinearGradient(0, 1, 0, 0, [
                  { offset: 0, color: "#FFFFFF00" },
                  { offset: 0.5, color: "#1FE2B9" },
                ]),
              },
            },
            pointer: {
              itemStyle: {
                color: "#fff",
              },
              length: "30%",
              width: 20,
              icon: "triangle",
            },
            anchor: {
              show: true,
              showAbove: true,
              size: 20,
              itemStyle: {
                borderWidth: 5,
                borderColor: "#fff",
                color: "#05182D",
              },
            },
            detail: {
              show: false,
            },
            data: [
              {
                value: 0,
              },
            ],
          },
          {
            type: "gauge",
            min: 0,
            max: 100,
            splitNumber: 10,
            startAngle: 180,
            endAngle: 0,
            center: ["50%", "90%"],
            radius: "132",
            axisLine: {
              lineStyle: {
                color: [[1, "#DABEBE"]],
                width: 2,
              },
            },
            splitLine: {
              show: false,
            },
            axisTick: {
              show: false,
            },
            axisLabel: {
              distance: props.labelShow ? -70 : -60,
              fontSize: 18,
              color: "#00FFCA",
              formatter: (value) => {
                // 為視覺平衡改為這樣
                let tick = [10, 30, 50, 70, 90];
                for (let i = 0; i < grading_criteria.length; i++) {
                  if (value == tick[i]) {
                    return grading_criteria[i][1];
                  }
                }
                return "";
              },
            },
          },
        ],
      };
      chartInstance.setOption(option.value);
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

    const reload = (res) => {
      if (chartInstance == null) {
        initChart();
      }
      option.value.series[0].data[0].value = res;
      if (option.value && typeof option.value === "object") {
        chartInstance.setOption(option.value);
      }
    };

    return {
      chartRef,
      reload,
      resize,
    };
  },
};
</script>