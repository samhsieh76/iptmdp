<template>
    <div class="frontend-search-box">
        <v-select2 class="select2 input-control" name="location_id" :options="locationOptions"
            :placeholder="$t('frontend_search_placeholder')" :settings="settings" v-model="location_id"
            @select="changeEvent($event)">
        </v-select2>
    </div>
</template>

<style></style>

<script>
import { onMounted, ref, inject, computed, getCurrentInstance } from "vue";
import Select2 from "vue3-select2-component";

export default {
  components: {
    "v-select2": Select2,
  },
  setup(props) {
    const { appContext : { config: { globalProperties } } } = getCurrentInstance();
    const sharedData = inject('sharedData');

    const location_id = ref("");
    const locations = sharedData.locations;

    const settings = {
      allowClear: true,
      language: { noResults: () => globalProperties.$t("not_found") },
    };

    onMounted(() => {
      location_id.value = "";
    });

    const hasSearch = computed(() => {
      return locations.length > 0;
    });

    const locationOptions = computed(() => {
      return locations.map((el) => {
        return {
          id: el.id,
          text: `${el.name}`,
        };
      });
    });

    const changeEvent = (event) => {
      if (location_id.value != null) {
        sharedData.location_id = location_id.value
      }
    };

    return {
      location_id,
      settings,
      hasSearch,
      locationOptions,
      changeEvent,
    };
  },
};
</script>