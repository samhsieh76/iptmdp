<template v-if="user">
  <div class="dropdown user-menu">
    <div class="user-avatar" data-toggle="dropdown"></div>
    <div
      class="userinfo dropdown-menu dropdown-menu-lg dropdown-menu-right"
      v-on:click.stop
    >
      <div class="info-title" v-if="user && user.name">{{ user.name }}</div>
      <span v-if="user && user.username">{{ user.username }}</span>
      <ul class="info-body" v-if="(user && user.email) || locations.length > 0">
        <li class="info-block" v-if="user && user.email">
          <div class="info-name">{{ $t("user_email") }}</div>
          <div class="info-content">{{ user.email }}</div>
        </li>
        <template v-for="location in locations" :key="location.id">
          <li class="info-block" v-if="location && location.address">
            <div class="info-name">{{ $t("location_address") }}</div>
            <div class="info-content">{{ location.address }}</div>
          </li>
          <li class="info-block" v-if="location && location.auth_code">
            <div class="info-name">{{ $t("location_auth_code") }}</div>
            <div class="info-content d-flex justify-content-between">
              {{ location.auth_code }}
              <i
                class="icon-copy"
                v-on:click.stop="copyAuthCode(location.auth_code)"
              ></i>
            </div>
          </li>
        </template>
      </ul>
      <div v-if="guard == 'web'">
        <button class="btn btn-self-password" @click="clickChangePassword">{{ $t('edit_password') }}</button>
        <form :action="logoutUrl" method="post">
          <input type="hidden" name="_token" :value="csrfToken" />
          <button type="submit" class="btn btn-logout">
            {{ $t("logout") }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      csrfToken: "",
    };
  },
  mounted() {
    this.csrfToken = this.$utils.$csrfToken();
  },
  props: {
    user: {
      type: Object,
    },
    locations: {
      type: Array,
    },
    guard: {
      type: String
    },
    logoutUrl: {
      type: String,
      required: true,
    },
    passwordUrl: {
      type: String,
      required: true
    }
  },
  methods: {
    copyAuthCode(auth_code) {
      navigator.clipboard
        .writeText(auth_code)
        .then(() => {
          this.$toast.success("複製成功");
        })
        .catch((error) => {
          this.$toast.error("複製失敗");
          console.error("Failed to copy text:", error);
        });
    },
    clickChangePassword() {
      this.$root.$refs.settingSelfPasswordModal?.$refs?.modal?.openPasswordModal(this.passwordUrl);
    }
  },
};
</script>