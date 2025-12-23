require("../bootstrap");
import { createApp } from "vue";
import "default-passive-events";
import UserInfoDropdownComponent from "./components/UserInfoDropdownComponent";
import DatetimeClockComponent from "./components/DatetimeClockComponent";
import GlobalSearchComponent from "./components/GlobalSearchComponent";
import RoleListComponent from "./components/Role/RoleListComponent";
import UserListComponent from "./components/User/UserListComponent";
import ToiletListComponent from "./components/Toilet/ToiletListComponent";
import LocationListComponent from "./components/Location/LocationListComponent";
import ActionListComponent from "./components/Action/ActionListComponent";
import ProgramListComponent from "./components/Program/ProgramListComponent";
import ApiandServeListComponent from "./components/ApiandServe/ApiandServeListComponent";
import RoleModalComponent from "./components/Role/RoleSettingModalComponent";
import UserModalComponent from "./components/User/UserSettingModalComponent";
import ToiletModalComponent from "./components/Toilet/ToiletSettingModalComponent";
import UserPasswordModalComponent from "./components/User/UserPasswordModalComponent";
import SelfPasswordModalComponent from "./components/User/SelfPasswordModalComponent";
import LocationSettingModalComponent from "./components/Location/LocationSettingModalComponent";
import ActionSettingModalComponent from "./components/Action/ActionSettingModalComponent";
import ProgramSettingModalComponent from "./components/Program/ProgramSettingModalComponent";
import RolePermissionListComponent from "./components/Role/RolePermissionListComponent";
import LocationRequestModalComponent from "./components/Location/LocationRequestModalComponent";
import ToiletBackendDashboard from "./components/Toilet/ToiletBackendDashboard";
import SensorListComponent from "./components/Sensor/SensorListComponent";
import SensorSettingModalComponent from "./components/Sensor/SensorSettingModalComponent";
import SensorShowComponent from "./components/Sensor/SensorShowComponent";
import LocationSensorListComponent from "./components/Sensor/LocationSensorListComponent";
import SensorLogSettingModalComponent from "./components/Sensor/SensorLogSettingModalComponent";

import VCalendar from "v-calendar";
import lang from "../../lang/zh-TW.json";
import utilities from "../utilities";
import toast from "../toast";
import "v-calendar/style.css";
import $ from "jquery";

const app = createApp({
    data() {
        return {
            menus: [],
        };
    },
    mounted() {
        window.addEventListener('pageshow', function(event) {
            // 檢查 event.persisted 屬性來確認是否是從瀏覽器緩存中載入的頁面
            if (event.persisted) {
                // 重新整理登入頁面
                window.location.reload();
            }
        });
    },
    unmounted() {

    },
    computed: {
        hasHistory() {
            return window.history.length > 1;
        },
    },
    methods: {
        handleHistoryBackClick: function () {
            window.history.back();
        }
    },
});

app.config.globalProperties.$t = function (trans_key) {
    return lang[trans_key] ?? trans_key;
};

app.config.globalProperties.$utils = utilities;
app.config.globalProperties.$toast = toast;

// 設置全局的 CSRF Token
axios.defaults.headers.common["X-CSRF-TOKEN"] = utilities.$csrfToken();
axios.interceptors.response.use(
    function (resp) {
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
            // window.location.reload();
            return;
        }
        return Promise.reject(error);
    }
);

$.fn.dataTable.ext.errMode = function (settings, tn, msg) {
    if (
        settings &&
        settings.jqXHR &&
        (settings.jqXHR.status === 401 || settings.jqXHR.status === 419)
    ) {
        // 處理 401 或 419 錯誤，例如重新導向
        window.location.reload();
        return;
    }
    // 其他錯誤處理
    alert(msg);
};
app.use(VCalendar, {});

app.component("v-dropdown-userinfo", UserInfoDropdownComponent);
app.component("v-clock", DatetimeClockComponent);
app.component("v-global-search", GlobalSearchComponent);
app.component("v-role-list", RoleListComponent);
app.component("v-user-list", UserListComponent);
app.component("v-location-list", LocationListComponent);
app.component("v-action-list", ActionListComponent);
app.component("v-program-list", ProgramListComponent);
app.component("v-toilet-list", ToiletListComponent);
app.component("v-api_and_serve-list", ApiandServeListComponent);
app.component("v-role-permissions", RolePermissionListComponent);
app.component("v-role-modal", RoleModalComponent);
app.component("v-user-modal", UserModalComponent);
app.component("v-user-password-modal", UserPasswordModalComponent);
app.component("v-self-password-modal", SelfPasswordModalComponent);
app.component("v-location-modal", LocationSettingModalComponent);
app.component("v-toilet-modal", ToiletModalComponent);
app.component("v-location-request-modal", LocationRequestModalComponent);
app.component("v-action-modal", ActionSettingModalComponent);
app.component("v-program-modal", ProgramSettingModalComponent);
app.component("v-toilet-dashboard", ToiletBackendDashboard);
app.component("v-sensor-list", SensorListComponent);
app.component("v-sensor-modal", SensorSettingModalComponent);
app.component("v-sensor-show", SensorShowComponent);
app.component("v-sensor-log-modal", SensorLogSettingModalComponent);
app.component("v-location-sensor-list", LocationSensorListComponent);
app.component("icon", {
    props: ["name"],
    template: `
      <svg :class="['icon', 'icon-' + name]">
        <use :xlink:href="'#icon-' + name"></use>
      </svg>
    `,
});

app.mount("#app");
window.app = app;
