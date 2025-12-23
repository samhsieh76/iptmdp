<template>
  <div :class="`content-wrapper ${sensor_menus.length > 0 ? 'sidebar' : ''}`">
    <div class="sensor-menus" v-if="sensor_menus.length > 0">
      <ul class="nav">
        <li
          v-for="sensor_menu in sensor_menus"
          :key="sensor_menu.name"
          class="nav-item"
        >
          <a
            :href="show_menu_children ? '' : sensor_menu.path"
            :class="[
              'nav-link',
              {
                active: sensor_menu.active,
                collapsed:
                  sensor_menu.children.length > 0 && show_menu_children,
                'has-children': sensor_menu.children.length > 0 && show_menu_children
              }
            ]"
            :data-toggle="show_menu_children ? 'collapse' : null"
            :data-target="show_menu_children ? '#' + sensor_menu.name : null"
            :aria-expanded="show_menu_children ? 'false' : null"
          >
            <i :class="['icon', sensor_menu.icon]"></i>
            {{ $t(sensor_menu.name) }}
          </a>
          <ul
            class="nav nav-treeview"
            v-if="sensor_menu.children.length > 0 && show_menu_children"
            :id="sensor_menu.name"
            :class="{ 'collapse show': sensor_menu.children.some(child => child.active) }"
          >
            <li
              v-for="(children, index) in sensor_menu.children"
              :key="index"
              class="nav-treeview-item"
            >
              <a
                :href="children.path"
                :class="['nav-treeview-link', { active: children.active }]"
              >
                {{ $t(children.name) }}
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
    <div class="main-container">
      <div class="content-header container-fluid">
        <slot name="content_header"></slot>
      </div>
      <div class="content container-fluid" :style="{overflowY: overflowY}">
        <slot name="content"></slot>
      </div>
      <slot name="footer">
        <footer id="footer">
          <div class="logo" />
          <span class="copy_text">
            {{ $t("copyright").replace("{year}", new Date().getFullYear()) }}
          </span>
        </footer>
      </slot>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    sensor_menus: {
      type: Array,
      default: [],
    },
    show_menu_children: {
      type: Boolean,
      default: true,
    },
    overflowY: {
      type: String
    }
  },
};
</script>