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
    const currentDate = new Date();
    const initChart = () => {
      const dom = chartRef.value;
      chartInstance = echarts.init(dom, null, {
        renderer: "canvas",
        useDirtyRect: false,
      });
      option.value = {
        title: {
          show: false,
        },
        grid: {
          left: "20px",
          right: "20px",
          bottom: "20px",
          top: "20px"
        },
        xAxis: {
          type: "time",
          splitNumber: 10,
          boundaryGap: false,
          min: new Date(currentDate.getFullYear(), currentDate.getMonth(), 1),
          max: new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0),
          axisLine: {
            lineStyle: {
              color: '#fff'
            }
          },
        },
        yAxis: {
          type: "value",
          min: 0,
          max: 100,
          splitNumber: 10,
          minInterval: 10,
          axisLabel: {
            color: '#fff',
            formatter: function (value) {
              for (let i = 0; i < grading_criteria.length; i++) {
                if (value == grading_criteria[i][0]) {
                    return grading_criteria[i][1];
                }
              }
              return "";
            },
          },
          axisLine: {
            show: true,
            lineStyle: {
                color: '#fff'
            }
          },
          splitLine: {
            show: false,
          },
          boundaryGap: [0, "100%"],
        },
        series: [
          {
            type: "line",
            symbol: "circle",
            symbolSize: 5,
            itemStyle: {
                color: "#00FFCF", // 選取狀態下的點顏色
            },
            lineStyle: {
              width: 1,
              color: "#00FFCF",
            },
            label: {
              show: false,
            },
            areaStyle: {
              opacity: 0.2,
              color: new echarts.graphic.LinearGradient(0, 1, 0, 0, [
                { offset: 0, color: "rgba(0, 255, 207, 0)" },
                { offset: 0.25, color: "rgba(0, 255, 207, 0.1)" },
                { offset: 0.5, color: "rgba(0, 255, 207, 0.36)" },
                { offset: 0.75, color: "rgba(0, 255, 207, 0.76)" },
                { offset: 1, color: "rgba(0, 255, 207, 1)" },
              ]),
            },
            data: [],
            emphasis: {
              symbol: "circle",
              scale: 1.5,
              itemStyle: {
                color: "#fff", // 選取狀態下的點顏色
              },
            },
            markLine: {
              silent: true,
              symbol: "none",
              lineStyle: {
                color: "#00FFCF",
                type: "solid",
              },
              data: (() => {
                let line = [];
                for (let i = 0; i < grading_criteria.length; i++) {
                  if (grading_criteria[i][0] == 0) {
                      continue;
                  }
                  line.push({
                    name: grading_criteria[i][1],
                    yAxis: grading_criteria[i][0],
                    label: {
                        show: false,
                    },
                  });
                }
                return line;
              })(),
            },
          },
        ],
      };
      chartInstance.setOption(option.value);
    };

    onMounted(() => {
      //initChart();
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
      let data = res.map((el) => {
        return [
          el.date,
          Math.round((el.filter_count / el.total_count) * 100) || 0,
        ];
      });
      option.value.series[0].data = data;
      if (option.value && typeof option.value === "object") {
        chartInstance.setOption(option.value);
      }
    };

    return {
      chartRef,
      reload,
      option,
      resize,
    };
  },
};
</script>