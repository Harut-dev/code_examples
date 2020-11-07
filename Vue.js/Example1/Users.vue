<template>
  <v-col sm="12" md="6" lg="6">
    <v-card>
      <v-toolbar >
        <v-card-title>Users</v-card-title>
        <v-spacer></v-spacer>
        <v-btn  class="ml-5 btn-primary btn-primary--small  "
                @click="addUser">
          + Add
        </v-btn>
      </v-toolbar>
      <v-list two-line style="min-height: 450px;">
        <template v-for="(item, index) in users">
          <v-list-item
          :key="item.user_id"
          :disabled="item.user_id === user.user_id"
          @click="editUser(item)">
            <v-list-item-content>
              <v-list-item-title v-html="item.name"></v-list-item-title>
            </v-list-item-content>
            <v-btn
            icon
            v-if="hasUserRemoveAccess(item, user.user_id)"
            @click.stop="openDeleteUserConfirmationDialog(item)"
            >
              <img class="close-icon" src="../../../assets/icons/trash.svg" alt="">
            </v-btn>
          </v-list-item>
          <v-divider
                  v-if="index < users.length - 1">
          </v-divider>
        </template>
      </v-list>
    </v-card>
    <!-- Dialogs -->
    <v-dialog persistent v-model="userDialog" max-width="500">
      <v-card>
        <v-card-title class="headline">{{userEditMode}} User</v-card-title>
        <v-container grid-list-xl fluid >
          <v-layout row wrap>
            <v-flex md12>
              <v-text-field v-if="userEditMode === 'Add'"
                            v-model="username"
                            required
                            prepend-icon="person"
                            label="Name*"></v-text-field>
              <v-text-field
                      v-else :disabled="true"
                      v-model="username"
                      prepend-icon="person"
                      label="Name*"></v-text-field>
            </v-flex>
          </v-layout>
          <v-layout row wrap>
            <v-flex md12>
              <v-text-field
                      v-if="userEditMode === 'Add'"
                      v-model="useremail"
                      label="Email*"
                      required
                      prepend-icon="mail"
                      :rules="emailRules"></v-text-field>
            </v-flex>
          </v-layout>
          <v-layout row wrap>
            <v-flex md12>
              <v-select
                      :items="userRoles"
                      item-text="name"
                      :value="userRole"
                      label="Role"
                      @change="userRoleChange($event)"
              ></v-select>
            </v-flex>
          </v-layout>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" text @click="userDialog = false">Cancel</v-btn>
          <v-btn
          class="ml-5 btn-primary btn-primary--small"
          :disabled="!canSave()"
          @click="saveUser">
          Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!--    Confirm Delete dialog-->
    <v-dialog v-model="dialogRemoveUser.open" persistent max-width="400">
      <v-card>
        <v-card-title class="headline">Please confirm</v-card-title>
        <v-card-text>Are you want to delete <strong>{{dialogRemoveUser.name}}</strong>?</v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="green darken-1" text @click="dialogRemoveUser.open = false">Cancel</v-btn>
          <v-btn color="red darken-1" text @click="deleteUser(dialogRemoveUser.entityId)">Confirm</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-col>
</template>

