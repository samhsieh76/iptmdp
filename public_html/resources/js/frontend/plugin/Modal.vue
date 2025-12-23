<template>
  <MDBModal tabindex="-1" size="lg" v-model="modalOpen" staticBackdrop centered>
    <MDBModalBody>
      <MDBContainer fluid>
        <div class="bg"></div>
        <div class="left-top-border"></div>
        <div class="right-top-border"></div>
        <div class="left-bottom-border"></div>
        <div class="right-bottom-border"></div>
        <slot name="modal-content"></slot>
      </MDBContainer>
    </MDBModalBody>
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
  data() {
    return {
      modalOpen: false,
    };
  },
  methods: {
    openModal() {
      this.modalOpen = true;
    },
    closeModal() {
      this.modalOpen = false;
    },
  },
};
</script>