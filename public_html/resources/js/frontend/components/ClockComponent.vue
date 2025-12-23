<template>
    <div class="date-box" v-if="date != null">
        <div class="text-date" v-if="current_date.length > 0">
            {{ current_date }}
        </div>
        <div class="text-weekday" v-if="current_weekday.length > 0">
            {{ current_weekday }}
        </div>
        <div class="text-time" v-if="current_time.length > 0">
            {{ current_time }}
        </div>
        <button class="btn btn-exit" @click="clickExit">{{ $t('frontend_exit') }}</button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            date: null,
            menus: [],
        };
    },
    mounted() {
        this.updateDate();
    },
    computed: {
        current_date() {
            if (this.date == null) {
                return "";
            }
            let year = this.date.getFullYear();
            let month = this.date.getMonth() + 1;
            month = String(month).padStart(2, "0");
            let date = this.date.getDate();
            date = String(date).padStart(2, "0");
            return `${year}/${month}/${date}`;
        },
        current_weekday() {
            if (this.date == null) {
                return "";
            }
            let weekdays = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"]
            let weekday = this.date.getDay();
            return weekdays[weekday];
        },
        current_time() {
            if (this.date == null) {
                return "";
            }
            let hour = this.date.getHours();
            hour = String(hour).padStart(2, "0");
            let minute = this.date.getMinutes();
            minute = String(minute).padStart(2, "0");
            let second = this.date.getSeconds();
            second = String(second).padStart(2, "0");

            return `${hour}:${minute}:${second}`;
        },
    },
    methods: {
        async updateDate() {
            this.date = new Date();
            requestAnimationFrame(this.updateDate);
        },
        clickExit() {
            // @todo
            this.$root.$refs.leaveModal.$refs.modal.openModal();
        },
    },
};
</script>