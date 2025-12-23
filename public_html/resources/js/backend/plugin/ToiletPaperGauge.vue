<template>
  <div class="echarts-box" :style="{ width: '100%', height: '100%' }">
    <div ref="chartRef" :style="{ width: '100%', height: '100%' }"></div>
  </div>
</template>

<script>
import * as echarts from "echarts";
import { shallowRef, onMounted, onUnmounted, ref, getCurrentInstance } from "vue";


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
            center: ["50%", "80%"],
            startAngle: 180,
            endAngle: 360,
            min: 0,
            max: 100,
            splitNumber: 10,
            radius: '100%',
            itemStyle: {
              color: "#2FA7CD",
            },
            progress: {
              show: true,
              width: 12,
            },
            pointer: {
              show: false,
            },
            axisLine: {
              lineStyle: {
                width: 12,
              }
            },
            axisTick: {
              distance: -25,
              length: 5,
              splitNumber: 5,
              lineStyle: {
                width: 2,
                color: "#2FA7CD",
              },
            },
            splitLine: {
              distance: -30,
              length: 10,
              lineStyle: {
                width: 3,
                color: "#2FA7CD",
              },
            },
            axisLabel: {
              distance: -20,
              color: "#2FA7CD",
              fontSize: 14,
            },
            title: {
              show: true,
              offsetCenter: [0, 0],
              color: '#AFAFAF',
              fontSize: 14
            },
            detail: {
              valueAnimation: true,
              lineHeight: 40,
              borderRadius: 8,
              offsetCenter: [0, "-30%"],
              fontSize: 48,
              fontWeight: "bolder",
              formatter: "{value}%",
              color: "inherit",
            },
            data: []
          }
        ],
      };

      if (option.value && typeof option.value === "object") {
        chartInstance.setOption(option.value);
      }
    };

    const updateData = (value) => {
      if (chartInstance == null) {
        initChart();
      }
      option.value.series[0].data = [
        {
          value: value,
          name: globalProperties.$t('toilet_paper_average_value')
        }
      ];

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
    return {
      chartRef,
      resize,
      updateData,
    };
  },
};
</script>
