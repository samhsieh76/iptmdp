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
    res: {
      type: Object,
      default: [],
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


    let xData = Object.values(props.res);
    let yData = Object.keys(props.res);
    const initChart = () => {
      const dom = chartRef.value;
      chartInstance = echarts.init(dom, null, {
        renderer: "canvas",
        useDirtyRect: false,
      });
      option.value = {
        grid: {
          left: "54px",
          top: "10px",
          right: "54px",
          bottom: "10px",
        },
        barMaxWidth: "16px",
        xAxis: [
          {
            type: "value",
            axisLabel: {
              show: false,
            },
            splitLine: {
              show: false,
            },
            axisLine: {
              show: true,
            },
            max: function (value) {
                return value.max + 10;
            },
          },
        ],
        yAxis: [
          {
            type: "category",
            axisTick: {
              show: false,
            },
            axisLabel: {
              color: "#fff",
              fontSize: '1.25rem'
            },
            axisLine: {
              onZero: false,
            },
            nameGap: 14,
            data: yData
          },
        ],
        series: [
          {
            type: "bar",
            itemStyle: {
              color: "#00FFCF",
            },
            label: {
              show: true,
              position: "right",
              formatter: "{c}處",
              color: "#fff",
              fontSize: '1.25rem'
            },
            emphasis: {
              focus: "series",
            },
            data: xData
          },
        ],
      };
      chartInstance.setOption(option.value);
    };

    const reload = () => {
      if (chartInstance == null) {
        initChart();
      }
        option.value.series[0].data = props.res;
        if (option.value && typeof option.value === "object") {
            chartInstance.setOption(option.value);
        }
    };

    onMounted(() => {
      initChart();
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
      reload,
      resize,
    };
  },
};
</script>