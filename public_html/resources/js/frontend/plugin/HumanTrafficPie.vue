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
        tooltip: {
          trigger: "item",
        },
        legend: {
          orient: "vertical",
          left: "left",
          top: "middle",
          textStyle: {
            fontSize: "12px",
            color: "#fff",
          },
        },
        series: [
          {
            name: "今日人流數據",
            type: "pie",
            radius: "100px",
            colorBy: "data",
            center: ['70%', '50%'],
            label: {
              position: "inside",
              formatter: function(params) {
                if (params.percent !== 0) {
                    return `${params.percent}%`;
                } else {
                    return '';
                }
            },
              textStyle: {
                fontSize: "20px",
              },
            },
            data: [
              {
                value: 0,
                name: "上午9:00~12:00",
                itemStyle: {
                  color: "#04B198",
                },
              },
              {
                value: 0,
                name: "中午12:00~15:00",
                itemStyle: {
                  color: "#07786F",
                },
              },
              {
                value: 0,
                name: "下午15:00~18:00",
                itemStyle: {
                  color: "#0A484D",
                },
              },
            ],
            emphasis: {
              itemStyle: {
                shadowBlur: 10,
                shadowOffsetX: 0,
                shadowColor: "rgba(0, 0, 0, 0.5)",
              },
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
      let segment_1 = res.data
        .filter((el) => el.hour >= 0 && el.hour < 6)
        .reduce((sum, el) => sum + el.total_num, 0);
      let segment_2 = res.data
        .filter((el) => el.hour >= 6 && el.hour < 11)
        .reduce((sum, el) => sum + el.total_num, 0);
      let segment_3 = res.data
        .filter((el) => el.hour >= 11 && el.hour < 14)
        .reduce((sum, el) => sum + el.total_num, 0);
      let segment_4 = res.data
        .filter((el) => el.hour >= 14 && el.hour < 18)
        .reduce((sum, el) => sum + el.total_num, 0);
      let segment_5 = res.data
        .filter((el) => el.hour >= 18 && el.hour <= 23)
        .reduce((sum, el) => sum + el.total_num, 0);

      let data = [];
      data.push({
        value: segment_1,
        name: "凌晨 00:00-05:59",
        itemStyle: {
          color: "#03CDB0",
        },
      });
      data.push({
        value: segment_2,
        name: "早上 06:00-10:59",
        itemStyle: {
          color: "#04B198",
        },
      });
      data.push({
        value: segment_3,
        name: "中午 11:00-13:59",
        itemStyle: {
          color: "#07786F",
        },
      });
      data.push({
        value: segment_4,
        name: "下午 14:00-17:59",
        itemStyle: {
          color: "#0A484D",
        },
      });
      data.push({
        value: segment_5,
        name: "晚上 18:00-23:59",
        itemStyle: {
          color: "#2D4345",
        },
      });

      option.value.series[0].data = data;
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
      reload,
      resize,
    };
  },
};
</script>