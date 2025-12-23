<template>
  <div class="grid-template">
    <div class="statistics-box">
      <div class="statistics-item">
        <span class="top-align">{{ $t('human_traffic_weekday_average') }}</span>
        <span class="statistics-value"> {{ formatNumberWithComma(averageWeekday) }} </span>
        <span class="bottom-align">人</span>
      </div>
      <div class="statistics-item">
        <span class="top-align">{{ $t('human_traffic_weekend_average') }}</span>
        <span class="statistics-value"> {{ formatNumberWithComma(averageWeekend) }} </span>
        <span class="bottom-align">人</span>
      </div>
      <div class="statistics-item mt-2">
        <span class="top-align">{{ $t('human_traffic_current_accumulated') }}</span>
        <span :class="['statistics-value larger-text', { over: overAverage}]">{{ formatNumberWithComma(accumulatedCount) }} </span>
        <span class="bottom-align">人</span>
      </div>
    </div>
    <div class="echarts-box" :style="{ width: '100%', height: '100%' }">
      <div ref="chartRef" :style="{ width: '100%', height: '100%' }"></div>
    </div>
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
  setup(props) {
    let chartRef = shallowRef(null);
    let chartInstance = null;
    let option = ref(null);
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();
    const averageWeekday = ref(null);
    const averageWeekend = ref(null);
    const overAverage = ref(null);
    const accumulatedCount = ref(null);

    const initChart = () => {
      const dom = chartRef.value;
      chartInstance = echarts.init(dom, null, {
        renderer: "canvas",
        useDirtyRect: false,
      });
      option.value = {
        /* axisPointer: {
          axis: "x",
          show: true,
          type: "line",
          lineStyle: {
            width: 0,
            color: "#2FABD1",
          },
          label: {
            show: false,
            backgroundColor: "#2FA7CD",
            color: "#ffffff",
            fontSize: 16,
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
        grid: {
          top: "16px",
          left: "48px",
          right: "32px",
          bottom: "32px",
          containLabel: true,
        },
        xAxis: [
          {
            name: "時",
            nameLocation: "start",
            axisLine: {
              lineStyle: {
                color: "#AFAFAF",
              },
            },
            nameTextStyle: {
              show: true,
              color: "#2FA7CD",
              padding: 20,
              verticalAlign: "top",
              align: "left",
              fontSize: 14,
            },
            axisLabel: {
              color: "#AFAFAF",
              formatter: function (value) {
                return value !== "0" ? value.toString().padStart(2, "0") : "";
              },
              fontSize: 14,
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
                color: "#AFAFAF",
              },
            },
            axisLabel: {
              color: "#AFAFAF",
              formatter: function (value) {
                return value !== 0 ? value : "";
              },
              fontSize: 14,
            },
            name: "人數",
            type: "value",
            nameLocation: "start",
            nameRotate: 0,
            nameTextStyle: {
              color: "#2FA7CD",
              verticalAlign: "bottom",
              align: "right",
              padding: 20,
              fontSize: 14,
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
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: "rgba(19, 146, 198, 0.5)" },
                { offset: 1, color: "rgba(106, 208, 173, 0.5)" },
              ]),
            },
            emphasis: {
              label: {
                show: true,
                position: "top",
                formatter: (params) => {
                  if (params.value == 0) return "";
                  return `{a|${params.value}}`; // 使用 rich 設定換行和不同大小的文本
                },
                rich: {
                  a: {
                    fontSize: 12,
                    color: "#2FA7CD",
                  },
                },
                align: "center",
              },
            },
          },
        ],
      };

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

    const updateData = (res) => {
      if (chartInstance == null) {
        initChart();
      }
      accumulatedCount.value = res.accumulatedCount;
      averageWeekday.value = res.averageWeekday;
      averageWeekend.value = res.averageWeekend;
      overAverage.value = res.overAverage;
      option.value.xAxis[0].data = res.data.map((el) => el.hour);
      option.value.series[0].data = res.data.map((el) => el.total_num);
      let max = Math.max(...res.data.map((el) => el.total_num));
      // 最大值補到10的倍數
      option.value.yAxis[0].max = Math.ceil(max / 10) * 10;

      if (option.value && typeof option.value === "object") {
        chartInstance.setOption(option.value);
      }
    };

    const resize = () => {
      if (chartInstance) {
        chartInstance.resize();
      }
    };

    const formatNumberWithComma = (number) => {
      if (!number) {
        return "-";
      }
      if (number >= 100) {
        return number.toLocaleString();
      } else {
        return number.toString();
      }
    };

    return {
      chartRef,
      formatNumberWithComma,
      option,
      resize,
      updateData,
      accumulatedCount,
      averageWeekday,
      averageWeekend,
      overAverage
    };
  },
};
</script>
