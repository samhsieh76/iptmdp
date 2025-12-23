<template>
  <settingModal
    ref="modal"
    id="locationRequestSettingModal"
    form_id="locationRequestSettingForm"
    :data-src="dataSrc"
  >
    <template #title>{{ $t("request_permission") }}</template>
    <template #modal-content>
      <div class="form-group">
        <label for="request_location">{{
          $t("location_request_location")
        }}</label
        ><span class="text-danger">*</span>
        <input
          type="text"
          class="form-control"
          v-model="dataSrc.request_location"
          id="request_location"
          :placeholder="$t('location_auth_code_placeholder')"
        />
      </div>
      <div v-if="this.records !== 'undefine' && this.records.length > 0">
        <label class="mt-2">{{ $t("location_audit_record") }}</label>
        <table class="request-records-table">
          <thead>
            <tr>
              <th>{{ $t("location_audit_record_request_location") }}</th>
              <th>{{ $t("location_audit_record_status") }}</th>
              <th class="table-right">
                {{ $t("location_audit_record_created_at") }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in this.records" :key="record.id">
              <td>{{ record.location_name }}</td>
              <td>{{ this.statusOptions[record.status] }}</td>
              <td class="table-right">{{ record.created_at }}</td>
            </tr>
          </tbody>
        </table>
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
    url: {
      type: String,
      required: true,
    },
    statusOptions: {
      type: Object,
      required: true,
    },
  },
  mounted() {

  },
  data() {
    return {
      records: [],
      dataSrc: {},
      defaultSrc: {
        request_location: null,
      },
    };
  },
  methods: {
    updateResource() {
      this.getLocationAuditRecord(this.url)
      .then((response) => {
        this.records = response;
      })
      .catch(this.$utils.$errorHandler);
    },
    resetData() {
      Object.entries(this.defaultSrc).forEach(([field, value]) => {
        if (field in this.dataSrc) {
          this.dataSrc[field] = value;
        }
      });
    },
    getLocationAuditRecord(url) {
      return new Promise((resolve, reject) => {
        axios
          .get(url)
          .then((res) => {
            resolve(res);
          })
          .catch((error) => {
            console.log(error);
            reject(error);
          });
      });
    },
  },
};
</script>