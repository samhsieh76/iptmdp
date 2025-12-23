<template>
    <div class="side-btns">
        <MDBTooltip direction="left" class="chart-info" v-for="(button, index) in btnList" v-model="tooltip_chart[index]" v-if="$root.mode === $root.modeMap.TOILET">
            <template #reference>
                <button class="btn side-btn" :disabled="$root.mode !== $root.modeMap.TOILET" :key="index"
                    @click="handleButtonClick(button)">
                    <img :class="`btn-${button}`" />
                </button>
            </template>
            <template #tip>
                <span>{{ btnContentList[index] }}</span>
            </template>
        </MDBTooltip>
    </div>
</template>
<script>
import { computed, inject, ref, getCurrentInstance } from "vue";
import { MDBTooltip } from "mdb-vue-ui-kit";

export default {
    components: {
        MDBTooltip,
    },
    props: {
        content: {
            type: String
        }
    },
    setup() {
        const btnList = ref([]);
        const btnContentList = ref([]);
        const sharedData = inject("sharedData");
        const handleScrollToBlock = inject("handleScrollToBlock");

        const tooltip_chart = ref([]);

        btnList.value = [
            'total-operation',
            'human-traffic',
            'toilet-paper',
            'hand-lotion',
            'smelly',
            'relative-humidity',
            'temperature',
            'improve-handling',
            'exception-notification'
        ];
        btnContentList.value = [
            '場域資訊',
            '人流統計',
            '廁紙剩餘量統計',
            '洗手液剩餘量統計',
            '氣味品質',
            '濕度統計',
            '溫度統計',
            '改善處理狀況',
            '異常通報事件'
        ];
        const {
            appContext: {
                config: { globalProperties },
            },
        } = getCurrentInstance();

        const handleButtonClick = (block) => {
            // console.log(block);
            handleScrollToBlock(block);
        }
        return {
            btnList,
            btnContentList,
            handleButtonClick,
            tooltip_chart
        }
    }
};
</script>