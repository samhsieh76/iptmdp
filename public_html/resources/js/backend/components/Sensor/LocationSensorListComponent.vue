<template>
  <v-content overflow-y="hidden">
    <template #content_header>
      <slot name="breadcrumb"></slot>
      <ul class="nav" style="gap: 0.625rem">
        <button
          :class="`btn btn-sensor btn-${sensor} scroll-button`"
          :data-section="`scroll_${sensor}`"
          v-for="(sensor, index) in sensors"
          :key="index"
          :ref="`scrollSections_scroll_${sensor}`"
          @click="scrollToSection(`scroll_${sensor}`)"
        >
          {{ $t(`${sensor}_sensors`) }}
        </button>
      </ul>
    </template>
    <template #content>
      <div
        :style="{
          width: '100%',
          height: '100%',
          flexGrow: '1',
          padding: '1rem 0',
          overflowY: 'scroll',
        }"
        ref="scrollableDiv"
      >
        <section
          class="row scroll-section"
          v-for="table in tables"
          :key="table.id"
          :id="`scroll_${table.name}`"
        >
          <div class="col-12">
            <h1 class="sensor_title">{{ $t(`${table.name}_sensors`) }}</h1>
          </div>
          <div class="col-12 table-responsive location-sensors">
            <data-table :columns="table.columns" :options="table.options">
              <thead>
                <tr>
                  <th v-for="(thead, index) in table.thead" :key="index">
                    {{ thead }}
                  </th>
                </tr>
              </thead>
            </data-table>
          </div>
        </section>
      </div>
    </template>
  </v-content>
</template>

<style type="scss">
.sensor_title {
  color: #afafaf;
  font-size: 1.5rem;
  font-weight: 600;
}
.location-sensors .dataTables_wrapper.no-footer .dataTables_scrollBody {
  border-bottom: none;
  border-radius: 0 0 20px 20px;
}
.scroll-section.row {
  margin: 0;
  margin-bottom: 1.5rem;
}
</style>
<script>
import contentComponent from "../ContentComponent.vue";
import DataTable from "datatables.net-vue3";
import DataTablesCore from "datatables.net";

DataTable.use(DataTablesCore);
export default {
  components: {
    "data-table": DataTable,
    "v-content": contentComponent,
  },
  data() {
    return {
      sensors: [
        "human_traffic",
        "toilet_paper",
        "hand_lotion",
        "smelly",
        "relative_humidity",
        "temperature",
      ],
      tables: [],
    };
  },
  props: {
    sensorUrl: {
      type: Object,
    },
    typeOptions: {
      type: Object,
    },
    userLevel: {
      type: String,
    },
  },
  methods: {
    handleScroll() {
      let scrollButtons = document.querySelectorAll(".scroll-button");

      // Step 1: 先移除所有按鈕的 active 類別
      /* scrollButtons.forEach(function (button) {
        button.classList.remove("active");
      }); */

      // Step 2: 建立 Intersection Observer
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          let sectionId = entry.target.getAttribute("id");
          scrollButtons.forEach(function (button) {
            if (button.getAttribute("data-section") === sectionId) {
              if (entry.isIntersecting) {
                // 如果區塊在畫面上可見，則加上 active 類別
                button.classList.add("active");
              } else {
                // 否則移除 active 類別
                button.classList.remove("active");
              }
            }
          });
        });
      });

      // Step 4: 監測所有區塊元素
      let scrollSections = document.querySelectorAll(".scroll-section");
      scrollSections.forEach((section) => {
        observer.observe(section);
      });
    },

    scrollToSection(sectionId) {
      // 取得目標區塊元素
      const targetSection = document.getElementById(sectionId);

      // 使用 scrollIntoView 進行平滑滾動
      if (targetSection) {
        targetSection.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    },
  },
  mounted() {
    const common_fields = ["id", "name", "latest_value", "latest_updated_at"];
    this.tables.length = 0;
    for (let i = 0; i < this.sensors.length; i++) {
      let sensor_name = this.sensors[i];
      this.tables.push({
        id: `${sensor_name}_table`,
        name: `${sensor_name}`,
        thead: [
          "ID",
          this.$t("toilet_name"),
          this.$t(`${sensor_name}_head`),
          this.$t("updated_at"),
        ],
        options: {
          processing: true,
          serverSide: true,
          searching: false,
          paging: false,
          lengthChange: false,
          info: false,
          language: this.$t("datatable_language"),
          scrollX: true,
          ajax: {
            url: this.sensorUrl[sensor_name],
          },
        },
        columns: common_fields.map((el) => {
          let render = null;
          let visible = true;
          if (sensor_name == "smelly" && el == "latest_value") {
            el = "latest_raw_data";
          }
          switch (el) {
            case "id":
              visible = this.userLevel <= 0;
              break;
            case "name":
              render = (data, topic, row) => {
                return `${row.toilet_name}-${
                  this.typeOptions[row.toilet_type]
                }-${data}`;
              };
              break;
            case "latest_value":
            case "latest_raw_data":
              if (["relative_humidity", "toilet_paper"].includes(sensor_name)) {
                render = function (data, topic, row) {
                  return data ? `${data}%` : "-";
                };
              } else if ("hand_lotion".includes(sensor_name)) {
                render = (data, topic, row) => {
                  return data != null
                    ? `${
                        data == 1
                          ? this.$t("hand_lotion_fill")
                          : this.$t("hand_lotion_empty")
                      }`
                    : "-";
                };
              } else if ("temperature".includes(sensor_name)) {
                render = function (data, topic, row) {
                  return data ? `${data}˚C` : "-";
                };
              } else if ("human_traffic".includes(sensor_name)) {
                render = function (data, topic, row) {
                  return data ? `${data}人` : "無";
                };
              } else if ("smelly".includes(sensor_name)) {
                render = function (data, topic, row) {
                  return data ? `${data} ppm` : "-";
                };
              } else {
                render = function (data, topic, row) {
                  return data ? `${data}` : "-";
                };
              }
              break;
            case "latest_updated_at":
              render = function (data, topic, row) {
                const currentDate = new Date();
                const updatedDate = new Date(data);

                // 檢查日期是否是今天
                const isToday =
                  currentDate.toDateString() === updatedDate.toDateString();

                // 根據檢查結果設定不同的字體顏色
                const textColor = isToday ? "#000" : "#FF5348";

                // 使用 span 包裹字體，並添加自定義樣式
                return `<span style="color: ${textColor}">${
                  data ?? "-"
                }</span>`;
              };
              break;
            default:
              break;
          }
          return {
            data: el,
            width: "25%",
            render: render,
            visible: visible,
          };
        }),
      });
    }
    const scrollableDiv = this.$refs.scrollableDiv;
    scrollableDiv.addEventListener("scroll", this.handleScroll);

    this.$nextTick(() => {
      this.handleScroll();
    });
  },
  unmounted() {
    // 移除滾動事件監聽
    const scrollableDiv = this.$refs.scrollableDiv;
    scrollableDiv.removeEventListener("scroll", this.handleScroll);
  },
};
</script>