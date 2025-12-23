require("../bootstrap");
import {
    createApp,
    reactive,
    provide,
    ref,
    toRef,
    computed,
    onMounted,
    onUnmounted,
} from "vue";
import "default-passive-events";
import SideButtonComponent from "./components/SideButtonComponent";
import SearchComponent from "./components/SearchComponent";
import ClockComponent from "./components/ClockComponent";
import LeaveModalComponent from "./components/LeaveModalComponent";
import MapComponent from "./components/MapComponent";
import SiteSelectionComponent from "./components/SiteSelectionComponent";
import OperationalLocationComponent from "./components/OperationalLocationComponent";
import PerformanceScoreComponent from "./components/PerformanceScoreComponent";
import LocationInfoComponent from "./components/LocationInfoComponent";
import OperationalTodayChartComponent from "./components/OperationalTodayChartComponent";
import OperationalMonthlyChartComponent from "./components/OperationalMonthlyChartComponent";
import ToiletPaperStatisticsBlockComponent from "./components/ToiletPaperStatisticsBlockComponent";
import HumanTrafficStatisticsBlockComponent from "./components/HumanTrafficStatisticsBlockComponent";
import HandLotionStatisticsBlockComponent from "./components/HandLotionStatisticsBlockComponent";
import TempHumidityStatisticsBlockComponent from "./components/TempHumidityStatisticsBlockComponent";
import SmellyStatisticsBlockComponent from "./components/SmellyStatisticsBlockComponent";
import AbnormalComponent from "./components/AbnormalComponent";
import ImproveComponent from "./components/ImproveComponent";
import utilities from "../utilities";
import lang from "../../lang/zh-TW.json";
import toast from "../toast";

const app = createApp(
    {
        props: {
            params: {
                type: Object,
                required: true,
            },
        },
        setup(props) {
            const humanTrafficBlock = ref(null);
            const toiletPaperBlock = ref(null);
            const smellyBlock = ref(null);
            const handLotionBlock = ref(null);
            const tempHumidityBlock = ref(null);
            const locationBlock = ref(null);
            const improveBlock = ref(null);
            const abnormalBlock = ref(null);
            const loading = ref(true);

            const params = props.params;
            const canFullLocation = params.canFullLocation;
            const canFullToilet = params.canFullToilet;
            const userLevel = params.userLevel;

            const modeMap = {
                TAIWAN: 1,
                COUNTY: 2,
                LOCATION: 3,
                TOILET: 3,
            };

            const county = ref(null);
            const location_id = ref(null);
            const toilet_id = ref(null);

            const locations = ref([]);

            // 創建共享的響應式數據對象
            const sharedData = reactive({
                county: county.value,
                location_id: location_id.value,
                toilet_id: toilet_id.value,
                locations: locations.value,
                location: computed(() => {
                    if (sharedData.locations && sharedData.location_id) {
                        return sharedData.locations.find(
                            (item) => item.id == sharedData.location_id
                        );
                    }
                    return null;
                }),
                location_toilets: [],
                operational_data: {
                    monthly_records: [],
                    total_count: 0,
                    filter_count: 0,
                },
                toilet: computed(() => {
                    if (sharedData.location_toilets && sharedData.toilet_id) {
                        return sharedData.location_toilets.find(
                            (item) => item.id == sharedData.toilet_id
                        );
                    }
                    return null;
                }),
            });

            // 設定完後定義最小模式
            const minMode = () => {
                // 全台
                if (canFullLocation) {
                    return modeMap.TAIWAN;
                }
                // 縣市
                if (canFullToilet && userLevel >= 2) {
                    return modeMap.COUNTY;
                }
                // 場域
                if (canFullToilet) {
                    return modeMap.TOILET;
                }
                // 供應商
                return modeMap.TAIWAN;
                /* if (sharedData.locations.length > 1) {
                    return modeMap.COUNTY;
                }
                return modeMap.TOILET; */
            };

            const mode = computed(() => {
                // 全台
                if (sharedData.county == null) {
                    return Math.max(modeMap.TAIWAN, minMode());
                }
                // 縣市
                if (sharedData.location_id == null) {
                    return Math.max(modeMap.COUNTY, minMode());
                }
                // 場域
                if (sharedData.toilet_id == null) {
                    return Math.max(modeMap.LOCATION, minMode());
                }
                // 廁所
                return modeMap.TOILET;
            });

            // 提供共享的響應式數據給其他元件使用
            provide("sharedData", sharedData);

            onMounted(() => {

                window.addEventListener('pageshow', function(event) {
                    // 檢查 event.persisted 屬性來確認是否是從瀏覽器緩存中載入的頁面
                    if (event.persisted) {
                        // 重新整理登入頁面
                        window.location.reload();
                    }
                });

                fetchLocations().then((res) => {
                    sharedData.locations = res;

                    switch (minMode()) {
                        case modeMap.TOILET:
                        case modeMap.LOCATION:
                            sharedData.county = res[0]?.county;
                            sharedData.location_id = res[0]?.id;
                            break;
                        case modeMap.COUNTY:
                            sharedData.county = res[0]?.county;
                            break;
                        default:
                            break;
                    }
                    loading.value = false;
                }).catch((error) => {
                    console.log(error);
                    toast.error('無法取得資料');
                });
            });

            onUnmounted(() => {
                document
                    .getElementsByClassName("map-box")[0]
                    .removeEventListener("click", handleBack);
            });

            const handleBack = () => {
                // console.log(minMode());
                if (mode.value > minMode()) {
                    let current_mode = mode.value;

                    switch (current_mode - 1) {
                        case modeMap.TAIWAN:
                            sharedData.county = null;
                            break;
                        case modeMap.COUNTY:
                            sharedData.location_id = null;
                            break;
                        case modeMap.LOCATION:
                        case modeMap.TOILET:
                            sharedData.toilet_id = null;
                            sharedData.location_id = null;
                            break;
                        default:
                            break;
                    }
                }
            };

            const handleScrollToBlock = (block) => {
                switch (block) {
                    case "total-operation":
                        locationBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "human-traffic":
                        humanTrafficBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "toilet-paper":
                        toiletPaperBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "hand-lotion":
                        handLotionBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "smelly":
                        smellyBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "temperature":
                    case "relative-humidity":
                        tempHumidityBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "improve-handling":
                        improveBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;
                    case "exception-notification":
                        abnormalBlock.value?.scrollIntoView({
                            behavior: "smooth",
                        });
                        break;

                    default:
                        break;
                }
            };

            const fetchLocations = () => {
                return new Promise((resolve, reject) => {
                    axios
                      .get(params.fetchLocationUrl)
                      .then((res) => {
                        resolve(res);
                      })
                      .catch((error) => {
                        reject(error);
                      });
                  });
            };
            provide("handleScrollToBlock", handleScrollToBlock);
            provide("handleBack", handleBack);
            provide("minMode", minMode);
            provide("modeMap", modeMap);

            return {
                mode: mode,
                modeMap,
                handleBack,
                loading,
                locationBlock,
                humanTrafficBlock,
                toiletPaperBlock,
                smellyBlock,
                handLotionBlock,
                tempHumidityBlock,
                improveBlock,
                abnormalBlock,
            };
        },
    },
    {
        /* locations: JSON.parse(
            document.getElementById("app").getAttribute("data-locations")
        ), */
        params: JSON.parse(
            document.getElementById("app").getAttribute("data-params")
        ),
    }
);

