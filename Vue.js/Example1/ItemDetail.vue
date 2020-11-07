<template>
  <div class="d-flex align-center justify-space-between" style="width: 100%;">
    <div class="d-flex align-center">
      <v-expand-x-transition>
        <strong
          v-show="reportObject.children && reportObject.children.length"
          @click="treeStore.toggleOpen(reportObject)"
        >
          <v-btn icon>
            <v-icon>{{reportObject.open ? 'remove' : 'add'}}</v-icon>
          </v-btn>
        </strong>
      </v-expand-x-transition>
      <span class="ml-2">
        <template v-if="!reportObject.topic_type_id">{{ reportObject.name }}</template>
        <!-- Report Object is Topic -->
        <template v-else>{{ reportObject.topicName }} <i>(Report Section)</i></template>
      </span>
    </div>
    <div>
      <v-btn
        icon
        @click="changeReportObject(reportObject.name, reportObject.entity_id)"
      >
        <v-icon>edit</v-icon>
      </v-btn>
      <v-btn
        icon
        color="error"
        @click="deleteReportObject(reportObject)"
      >
        <v-icon>delete</v-icon>
      </v-btn>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReportDetailItem',
  props: {
    reportObject: {
      type: Object,
      default: null
    },
    treeStore: {
      type: Object,
      default: null
    }
  },
  methods: {
    changeReportObject(name, entity_id) {
      this.$emit('onReportObjectEdit', { name, entity_id })
    },
    deleteReportObject (node) {
      this.$store.dispatch('deleteReport_object', this.reportObject.entity_id);
      this.$emit('onReportObjectDelete', node)
    }
  }
}
</script>

<style scoped>
</style>