<script>
  import Vue from 'vue'
  import {mapGetters, mapState} from "vuex";
  export default {
  name: 'Users',
  computed: {
    ...mapGetters(['activeProject', 'user']),
    ...mapState('ProjectEditor', ['userRole', 'users'])
  },
  data() {
    return {
      userDialog: false,
      userEditMode: 'Add',
      username: '',
      useremail: '',
      emailRules: [ v => /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i.test(String(v).toLowerCase()) || 'Invalid Email address' ],
      userRoles: ['provider admin', 'provider analyst', 'client manager', 'client analyst', 'participant'],
      activeUser: null,
      dialogRemoveUser: {
        open: false,
        entityId: null,
        name: '',
      },
      loggedUserRoleForProject: {}
    }
  },
  methods: {
    canSave() {
      if(this.userEditMode === 'Edit') return true;
      return /^[^.\s]/.test(this.username) && /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i.test(String(this.useremail).toLowerCase())
    },
    async saveProject(e) {
      this.$store.commit('ProjectEditor/setName', e.name);
      var _this = this;
      if (this.users.length === 0) {
        this.$store.commit('ProjectEditor/setUsers', [{
          'user_id': this.user_id,
          'name': this.user.name,
          'role': 'provider admin'
        }])
      }
      if (this.user_ids.length === 0) {
        this.$store.commit('ProjectEditor/setUserIds', [this.user_id])
      }
      this.setValues(this, this.activeProject);
      await this.$store.dispatch('saveProject', this.activeProject).then(function (project) {
        if (!project['error'] && window.location.pathname !== '/Projecteditor/' + project.entity_id) {
          _this.$store.commit('ProjectEditor/setEditMode', 'Edit');
          Vue.notify({
            group: 'loggedIn',
            type: 'success',
            text: 'User deleted'
          })
        }
      })
    },
    setValues(source, destination) {
      destination['version'] = source['version']
      if (source['id'] !== 'new') {
        destination['entity_id'] = source['id']
      }
      destination['name'] = source['name']
      destination['user_id'] = source['user_id']
      destination['users'] = source['users']
      destination['user_ids'] = source['user_ids']
      destination['description'] = source['description']
    },
    addUser() {
      this.userDialog = true;
      this.activeUser = {};
      this.username = '';
      this.useremail = '';
      this.userEditMode = 'Add'
    },
    editUser(user) {
      this.userDialog = true;
      this.activeUser = user;
      this.username = user.name;
      this.$store.commit('ProjectEditor/setUserRole', user.role);
      this.userEditMode = 'Edit'
    },
    openDeleteUserConfirmationDialog(item) {
      this.dialogRemoveUser = { open: true, entityId: item.user_id, name: item.name };
    },
    async deleteUser(user_id) {
      for(let i=0; i < this.activeProject.users.length; i++){
        let user = this.activeProject.users[i]
        if(user.user_id === user_id){
          this.activeProject.users.splice(i, 1)
        }
      }
      for(let i=0; i < this.activeProject.user_ids.length; i++){
        if(user_id === this.activeProject.user_ids[i]){
          this.activeProject.user_ids.splice(i, 1)
        }
      }
      await this.$store.dispatch('saveProject', this.activeProject).then((project) => {
        if (!project['error']) {
          Vue.notify({
            group: 'loggedIn',
            type: 'success',
            text: 'User deleted'
          })
        }
        this.dialogRemoveUser = { open: false, entityId: null, name: '' };
      })
    },
    userRoleChange(e) {
      this.$store.commit('ProjectEditor/setUserRole', e);
    },
    async saveUser() {
      if(this.userEditMode === 'Add'){
        let user = {
          'user_id': null,
          'name': this.username,
          'email': this.useremail,
          'role': this.userRole
        };
        this.activeProject.users.push(user)
      }
      else {
        for(let i=0; i < this.activeProject.users.length; i++){
          let user = this.activeProject.users[i]
          if((user.user_id === this.activeUser.user_id) && (user.role !== this.activeUser.role)){
            this.activeProject.users[i].role = this.activeUser.role
          }
        }
      }
      this.userDialog = false
      await this.$store.dispatch('saveProject', this.activeProject).then(function (project) {
        if (!project['error']) {
          Vue.notify({
            group: 'loggedIn',
            type: 'success',
            text: 'User saved'
          })
        }
      })
    },
    hasUserRemoveAccess(item, user_id) {
      if (item.user_id === user_id) {
        return false
      }
      let hasAccess = false;
      switch (this.loggedUserRoleForProject.role) {
        case 'provider admin':
          hasAccess = true;
          break;
        case 'provider analyst':
          break;
        case 'client manager':
          if (item.role === 'client analyst') {
            hasAccess = true;
          }
          break;
        case 'client analyst':
          break;
        case 'participant':
          break;
      }
      return hasAccess;
    }
  },
  created(){
    const _loggedUserRoleForProject = this.users.filter(user => user.user_id === this.user.user_id);
    if(_loggedUserRoleForProject[0] !== undefined && _loggedUserRoleForProject[0]['role']) {
      this.loggedUserRoleForProject = _loggedUserRoleForProject[0];
    }
  },
  watch: {
    userRole() {
      this.activeUser.role = this.userRole
    }
  }
}
</script>

<style scoped>
</style>
