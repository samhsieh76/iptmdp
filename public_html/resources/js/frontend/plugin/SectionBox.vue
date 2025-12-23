<template>
    <div class="section-box">
        <div class="bg"></div>
        <div class="left-top-border"></div>
        <div class="right-top-border"></div>
        <div class="left-bottom-border"></div>
        <div class="right-bottom-border"></div>
        <div class="section-title" v-if="title">{{ title }}</div>
        <div class="section-content" v-show="hasContent">
            <slot name="section-content"></slot>
        </div>
        <div class="empty_data" v-show="!hasContent">
            {{ $t("dashboard_empty_data") }}
        </div>
    </div>
</template>

<script>
export default {
    props: {
        hasContent: {
            type: Boolean,
            default: false
        },
        title: {
            type: String
        }
    },
    setup() {
        return {}
    },
}
</script>