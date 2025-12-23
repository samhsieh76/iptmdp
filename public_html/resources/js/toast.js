import { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";

const toast = useToast({
    position: "top-right",
    timeout: 3 * 1000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: false,
    showCloseButtonOnHover: true,
    hideProgressBar: true,
    closeButton: "button",
    icon: true,
    rtl: false
});
export default {
    success(message) {
        toast.success(message);
    },
    default(message) {
        toast(message);
    },
    info(message) {
        toast.info(message);
    },
    warning(message) {
        toast.warning(message);
    },
    error(message) {
        toast.error(message);
    },
    alert(message) {
        alert(message);
    },
};
