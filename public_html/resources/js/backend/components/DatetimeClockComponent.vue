<template>
  <div class="date-box" v-if="date != null">
    <div class="icon"></div>
    <div class="text-date" v-if="current_date.length > 0">
      {{ current_date }}
    </div>
    <div class="text-time" v-if="current_time.length > 0">
      {{ current_time }}
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      date: null,
      menus: [],
    };
  },
  mounted() {
    this.updateDate();
  },
  computed: {
    current_date() {
      if (this.date == null) {
        return "";
      }
      let year = this.date.getFullYear();
      let month = this.date.getMonth() + 1;
      month = String(month).padStart(2, "0");
      let date = this.date.getDate();
      date = String(date).padStart(2, "0");
      return `${year}-${month}-${date}`;
    },
    current_time() {
      if (this.date == null) {
        return "";
      }
      let hour = this.date.getHours();
      let minute = this.date.getMinutes();
      minute = String(minute).padStart(2, "0");
      let second = this.date.getSeconds();
      second = String(second).padStart(2, "0");

      /*
        早上6.01-12.
        下午12.01-18.
        晚上18.01-24.
        凌晨0.01-6.*/

      let pre_text = ((hour) => {
        switch (true) {
          case hour >= 0 && hour < 6:
            return "凌晨";
          case hour >= 6 && hour < 12:
            return "早上";
          case hour >= 12 && hour < 18:
            return "下午";
          case hour >= 18 && hour <= 23:
            return "晚上";
          default:
            return "";
        }
      })(hour);
      if (hour > 12) {
        hour -= 12;
      }
      hour = String(hour).padStart(2, "0");
      return `${pre_text} ${hour}:${minute}`;
    },
  },
  methods: {
    async updateDate() {
      this.date = new Date();
      requestAnimationFrame(this.updateDate);
    },
  },
};
</script>