<template>
  <div class="map-box">
    <slot name="map" ref="map"></slot>
  </div>
</template>

<script>
import { onMounted, onUnmounted, ref, inject, watch, getCurrentInstance } from "vue";
import * as d3 from "d3";

export default {
  setup() {
    const mapRef = ref(null);
    const sharedData = inject("sharedData");
    const handleBack = inject("handleBack");
    const minMode = inject("minMode");
    const modeMap = inject("modeMap");

    const locations = sharedData.locations;

    var hoverCounty = [];
    locations.forEach((location) => {
      if (!hoverCounty.includes(location.county.code)) {
        hoverCounty.push(`code_${location.county.code}`);
      }
    });

    var county = sharedData.county;

    watch(
      () => sharedData.location,
      (newValue, oldValue) => {
        // console.log("Map Location changed:", newValue, oldValue);
        if (newValue != null) {
          handleLocationChange(newValue);
        }
      }
    );
    watch(
      () => sharedData.county,
      (newValue, oldValue) => {
        // console.log("MapCounty changed:", newValue, oldValue);
        county = newValue;
        handleCountyChange(county);
      }
    );

    onMounted(() => {
      draw(mapRef.value);
      handleCountyChange(county);
      document
        .getElementsByClassName("map-box")[0]
        .addEventListener("click", handleBack);
    });

    onUnmounted(() => {
      document
        .getElementsByClassName("map-box")[0]
        .removeEventListener("click", handleBack);
    });

    const draw = () => {
      // 使用 D3 選擇器選取所有的 path 元素
      const paths = d3.selectAll("#counties path");
      // 有一個以上的場域才可以選地點
      paths.on("click", function (event) {
        event.stopPropagation();
        if (locations.length <= 1) {
          return;
        }
        const id = d3.select(this).attr("id");
        if (id.startsWith("code_")) {
          let code = id.replace("code_", "");
          if (!hoverCounty.includes(id)) {
            return;
          }
          if (county && id == `code_${county.code}`) {
            return;
          }
          let location = locations.find((el) => el.county.code == code);
          if (location) {
            // 該區域至少要有一個場域
            sharedData.toilet_id = null;
            sharedData.location_id = null;
            sharedData.county = location.county;
            /* if (minMode() == modeMap.LOCATION) {
              sharedData.location_id = location.id;
            } */
          }
        }
      });
      // 使用 D3 的事件處理方法綁定 mouseover 和 mouseout 事件
      paths.on("mouseover", function () {
        const id = d3.select(this).attr("id");
        if (!hoverCounty.includes(id)) {
          return;
        }
        if (county && id == `code_${county.code}`) {
          return;
        }
        d3.select(this).style("fill", "#00FFCF").style("opacity", 0.49);
      });

      paths.on("mouseout", function () {
        const id = d3.select(this).attr("id");
        if (!hoverCounty.includes(id)) {
          return;
        }
        if (county && id == `code_${county.code}`) {
          return;
        }
        d3.select(this).style("fill", "").style("opacity", 0.49);
      });
    };

    const handleCountyChange = (selectedCounty) => {
      d3.selectAll("[id^='code_']").style("fill", null).style("opacity", 0.49);
      if (selectedCounty) {
        d3.select("#code_" + selectedCounty.code)
          .style("fill", "#00FFCF")
          .style("opacity", 1);
      }
    };

    const handleLocationChange = (selectedLocation) => {
      sharedData.county = selectedLocation?.county;
    };
    return {
      mapRef,
    };
  },
};
</script>
