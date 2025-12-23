<template>
  <!-- Modal 訊息 -->
  <MDBModal
    id="flashMessageRedirect"
    tabindex="-1"
    v-model="showDialog"
    staticBackdrop
    centered
  >
    <MDBModalHeader>
      <MDBModalTitle>{{ deviceMessage }}</MDBModalTitle>
    </MDBModalHeader>
    <MDBModalBody>
      <MDBContainer fluid>
        <MDBRow class="text-center">
          <MDBCol md="6" class="mb-2 mb-md-0">
            <MDBBtn color="cancel" class="w-100" @click="keepDesktop"
              >保留在電腦版</MDBBtn
            >
          </MDBCol>
          <MDBCol md="6">
            <MDBBtn color="save" class="w-100" @click="goMobile"
              >立即前往</MDBBtn
            >
          </MDBCol>
        </MDBRow>
        <MDBRow class="text-center">
          <MDBCol>
            <div class="mt-1 text-center text-secondary text-sm w-100">
              {{ autoCloseVDialogCountdown }} 秒後將自動導向至手機版網站
            </div>
          </MDBCol>
        </MDBRow>
      </MDBContainer>
    </MDBModalBody>
  </MDBModal>
</template>
<style lang="scss">
.modal .modal-header .btn-close {
  box-sizing: content-box;
  width: 1em;
  height: 1em;
  padding: 0.25em;
  color: #000;
  background: transparent
    url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3E%3C/svg%3E")
    50% / 1em auto no-repeat;
  border: 0;
  border-radius: 0.25rem;
  opacity: 0.5;
}
.btn-cancel {
  background: rgb(0 0 0 / 20%);
  border: 2px solid hsla(0, 0%, 100%, 0.2);
  color: #fff !important;
  height: 40px;
}
.btn-save {
  background: #2FA7CD;
  border: 2px solid hsla(0, 0%, 100%, 0.2);
  color: #fff !important;
  height: 40px;
}
</style>>
<script>
import device from "current-device";
import {
  MDBModal,
  MDBModalHeader,
  MDBModalTitle,
  MDBModalBody,
  MDBModalFooter,
  MDBBtn,
  MDBRow,
  MDBCol,
  MDBContainer,
  MDBInput,
} from "mdb-vue-ui-kit";
import { ref, onMounted } from "vue";
export default {
  components: {
    MDBModal,
    MDBModalHeader,
    MDBModalTitle,
    MDBModalBody,
    MDBModalFooter,
    MDBBtn,
    MDBRow,
    MDBCol,
    MDBContainer,
    MDBInput,
  },
  setup() {
    const isDesktop = device.desktop();
    const webMobile = window.location.href.includes("iptmdp.utrust.com.tw");
    const showDialog = ref(false);
    const autoCloseVDialogCountdown = ref(10);
    const isStayDesktop = ref(false);
    const deviceMessage = ref("偵測到您的裝置為手機");

    const autoCloseMessage = () => {
      const intervalId = setInterval(() => {
        if (autoCloseVDialogCountdown.value === 1) {
          clearInterval(intervalId);
          if (!isStayDesktop.value) {
            goMobile();
          }
        }
        autoCloseVDialogCountdown.value--;
      }, 1000);
    };

    onMounted(() => {
      detectDevice();
      if (webMobile && !isDesktop) {
        showDialog.value = true;
        autoCloseMessage();
      }
    });

    const keepDesktop = () => {
      showDialog.value = false;
      isStayDesktop.value = true;
    };
    const goMobile = () => {
      window.location.href = "https://m-iptmdp.utrust.com.tw/";
    };

    const detectDevice = () => {
      if (device.mobile()) {
        deviceMessage.value = "偵測到您的裝置為手機";
      } else if (device.tablet()) {
        deviceMessage.value = "偵測到您的裝置為平板";
      }
    }
    return {
      showDialog,
      autoCloseVDialogCountdown,
      keepDesktop,
      goMobile,
      deviceMessage
    };
  },
};
</script>