<template>
  <v-content>
    <template #content_header>
      <slot name="breadcrumb"></slot>
    </template>
    <template #content>
      <form ref="form" class="row" @submit.prevent="handleSubmit">
        <div class="col-12 mb-4 d-flex justify-content-end">
          <MDBBtn color="save" type="submit">{{ $t("save") }}</MDBBtn>
        </div>
        <div
          class="col-md-6"
          v-for="program in programs"
          :key="program.id"
          :program_id="program.id"
        >
          <table class="table custom-table table-hover w-100">
            <thead class="radius-0">
              <tr>
                <th style="width: 2rem">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    name="checkAll[]"
                    @change="checkAll($event, program.id)"
                  />
                </th>
                <th class="text-nowrap">{{ program.display_name }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="action in program.actions" :key="action.id">
                <td>
                  <input
                    type="checkbox"
                    class="form-check-input"
                    v-model="bind_permissions"
                    :value="action.permission_id"
                  />
                </td>
                <td>{{ action.display_name }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </form>
    </template>
  </v-content>
</template>

<script>
import contentComponent from "../ContentComponent.vue";
import { MDBBtn } from "mdb-vue-ui-kit";
export default {
  components: {
    "v-content": contentComponent,
    MDBBtn,
  },
  props: {
    actionUrl: {
      type: String,
      required: true,
    },
    role: {
      type: Object,
    },
    programs: {
      type: Array,
    },
    role_permissions: {
      type: Array,
    },
  },
  data() {
    return {
      bind_permissions: [],
    };
  },
  mounted() {
    this.bind_permissions = this.role_permissions;
  },
  methods: {
    async handleSubmit() {
      await this.sendSave()
        .then((res) => {
          if ('messages' in res) {
            this.$toast.success(res.messages);
          }
        })
        .catch(this.$utils.$errorHandler);
    },
    sendSave() {
      return new Promise((resolve, reject) => {
        const requestType = "put";
        const formData = {
          permissions: this.bind_permissions,
        };
        axios
          .request({
            url: this.actionUrl,
            method: requestType,
            data: formData,
          })
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    },
    checkAll(e, program_id) {
      const tbody = document.querySelector(
        `div[program_id="${program_id}"] tbody`
      );
      if (tbody) {
        const inputs = tbody.querySelectorAll("input");
        if (e.target.checked) {
          inputs.forEach((input) => {
            let index = this.bind_permissions.findIndex((el) => el == input.value);
            if (index == -1) {
              this.bind_permissions.push(input.value);
            }
          });
        } else {
          inputs.forEach((input) => {
            let index = this.bind_permissions.findIndex((el) => el == input.value);
            if (index !== -1) {
              this.bind_permissions.splice(index, 1);
            }
          });
        }
      }
    },
  },
};
</script>
