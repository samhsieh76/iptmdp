<template>
  <div
    class="echarts-box"
    :style="{
      width: '100%',
      height: '100%',
      background: 'white',
      borderRadius: '30px',
      gridRow: '1',
      padding: '1rem',
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
import moment from "moment";

export default {
  props: {
    url: {
      type: String,
    },
    searchValue: {
      type: Object,
    },
    max: {
      type: String,
    },
    unit: {
      type: String,
      default: "",
    },
    splitNumber: {
      type: Number,
      default: 10,
    },
    minInterval: {
      type: Number,
      default: 1,
    },
    title: {
      type: String,
      default: "",
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
        tooltip: {
          trigger: "axis",
          position: "inside",
        },
        title: {
          left: "left",
          text: props.title,
        },
        grid: {
          left: "100px",
          right: "100px",
          bottom: "50px",
        },
        xAxis: {
          type: "time",
          // splitNumber: 20,
          boundaryGap: false,
          axisLabel: {
            interval: "auto",
            fontSize: 14,
          },
        },
        yAxis: {
          type: "value",
          min: 0,
          splitNumber: props.splitNumber,
          minInterval: props.minInterval,
          axisLabel: {
            formatter: `{value}${props.unit}`,
            fontSize: 14,
          },
          splitLine: {
            lineStyle: {
              type: "dashed",
              dashOffset: 5,
            },
          },
          boundaryGap: [0, "100%"],
        },
        series: [
          {
            name: props.title,
            type: "line",
            symbol: "circle",
            symbolSize: 0.1,
            lineStyle: {
              width: 0,
            },
            itemStyle: {
              color: "#2FABD1", // 選取狀態下的點顏色
            },
            label: {
              show: false,
            },
            areaStyle: {
              opacity: 0.2,
              color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 1, color: "rgba(106, 209, 174)" },
                { offset: 0, color: "rgba(19, 147, 199)" },
              ]),
            },
            data: [],
            emphasis: {
              label: {
                show: true,
                position: "top",
                formatter: (params) => {
                  const yValue = params.value[1]; // 取得 y 值
                  const xValue = params.value[0]; // 取得 x 值
                  const [date, time] = xValue.split(" "); // 分割日期和時間
                  return `{a|${date}\n${time}}\n{b|${yValue}${props.unit}}`; // 使用 rich 設定換行和不同大小的文本
                },
                rich: {
                  a: {
                    fontSize: 16,
                    color: "#2FABD1",
                  },
                  b: {
                    fontSize: 24,
                    color: "#2FABD1",
                  },
                },
                align: "center",
              },
              symbol: "circle",
              scale: 100,
              itemStyle: {
                color: "#2FABD1", // 選取狀態下的點顏色
              },
            },
          },
        ],
      };
      if (props.max) {
        option.value.yAxis.max = props.max;
      }
      chartInstance.setOption(option.value);
    };

    const updateData = () => {
      return new Promise((resolve, reject) => {
        let options = {
          url: props.url,
          method: "GET",
          params: props.searchValue,
        };
        axios
          .request(options)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };

    const reload = () => {
      updateData()
        .then((res) => {
          // @todo option的tooltip看是要隱藏或是調一下顯示的資訊
          // @todo x軸顯示方式看要不要調整
          let title = "";
          title =
            globalProperties.$t("search_log_options")[
              props.searchValue.date_interval
            ];
          const localStartDate = moment(props.searchValue.start_date)
            .local()
            .format("Y/MM/DD");
          const localEndDate = moment(props.searchValue.end_date)
            .local()
            .format("Y/MM/DD");
          if (localStartDate == localEndDate) {
            title = title + `(${localEndDate})`;
          } else {
            title = title + `(${localStartDate}~${localEndDate})`;
          }

          option.value.title.text = title;
          option.value.series[0].data = res;

          if (option.value && typeof option.value === "object") {
            chartInstance.setOption(option.value);
          }
        })
        .catch((error) => console.log(error));
    };

    onMounted(() => {
      initChart();
    });

    onUnmounted(() => {
      chartInstance.dispose();
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