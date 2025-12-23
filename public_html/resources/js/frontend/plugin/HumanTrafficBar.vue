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
        grid: {
          top: "16px",
          left: "48px",
          right: "32px",
          bottom: "16px",
          containLabel: true,
        },
        /* axisPointer: {
          axis : 'x',
          show: true,
          type: "line",
          snap: true,
          lineStyle: {
            width: 0,
            color: "#fff",
          },
          label: {
            show: false,
            backgroundColor: 'rgba(0, 255, 202, 0.3)',
            color: "#ffffff",
            margin: 20,
            formatter: function (params) {
              return Math.round(params.value);
            },
          },
        }, */
        tooltip: {
          trigger: "axis",
          formatter: '{b0}時 累計人流<br />{c0}人',
          axisPointer: {
            type: "line", // 配置指示器类型为阴影指示器（显示在柱子底部）,
            axis: "x",
            lineStyle: {
              width: 0,
              color: "#2FABD1",
            },
          },
        },
        xAxis: [
          {
            name: "時",
            nameLocation: "start",
            axisLine: {
              lineStyle: {
                color: "#fff",
              },
            },
            nameTextStyle: {
              show: true,
              color: "#00FFCA",
              padding: 20,
              verticalAlign: "top",
              align: "left",
            },
            axisLabel: {
              color: "#fff",
              formatter: function (value) {
                return value !== "0" ? value.toString().padStart(2, "0") : "";
              },
            },
            type: "category",
            data: (() => {
              let categories = [];
              for (var i = 0; i < 24; i++) {
                categories.push(i);
              }
              return categories;
            })(),
            axisTick: {
              alignWithLabel: true,
            },
          },
        ],
        yAxis: [
          {
            minInterval: 1,
            max: 10,
            axisLine: {
              show: true,
              lineStyle: {
                color: "#fff",
              },
            },
            axisLabel: {
              color: "#fff",
              formatter: function (value) {
                return value !== 0 ? value : "";
              },
            },
            name: "人數",
            type: "value",
            nameLocation: "start",
            nameRotate: 0,
            nameTextStyle: {
              color: "#00FFCA",
              verticalAlign: "bottom",
              align: "right",
              padding: 20,
            },
            axisTick: {
              show: true,
              alignWithLabel: true,
            },
          },
        ],
        series: [
          {
            name: "時",
            type: "bar",
            barWidth: "12px",
            data: [],
            itemStyle: {
              color: "#00FFCA",
            },
            emphasis: {
              label: {
                show: true,
                position: "top",
                formatter: (params) => {
                  if(params.value == 0) return '';
                  return `{a|${params.value}}`; // 使用 rich 設定換行和不同大小的文本
                },
                rich: {
                  a: {
                    fontSize: 12,
                    color: "#fff",
                  }
                },
                align: "center"
              }
            }
          }
        ],
      };
      chartInstance.setOption(option.value);
    };

    const reload = (res) => {
      if (chartInstance == null) {
        initChart();
      }
      option.value.xAxis[0].data = res.data.map((el) => el.hour);
      option.value.series[0].data = res.data.map((el) => el.total_num);
      let max = Math.max(...res.data.map((el) => el.total_num));
      // 最大值補到10的倍數
      option.value.yAxis[0].max = Math.ceil(max / 10) * 10;
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