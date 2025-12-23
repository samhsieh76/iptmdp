<template>
  <div class="global-search-box" v-if="hasSearch">
    <div class="input-prepend">
      <div class="input-prepend-text">
        <div class="icon-search"></div>
      </div>
    </div>
    <v-select2
      class="select2 input-control"
      name="location_id"
      :options="locationOptions"
      :placeholder="$t('search_locations')"
      :settings="settings"
      v-model="location_id"
      @select="changeEvent($event)"
    >
    </v-select2>
  </div>
</template>

<style>
.select2-container--default .select2-selection--single {
  background-color: #fff;
  border: unset;
  height: 100%;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: linear-gradient(180deg, rgba(19, 147, 199, 0.2) 0%, rgba(106, 209, 174, 0.2) 100%);
    color: #000;
}
  .select2-container--default
  .select2-selection--single
  .select2-selection__rendered {
  color: #444;
}
  .select2-container--default
  .select2-selection--single
  .select2-selection__arrow {
  height: 100%;
}
.global-search-box
  .select2-container--default
  .select2-selection--single
  .select2-selection__rendered {
  line-height: 40px;
}
:focus-visible {
  outline: none;
}
</style>

<script>
import Select2 from "vue3-select2-component";
export default {
  components: {
    "v-select2": Select2,
  },
  props: {
    locations: {
      type: Array,
    },
    toiletUrl: {
      type: String,
    },
  },
  data() {
    return {
      location_id: "",
      settings: {
        allowClear: true,
        language: { noResults: () => this.$t("not_found") },
      },
    };
  },
  mounted() {
    this.location_id = "";
  },
  computed: {
    hasSearch() {
      return this.locations.length > 0;
    },
    locationOptions() {
      return this.locations.map((el) => {
        return {
          id: el.id,
          text: `${el.name}`,
        };
      });
    }
  },
  methods: {
    changeEvent(event) {
      let location_id = this.location_id;
      if (location_id != null && location_id.length > 0) {
        this.location_id = null;
        window.location = this.toiletUrl.replace('location_id', location_id);
      }
    },
  },
};
</script>