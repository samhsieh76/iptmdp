<template>
  <settingModal
    ref="modal"
    id="userSettingModal"
    form_id="userSettingForm"
    :data-src="dataSrc"
    :storeUrl="storeUrl"
  >
    <template #title="titleProps">{{
      `${titleProps.isCreate ? $t("add") : $t("edit")}${$t("user")}`
    }}</template>
    <template #modal-content="modalProps">
      <div class="form-group">
        <label for="username">{{ $t("username") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.username"
          id="username"
          :readonly="!modalProps.isCreate"
        />
      </div>
      <div class="form-group" v-if="modalProps.isCreate">
        <label for="password">{{ $t("password") }}</label
        ><span class="text-danger">*</span>
        <input
          type="password"
          class="form-control"
          v-model="dataSrc.password"
          id="password"
        />
      </div>
      <div class="form-group" v-if="modalProps.isCreate">
        <label for="confirm_password">{{ $t("confirm_password") }}</label
        ><span class="text-danger">*</span>
        <input
          type="password"
          class="form-control"
          v-model="dataSrc.confirm_password"
          id="confirm_password"
        />
      </div>
      <div class="form-group">
        <label for="name">{{ $t("user_name") }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.name"
          id="name"
        />
      </div>
      <div class="form-group">
        <label for="role_id">{{ $t("user_role") }}</label
        ><span class="text-danger">*</span>
        <select
          name="role_id"
          id="role_id"
          class="form-select"
          v-model="dataSrc.role_id"
          @change="changeRole()"
        >
          <option
            :value="role.id"
            v-for="role in roleOptions"
            v-bind:key="role.id"
          >
            {{ role.name }}
          </option>
        </select>
      </div>
      <div class="form-group" v-if="required_parent">
        <label for="parent_id">{{ $t("user_parent") }}</label
        ><span class="text-danger">*</span>
        <select
          name="parent_id"
          id="parent_id"
          class="form-select"
          v-model="dataSrc.parent_id"
        >
          <option
            :value="parent_user.id"
            v-for="parent_user in role_parents"
            v-bind:key="parent_user.id"
          >
            {{ parent_user.name }}
          </option>
        </select>
      </div>
      <div class="form-group">
        <label for="email">{{ $t("user_email") }}</label>
        <span class="text-danger">*</span>
        <input
          type="email"
          class="form-control"
          v-model="dataSrc.email"
          id="email"
        />
      </div>
      <div class="form-group">
        <label for="phone">{{ $t("user_phone") }}</label>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.phone"
          id="phone"
        />
      </div>
    </template>
  </settingModal>
</template>

<script>
import settingModal from "../SettingModalComponent";
export default {
  components: {
    settingModal,
  },
  props: {
    roleOptions: {
      type: Array,
    },
    storeUrl: {
      type: String
    },
    parentUrl: {
      type: String
    }
  },
  mounted() {
    this.searchParents();
  },
  data() {
    return {
      roleParentOptions: [],
      dataSrc: {},
      defaultSrc: {
        username: null,
        password: null,
        confirm_password: null,
        name: null,
        email: null,
        phone: null,
        role_id: null,
        parent_id: null,
      },
    };
  },
  mounted() {
    this.changeRole();
  },
  methods: {
    updateResource(res) {
      this.dataSrc.username = res.username;
      this.dataSrc.password = res.password;
      this.dataSrc.confirm_password = res.confirm_password;
      this.dataSrc.name = res.name;
      this.dataSrc.email = res.email;
      this.dataSrc.phone = res.phone;
      this.dataSrc.role_id = res.role_id;
      this.searchParents();
      this.dataSrc.parent_id = res.parent_id;
    },
    changeRole() {
      this.searchParents();
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        if (field in this.dataSrc) {
          this.dataSrc[field] = value;
        }
      });
    },
    searchParents() {
      if(this.required_parent) {
        this.getRoleParents().then((res) => {
          this.roleParentOptions = res;
        })
      }
    },
    getRoleParents() {
      return new Promise((resolve, reject) => {
        axios
          .request({
            url: this.parentUrl.replace('role_id', this.dataSrc.role_id),
            method: 'get',
          })
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            reject(error);
          });
      });
    }
  },
  computed: {
    required_parent() {
      if ("role_id" in this.dataSrc) {
        let role = this.roleOptions.find((el) => el.id == this.dataSrc.role_id);
        return role? role.required_parent || false : false;
      }
      return false;
    },
    role_parents() {
      return this.roleParentOptions;
      /* if ("role_id" in this.dataSrc) {
        return this.roleParentOptions[this.dataSrc.role_id] || [];
      }
      return []; */
    },
  },
};
</script>