<template>
  <div class="operational-score-box" v-show="$root.mode == $root.modeMap.TAIWAN || $root.mode == $root.modeMap.COUNTY">
    <div class="score-box">
      <div class="score-gauge">
        <score-gauge
          ref="operateScoreGauge"
          color="#00FFCA"
          id="operate-score-gauge"
        ></score-gauge>
      </div>
      <div class="score-text">
        <span class="operate-score-text" v-if="$root.mode == $root.modeMap.TAIWAN">全台{{ $t("operate_score") }}</span>
        <span class="operate-score-text" v-if="$root.mode == $root.modeMap.COUNTY && county">{{ county.name }}{{ $t("operate_score") }}</span>
        <div class="score-content">
          <span>{{ $t("total") }}</span>
          <span class="number">{{ location_number }}</span>
          <span>{{ $t("operate_locations") }}</span>
        </div>
      </div>
    </div>
    <div class="score-box">
      <div class="score-gauge">
        <score-gauge
          ref="processScoreGauge"
          color="#FF4E00"
          id="process-score-gauge"
        ></score-gauge>
      </div>
      <div class="score-text">
        <span class="process-score-text" v-if="$root.mode == $root.modeMap.TAIWAN">全台{{ $t("process_score") }}</span>
        <span class="process-score-text" v-if="$root.mode == $root.modeMap.COUNTY && county">{{ county.name }}{{ $t("process_score") }}</span>
        <div class="score-content">
          <!-- <span>{{ $t("total") }}</span> -->
          <span>{{ $t("process_abnormal") }}<span class="number">{{ process_number }}</span>分鐘</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, ref, inject, watch, getCurrentInstance, shallowRef} from "vue";
import ScoreGauge from "../plugin/ScoreGauge";

export default {
  components: {
    "score-gauge": ScoreGauge,
  },
  props: {
    operateDataUrl: {
      type: String,
    },
    processDataUrl: {
      type: String,
    }
  },
  setup(props) {

    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();

    let operateScoreGauge = shallowRef(null);
    let processScoreGauge = shallowRef(null);

    let location_number = ref(null)
    let process_number = ref(null)
    let county = ref(null)

    onMounted(() => {
      updateData()
    });

    const sharedData = inject("sharedData");
    watch(
      () => sharedData.county,
      (newValue, oldValue) => {
        county.value = newValue;
        updateData();
      }
    );

    const fetchData = (url) => {
      return new Promise((resolve, reject) => {
        axios
          .get(url, {
            params: {
              date: globalProperties.$utils.$getToday(),
              county_id: sharedData.county?.id
            },
          })
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    }

    const updateData = () => {
      fetchData(props.processDataUrl).then((res) => {
        process_number.value = res.average_processing ?? '-';
        // processScoreGauge.value?.reload(Math.round((res.filter_count / res.total_count) * 100) || 0);
      });
      fetchData(props.operateDataUrl).then((res) => {
        location_number.value = res.filter_count;
        operateScoreGauge.value?.reload(Math.round((res.filter_count / res.total_count) * 100) || 0);

        sharedData.operational_data = res;
      });
    };

    const resize = () => {
        operateScoreGauge.value?.resize();
        processScoreGauge.value?.resize();
    };
    return {
        operateScoreGauge,
        processScoreGauge,
        resize,
        location_number,
        process_number,
        county
    }
  },
};
</script>