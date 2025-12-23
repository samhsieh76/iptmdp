<template>
  <h1>{{ data }}{{ unit }}</h1>
  <div class="range-indicator">
    <div class="tick">
      <template v-for="(tick, index) in max + 1" :key="index">
        <span v-if="index == 0 || index % (max / (splitNumber - 1)) == 0">{{
          index
        }}</span>
      </template>
    </div>
    <div
      class="line"
      :style="{
        width: '100%',
        height: '10px',
        background: background,
        borderRadius: '5px',
      }"
    ></div>
    <div class="indicator">
      <div
        class="pointer"
        :style="{ left: `calc(${(data / max) * 100}% - 5px)` }"
      >
        <svg
          width="10"
          height="16"
          viewBox="0 0 10 16"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M7.71135 7.26199L5.1806 0L2.64921 7.26225C1.36068 8.09406 0.510562 9.53823 0.506736 11.1852C0.507087 13.7654 2.59735 15.8581 5.17753 15.8577C7.7577 15.8574 9.85036 13.7671 9.85001 11.1869C9.84901 9.54122 8.9964 8.09781 7.70958 7.26505L7.71135 7.26199ZM5.17958 14.2138C3.50535 14.2147 2.15027 12.8567 2.15246 11.1842C2.15158 9.51002 3.50953 8.15494 5.18199 8.15712C6.85445 8.15931 8.2113 9.5142 8.20911 11.1867C8.20999 12.8609 6.85204 14.216 5.17958 14.2138Z"
            fill="#A0A0A0"
          />
        </svg>
      </div>
    </div>
  </div>
</template>

<style>
.range-indicator {
  width: 100%;
}

.tick {
  font-family: Century Gothic Pro;
  width: calc(100% + 1.4rem);
  display: flex;
  justify-content: space-between;
  margin-left: -0.7rem;
  font-size: 0.875rem;
  font-style: normal;
  font-weight: 400;
  line-height: normal;
}

.tick span {
  /* width: 1.4rem; */
  color: #a0a0a0;
}

.indicator {
  position: relative;
  width: 100%;
}

.pointer {
  position: absolute;
  line-height: 18px;
}
</style>
<script>
import { computed, ref } from "vue";

export default {
  props: {
    max: {
      type: Number,
      default: 50,
    },
    splitNumber: {
      type: Number,
      default: 6,
    },
    colors: {
      type: Array,
      default: [
        [0.3, "#2595BD"],
        [0.54, "#95BE52"],
        [1, "#FF4E00"],
      ],
    },
    unit: {
      type: String,
      default: "",
    },
  },
  setup(props) {
    const data = ref(0);

    const updateData = (data_value) => {
      data.value = data_value;
    };

    const background = computed(() => {
      let gradient = "";
      props.colors.forEach((color, index) => {
        if (index == 0) {
          gradient = `${gradient},${color[1]} 0%`;
        } else {
          gradient = `${gradient},${color[1]} ${
            props.colors[index - 1][0] * 100
          }%`;
        }
        gradient = `${gradient},${color[1]} ${color[0] * 100}%`;
      });
      return `linear-gradient(to right ${gradient})`;
    });

    return {
      data,
      background,
      updateData,
    };
  },
};
</script>