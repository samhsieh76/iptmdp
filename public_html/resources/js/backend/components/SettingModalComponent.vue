<template>
  <MDBModal :id="id" tabindex="-1" v-model="modalOpen" staticBackdrop>
    <form :id="form_id" ref="form" @submit.prevent="handleSubmit">
      <MDBModalHeader>
        <MDBModalTitle>
          <slot name="title" :isCreate="isCreate"></slot>
        </MDBModalTitle>
      </MDBModalHeader>
      <MDBModalBody>
        <MDBContainer fluid>
          <slot name="modal-content" :isCreate="isCreate"></slot>
        </MDBContainer>
      </MDBModalBody>
      <MDBModalFooter>
        <slot name="btn-negative">
          <MDBBtn color="cancel" @click="modalOpen = false">{{
            $t("cancel")
          }}</MDBBtn>
        </slot>
        <slot name="btn-positive">
          <MDBBtn color="save" type="submit" :disabled="isSaveDisabled">{{
            $t("save")
          }}</MDBBtn>
        </slot>
      </MDBModalFooter>
    </form>
  </MDBModal>
</template>

<script>
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
  props: {
    id: {
      type: String,
      default: "settingModal",
    },
    form_id: {
      type: String,
      default: "settingForm",
    },
    storeUrl: {
      type: String,
    },
    dataSrc: {
      type: Object,
    },
    defaultSrc: {
      type: Object,
    },
    hasFile: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      modalOpen: false,
      isCreate: false,
      actionUrl: null,
      isSaveDisabled: false,
    };
  },
  methods: {
    openCreateModal() {
      this.isCreate = true;
      this.actionUrl = this.storeUrl;
      this.openModal();
      this.$nextTick(() => {
        this.resetData();
      });
    },
    openPasswordModal(dataUrl) {
      this.isCreate = false;
      this.actionUrl = dataUrl;
      this.openModal();
      this.$nextTick(() => {
        this.resetData();
      });
    },
    openRequestModal(dataUrl) {
      this.isCreate = false;
      this.actionUrl = dataUrl;
      this.$parent.updateResource();
      this.openModal();
      this.$nextTick(() => {
        this.resetData();
      });
    },
    openEditModal(dataUrl) {
      this.isCreate = false;
      this.getResource(dataUrl)
        .then((response) => {
          this.actionUrl = response.actionUrl;
          this.$parent.updateResource(response.resource);
          this.openModal();
        })
        .catch(this.$utils.$errorHandler);
    },
    openModal() {
      this.modalOpen = true;
    },
    closeModal() {
      this.modalOpen = false;
      this.resetData();
    },
    async handleSubmit() {
      if (this.isSaveDisabled) {
        return;
      }
      this.isSaveDisabled = true;
      await this.sendSave()
        .then((res) => {
          // console.log('handleSubmit');
          if ("messages" in res) {
            this.$toast.success(res.messages);
          }
          if (this.$root.$refs.list) {
            this.$root.$refs.list?.$refs?.dataTable?.reload();
          }
          this.modalOpen = false;
        })
        .catch(this.$utils.$errorHandler);
      this.isSaveDisabled = false;
    },
    sendSave() {
      return new Promise((resolve, reject) => {
        const requestType = this.isCreate || this.hasFile ? "post" : "put";
        let formData;
        if (this.hasFile) {
          formData = new FormData();
          for (const key in this.dataSrc) {
            if (this.dataSrc[key] != null) {
              formData.append(key, this.dataSrc[key]);
            }
          }
          if (!this.isCreate) formData.append("_method", "PUT");
        } else {
          formData = this.dataSrc;
        }
        let options = {
          url: this.actionUrl,
          method: requestType,
          data: formData,
        };
        if (this.hasFile) {
          options.headers = {
            "Content-Type": "multipart/form-data",
          };
        }
        axios
          .request(options)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    },
    getResource(dataUrl) {
      return new Promise((resolve, reject) => {
        axios
          .get(dataUrl)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    },
    resetData() {
      this.$parent.resetData();
    },
  },
};
</script>