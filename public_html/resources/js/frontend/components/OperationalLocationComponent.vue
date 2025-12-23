<template>
  <v-section-box :title="$t('frontend_operation_location')" :hasContent="true">
    <template #section-content>
      <div
        class="operation-location taiwan"
        v-if="$root.mode === $root.modeMap.TAIWAN"
      >
        <div class="total-box">
          <span>全台場域總數/處</span>
          <div class="total_text">
            {{ locations.length }}
          </div>
        </div>
        <div class="chart-wrapper">
          <span>全台 北、中、南、東之場域數量</span>
          <v-taiwan-location-bar
            :res="taiwan_locations"
          ></v-taiwan-location-bar>
        </div>
      </div>
      <div
        class="operation-location county"
        v-if="$root.mode === $root.modeMap.COUNTY && county"
      >
        <div class="total-box">
          <span>全{{ county.name }}場域總數/處</span>
          <div class="total_text" v-if="county_locations">
            {{ county_locations.length }}
          </div>
        </div>
        <div class="slider-wrapper" v-if="location">
          <div class="slider-control">
            <div
              class="slider-control-button-prev"
              @click="handleSliderPrevClick()"
            >
              <svg
                width="22"
                height="13"
                viewBox="0 0 22 13"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M21 12L11 2L1 12"
                  stroke="#00FFCA"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
            <div
              class="slider-control-button-next"
              @click="handleSliderNextClick('next')"
            >
              <svg
                width="22"
                height="13"
                viewBox="0 0 22 13"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M1 1L11 11L21 1"
                  stroke="#00FFCA"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
          </div>
          <div class="slider-text-block">
            <p class="slider-location-count">
              {{ `${locationIndex + 1}/${county_locations.length}` }}
            </p>
            <p class="slider-location-name">{{ location.name }}</p>
            <ul class="slider-toilet-list">
              <li v-for="toilet in toiletCount" :key="toilet.type">
                {{ toilet.label }} | {{ toilet.count }} 間
              </li>
            </ul>
          </div>
          <div class="slider-image-block">
            <img :src="location.image" alt="" width="100%" height="auto" />
          </div>
        </div>
      </div>
    </template>
  </v-section-box>
</template>

<script>
import { onMounted, watchEffect,ref, inject, watch, computed, nextTick } from "vue";
import SectionBox from "../plugin/SectionBox.vue";
import TaiwanLocationBar from "../plugin/TaiwanLocationBar.vue";
export default {
  components: {
    "v-section-box": SectionBox,
    "v-taiwan-location-bar": TaiwanLocationBar,
  },
  props: {
    countyLocationUrl: {
      type: String,
    },
    locationToiletUrl: {
      type: String,
    },
    regions: {
      type: Array,
    },
    toiletTypeOptions: {
      type: Object,
    },
  },
  setup(props) {
    const sharedData = inject("sharedData");
    const locations = sharedData.locations;
    const county = ref(null);
    const taiwan_locations = ref(null);
    const county_locations = ref([]);
    const location_toilets = ref([]);
    // const toiletCount = ref([]);

    const locationIndex = ref(null);
    const location = computed(() => {
      if (
        locationIndex.value != null &&
        county_locations.value.length > locationIndex.value
      ) {
        return county_locations.value[locationIndex.value];
      }
      return null;
    });
    const regionCount = {};
    props.regions.forEach((item) => {
      if (!regionCount.hasOwnProperty(item.name)) {
        regionCount[item.name] = 0;
      }
    });
    locations.forEach((item) => {
      const regionName = item.county.region.name;
      if (regionCount.hasOwnProperty(regionName)) {
        regionCount[regionName]++;
      } else {
        regionCount[regionName] = 1;
      }
    });
    taiwan_locations.value = regionCount;

    watch(
      () => sharedData.county,
      (newValue, oldValue) => {
        county.value = newValue;
      }
    );

    watch(
      () => county.value,
      (newValue, oldValue) => {
        handleCountyChange(newValue);
      }
    );

    watch(
      () => sharedData.location,
      (newValue, oldValue) => {
        handleLocationChange(newValue);
      }
    );

    watch(location,
      (newValue, oldValue) => {
        getLocationToilets(newValue);
      }
    );

    const handleCountyChange = (newValue) => {
      new Promise((resolve, reject) => {
        if (!newValue) {
          return resolve([]);
        }
        axios
          .get(props.countyLocationUrl.replace("county_id", newValue.id))
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      }).then((res) => {
        county_locations.value = res;
        locationIndex.value = county_locations.value.length > 0 ? 0 : null;
      });
    };
    const handleLocationChange = (selectedCounty) => {
      county.value = selectedCounty;
    };
    const handleSliderPrevClick = () => {
      if (locationIndex.value !== null && locationIndex.value > 0) {
        locationIndex.value--;
      } else if (locationIndex.value !== null){
        locationIndex.value = county_locations.value.length - 1;
      }
    };

    const handleSliderNextClick = () => {
      if (
        locationIndex.value !== null &&
        locationIndex.value < county_locations.value.length - 1
      ) {
        locationIndex.value++;
      } else if (locationIndex.value !== null){
        locationIndex.value = 0;
      }
    };

    const getLocationToilets = (selectLocation) => {
      let location = JSON.parse(JSON.stringify(selectLocation));
      new Promise((resolve, reject) => {
        if (location == null) {
          return reject();
        }
        axios
          .get(props.locationToiletUrl.replace("location_id", location.id))
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      })
        .then((res) => {
          location_toilets.value = res;
        })
        .catch((error) => {
          location_toilets.value = [];
        });
    };

    onMounted(() => {
      county.value = sharedData.county;
    })

    return {
      locations,
      county,
      taiwan_locations,
      county_locations,
      locationIndex,
      handleSliderPrevClick,
      handleSliderNextClick,
      location_toilets,
      location,
      toiletCount: computed(() => {
        const res = [];
        Object.entries(props.toiletTypeOptions).forEach(([type, label]) => {
          const count =
            location_toilets.value?.filter((toilet) => toilet.type == type)?.length || 0;
          res.push({
            type,
            count,
            label,
          });
        });
        return res;
      }),
    };
  },
};
</script>