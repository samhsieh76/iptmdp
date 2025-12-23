<template>
    <v-section-box :title="location.name" :hasContent="toilet != null" v-if="location">
        <template #section-content>
            <div class="location-info" v-if="toilet">
                <div class="location-info-block">
                    <div class="first-row">
                        <div class="first-row-item">
                            <div class="location-info-title">
                                廁所編號
                            </div>
                            <div class="location-info-content">
                                {{ toilet.code ?? '' }}
                            </div>
                        </div>
                        <div class="first-row-item">
                            <div class="location-info-title">
                                開放時間
                            </div>
                            <div class="location-info-content">
                                {{ location.business_hours ?? '' }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="location-info-title">
                            管理單位
                        </div>
                        <div class="location-info-content">
                            {{ location.administrator.name ?? '' }}
                        </div>
                    </div>
                </div>
                <div class="toilet-image-block">
                    <img :src="toilet.image" alt="" width="100%" height="auto" />
                </div>
            </div>
        </template>
    </v-section-box>
</template>

<script>
import { onMounted, ref, inject, watch, computed } from "vue";
import SectionBox from "../plugin/SectionBox.vue";
export default {
    components: {
        "v-section-box": SectionBox,
    },
    props: {
        locationToiletUrl: {
            type: String
        }
    },
    setup(props) {
        const sharedData = inject("sharedData");
        let location = ref(null);
        let location_toilets = ref(null);
        let toilet = ref(null);

        onMounted(() => {
            location.value = sharedData.location;
        });

        watch(
            () => sharedData.toilet,
            (newValue, oldValue) => {
                if (newValue == null && location_toilets.length != 0) {
                    toilet.value = location_toilets[0];
                } else {
                    toilet.value = newValue;
                }
            }
        );

        watch(
            () => sharedData.location,
            (newValue, oldValue) => {
                location.value = newValue;
            }
        );
        
        return {
            location,
            location_toilets,
            toilet,
        };
    },
};
</script>