app.config.globalProperties.$t = function (trans_key) {
    return lang[trans_key] ?? trans_key;
};

app.config.globalProperties.$utils = utilities;
app.config.globalProperties.$toast = toast;

// 設置全局的 CSRF Token
axios.defaults.headers.common["X-CSRF-TOKEN"] = utilities.$csrfToken();
axios.interceptors.response.use(
    function (resp) {
        // console.log(resp);
        let data = resp.data;
        if (data.code == 200) {
            return data.data;
        }
        if (data.code == 401 || data.code == 419) {
            window.location.reload();
            return;
        }
        if (data.hasOwnProperty("errors")) {
            if (Array.isArray(data.errors)) {
                console.table(data.errors);
                data.errors.forEach((element) => {
                    if (element.length > 0) {
                        toast.error(element);
                    }
                });
            } else if (typeof data.errors === "object") {
                for (const [key, errors] of Object.entries(data.errors)) {
                    if (Array.isArray(errors)) {
                        errors.forEach((element) => {
                            console.error(element);
                            toast.error(element);
                        });
                    } else {
                        console.error(errors);
                        toast.error(errors);
                    }
                }
            } else if (data.errors.length > 0) {
                toast.error(data.errors);
            }
        }
        return Promise.reject(new Error(JSON.stringify(data)));
    },
    function (error) {
        if (error?.response?.status == 419 || error?.response?.status == 401) {
            window.location.reload();
            return;
        }
        return Promise.reject(error);
    }
);

app.component("v-side-button", SideButtonComponent);
app.component("v-search-box", SearchComponent);
app.component("v-clock", ClockComponent);
app.component("v-leave-modal", LeaveModalComponent);
app.component("v-site-selection", SiteSelectionComponent);
app.component("v-map", MapComponent);
app.component("v-operational-location", OperationalLocationComponent);
app.component("v-performance-score", PerformanceScoreComponent);
app.component("v-location-info", LocationInfoComponent);
app.component("v-operational-today-chart", OperationalTodayChartComponent);
app.component("v-operational-monthly-chart", OperationalMonthlyChartComponent);
app.component("v-toilet-paper-block", ToiletPaperStatisticsBlockComponent);
app.component("v-human-traffic-block", HumanTrafficStatisticsBlockComponent);
app.component("v-hand-lotion-block", HandLotionStatisticsBlockComponent);
app.component("v-temp-humidity-block", TempHumidityStatisticsBlockComponent);
app.component("v-smelly-block", SmellyStatisticsBlockComponent);
app.component("v-abnormal-record", AbnormalComponent);
app.component("v-improve-record", ImproveComponent);
app.mount("#app");
window.app = app;
