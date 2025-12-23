<template>
  <div class="range-indicator">
    <div class="tick">
      <template v-for="(tick, index) in max + 1" :key="index">
        <span
          v-if="index == 0 || index % (max / (splitNumber - 1)) == 0"
          >{{ max - index }}</span
        >
      </template>
    </div>
    <div
      class="line"
      :style="{
        width: '10px',
        height: '100%',
        background: background,
        borderRadius: '5px',
      }"
    ></div>
    <div class="indicator">
      <div
        class="pointer"
        :style="{ top: pointerTop }"
      >
        <svg width="28" height="17" viewBox="0 0 28 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.4392 3.89064L0 8.22559L12.4396 12.5616C13.8644 14.7688 16.3382 16.225 19.1594 16.2315C23.579 16.2309 27.1635 12.6505 27.1629 8.23086C27.1623 3.81123 23.5819 0.226691 19.1623 0.227293C16.3433 0.229012 13.8708 1.68945 12.4444 3.89367L12.4392 3.89064ZM24.347 8.22734C24.3485 11.0951 22.0224 13.4163 19.1577 13.4125C16.2898 13.414 13.9687 11.088 13.9725 8.22321C13.9762 5.35843 16.297 3.03427 19.1618 3.03801C22.0296 3.03651 24.3507 5.36256 24.347 8.22734Z" fill="white"/>
        </svg>

      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref } from 'vue';
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
    }
  },
  setup(props) {
    const data = ref(0);
    const max = props.max;
    const splitNumber = props.splitNumber;
    const updateData = (res) => {
        data.value = res;
    }
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
        return `linear-gradient(to top ${gradient})`;
    });
    const pointerTop = computed(() => {
        // console.log(max, data.value);
        if (data.value) {
            return `calc(100% - (${data.value/max*100}% + 8px))`;
        }
        return 'calc(100% - 8px)';
    });
    return {
        updateData,
        background,
        pointerTop,
        splitNumber
    }
  }
};
</script>