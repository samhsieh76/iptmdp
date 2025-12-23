<template>
  <div class="site-box">
    <div class="section-box" v-if="county != null">
      <div class="half-bg"></div>
      <div class="left-bottom-border"></div>
      <div class="right-bottom-border"></div>
      <div class="site-county">
        <div class="county-left-border"></div>
        {{ county.name }}
        <div class="county-right-border"></div>
      </div>
      <div class="toilet-box" v-if="location">
        <div class="item location-name">{{ location.name }}</div>
        <ul>
          <li :class="{item: true, active: toilet.id == toilet_id}" v-for="toilet in location_toilets" :key="toilet.id"
            @click.stop="handleSelectToilet(toilet.id)">
            {{ toilet.name }}-{{ toilet_type_options[toilet.type] }}
          </li>
        </ul>
      </div>
      <div class="location-box" v-else>
        <ul>
          <li v-for="location in county_locations" :key="location.id" @click.stop="handleSelectLocation(location.id)">
            {{ location.name }}
          </li>
        </ul>
      </div>
    </div>
    <div class="click-map" v-else>
      <div class="click-map-icon"></div>
      <span>{{ $t("click_map") }}</span>
    </div>
  </div>
</template>

<script>
import { onMounted, ref, inject, watch } from "vue";

export default {
  props: {
    locationToiletUrl: {
      type: String
    },
    toiletTypeOptions: {
      type: Object
    }
  },
  setup(props) {
    const sharedData = inject("sharedData");
    const locations = sharedData.locations;
    let county = ref(null);
    let location = ref(null);
    let county_locations = ref(null);
    let location_toilets = ref(null);
    let toilet_id = ref(null);
    const toilet_type_options = props.toiletTypeOptions;

    watch(
      () => sharedData.county,
      (newValue, oldValue) => {
        county.value = newValue;
        handleCountyChange(county.value);
      }
    );
    watch(
      () => sharedData.location,
      (newValue, oldValue) => {
        handleLocationChange(newValue);
        getLocationToilets(newValue);
      }
    );
    watch(
      () => sharedData.location_toilets,
      (newValue, oldValue) => {
        location_toilets.value = newValue;
        sharedData.toilet_id = newValue[0]?.id || null;
      }
    );
    watch(
      () => sharedData.toilet_id,
      (newValue, oldValue) => {
        toilet_id.value = newValue;
      }
    );
    onMounted(() => {
      if (sharedData.county) {
        handleCountyChange(sharedData.county);
      }
      if (sharedData.location) {
        handleLocationChange(sharedData.location);
        getLocationToilets(sharedData.location);
      }
    });

    const handleSelectLocation = (location_id) => {
      sharedData.location_id = location_id;
    };

    const handleSelectToilet = (toilet_id) => {
      sharedData.toilet_id = toilet_id;
    };

    const handleLocationChange = (selectedLocation) => {
      location.value = selectedLocation;
    };

    const getLocationToilets = (location) => {
      new Promise((resolve, reject) => {
        if (!location) {
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
      }).then((res) => {
        sharedData.location_toilets = res;
      }).catch((error) => {
        sharedData.location_toilets = [];
      });
    };

    const handleCountyChange = (selectedCounty) => {
      county.value = selectedCounty;
      county_locations.value = locations.filter(
        (el) => el.county.id == selectedCounty?.id
      );
    };
    return {
      county,
      location,
      county_locations,
      location_toilets,
      toilet_id,
      toilet_type_options,
      handleSelectLocation,
      handleSelectToilet
    };
  }
};
</script>
