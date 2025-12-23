<template>
  <div class="echarts-box" :style="{ width: '100%', height: '100%' }">
    <div ref="chartRef" :style="{ width: '100%', height: '100%' }"></div>
  </div>
</template>

<script>
import {
  shallowRef,
  onMounted,
  onUnmounted,
  ref,
  getCurrentInstance,
} from "vue";
import * as echarts from "echarts";
export default {
  props: {
    color: {
      type: String,
      default: "#00FFCA",
    },
  },
  setup(props) {
    let chartRef = shallowRef(null);
    let chartInstance = null;
    let option = ref(null);

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
            startAngle: 90,
            endAngle: -270,
            pointer: {
              show: false,
            },
            progress: {
              show: true,
              overlap: false,
              roundCap: false,
              clip: false,
              itemStyle: {
                borderWidth: 0,
                borderColor: props.color,
                color: props.color,
              },
            },
            axisLine: {
              lineStyle: {
                width: 10,
                color: [[1, "#3F3F3F"]],
              },
            },
            splitLine: {
              show: false,
              distance: 0,
              length: 10,
            },
            axisTick: {
              show: false,
            },
            axisLabel: {
              show: false,
              distance: 50,
            },
            data: [
              {
                value: 0,
                title: {
                  show: false,
                },
                detail: {
                  valueAnimation: true,
                  offsetCenter: ["0%", "0%"],
                },
              },
            ],
            title: {
              fontSize: 14,
            },
            detail: {
              width: 50,
              height: 14,
              fontSize: 20,
              color: "#FFFFFF",
              formatter: "{value}分",
            },
          },
        ],
      };
      chartInstance.setOption(option.value);
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

    onMounted(() => {
      // initChart();
      // reload();
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
    return {
      chartRef,
      resize,
      reload,
    };
  },
};
</script>