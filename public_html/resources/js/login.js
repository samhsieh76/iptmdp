require("./bootstrap");
import { createApp } from "vue";
import "default-passive-events";

import lang from "../lang/zh-TW.json";
import utilities from "./utilities";
import toast from "./toast";
import FlashMessagesRedirect from "./FlashMessagesRedirect.vue";

const app = createApp({
    mounted() {
        if (!window.location.href.includes("login")) {
            sessionStorage.setItem('shouldReloadLogin', 'true');
        }
    },
    unmounted() {

    },
    methods: {

    }
});

app.component("flash-messages-redirect", FlashMessagesRedirect);
app.config.globalProperties.$t = function (trans_key) {
    return lang[trans_key] ?? trans_key;
};

app.config.globalProperties.$utils = utilities;
app.config.globalProperties.$toast = toast;

app.mount("#app");
window.app = app;
