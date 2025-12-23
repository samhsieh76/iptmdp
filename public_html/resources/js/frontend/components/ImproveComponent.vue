<template>
    <v-section-box :title="$t('frontend_improve_record')" :hasContent="hasContent" class="improve">
        <template #section-content>
            <div class="table-block">
                <div :class="{ 'table-control': true, disabled: PageIndex == 0 }" @click="handlePrevClick()">
                    <svg width="17" height="29" viewBox="0 0 17 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 27L3 14.5009L15 2" stroke-width="3" stroke-miterlimit="10" />
                    </svg>
                </div>
                <div class="table-content">
                    <table>
                        <thead>
                            <th>{{ $t('frontend_number') }}</th>
                            <th>{{ $t('frontend_abnormal_type') }}</th>
                            <th>{{ $t('frontend_sensor_name') }}</th>
                            <th>{{ $t('frontend_improve_efficiency') }}</th>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in Records[PageIndex]" :key="index">
                                <td>{{ PageIndex * pageLength + index + 1 }}</td>
                                <td>{{ record.abnormal_type }}</td>
                                <td>{{ record.sensor_name }}</td>
                                <td>{{ record.efficient }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div :class="{
                    'table-control': true,
                    disabled: PageIndex >= Records.length - 1
                }" @click="handleNextClick()">
                    <svg width="17" height="29" viewBox="0 0 17 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 2L14 14.4991L2 27" stroke-width="3" stroke-miterlimit="10" />
                    </svg>
                </div>
            </div>
        </template>
    </v-section-box>
</template>

<script>
import { onMounted, ref, inject, watch, nextTick, getCurrentInstance } from "vue";
import SectionBox from "../plugin/SectionBox.vue";

export default {
  components: {
    "v-section-box": SectionBox,
  },
  props: {
    dataUrl: {
      type: String
    },
  },
  setup(props) {
    const {
      appContext: {
        config: { globalProperties },
      },
    } = getCurrentInstance();
    const sharedData = inject("sharedData");

    const hasContent = ref(false);

    const pageLength = 5;
    const Records = ref([]);
    const PageIndex = ref(0);

    watch(
      () => sharedData.toilet_id,
      (newValue, oldValue) => {
        nextTick(() => {
          updateData(newValue);
        });
      }
    );

    const updateData = (toilet_id) => {
      fetchData(toilet_id, props.dataUrl)
        .then((res) => {
          if (res.length <= 0) {
            throw "res length 0";
          }
          hasContent.value = true;
          const records = Array.from(
            { length: Math.ceil(res.length / pageLength) },
            (_, index) =>
              res.slice(index * pageLength, index * pageLength + pageLength)
          );
          PageIndex.value = 0;
          Records.value = records;
        })
        .catch((error) => {
          hasContent.value = false;
          Records.value = [];
          PageIndex.value = 0;
        });
    };

    const fetchData = (toilet_id, url) => {
      return new Promise((resolve, reject) => {
        if (!toilet_id) {
          return reject();
        }
        axios
          .get(url, {
            params: {
              toilet_id: toilet_id,
              date: globalProperties.$utils.$getToday(),
            },
          })
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    };
    const handlePrevClick = () => {
      if (PageIndex.value !== null && PageIndex.value > 0) {
        PageIndex.value--;
      }
    };
    const handleNextClick = () => {
      if (
        PageIndex.value !== null &&
        PageIndex.value < Records.value.length - 1
      ) {
        PageIndex.value++;
      }
    };

    onMounted(() => {
      updateData();
    });

    return {
      Records,
      PageIndex,
      pageLength,

      hasContent,

      handlePrevClick,
      handleNextClick,
    };
  },
};
</script>