<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">User Profiles</h1>
          <p class="text-green-600">Manage all user profiles in the system</p>
        </div>
        <router-link
          to="/profiles/create"
          class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          <i class="mr-2 pi pi-plus"></i>
          Add New Profile
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-6 mb-6 bg-white shadow-md rounded-xl">
        <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
          <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
          <div>
            <h3 class="font-medium">Error Loading Profiles</h3>
            <p>{{ error }}</p>
          </div>
        </div>
        <button
          @click="fetchProfiles"
          class="px-4 py-2 mt-4 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          Retry
        </button>
      </div>

      <!-- Profiles Table -->
      <div v-else class="overflow-hidden bg-white shadow-md rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Username</th>
              <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Email</th>
              <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Phone</th>
              <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Created</th>
              <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="profile in profiles" :key="profile.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 w-10 h-10">
                    <div class="flex items-center justify-center w-full h-full text-white bg-green-600 rounded-full">
                      {{ profile.username.charAt(0).toUpperCase() }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ profile.username }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ profile.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ profile.phone || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ formatDate(profile.createdAt) }}</div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                <router-link
                  :to="`/profiles/${profile.id}`"
                  class="px-3 py-1 mr-2 text-blue-600 hover:text-blue-900"
                >
                  <i class="pi pi-eye"></i> View
                </router-link>
                <router-link
                  :to="`/profiles/${profile.id}/edit`"
                  class="px-3 py-1 mr-2 text-yellow-600 hover:text-yellow-900"
                >
                  <i class="pi pi-pencil"></i> Edit
                </router-link>
                <button
                  @click="confirmDelete(profile)"
                  class="px-3 py-1 text-red-600 hover:text-red-900"
                >
                  <i class="pi pi-trash"></i> Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="!loading && !error && profiles.length === 0" class="p-8 text-center">
          <div class="mx-auto mb-4 text-green-500 w-14 h-14">
            <i class="text-5xl pi pi-user"></i>
          </div>
          <h3 class="mb-2 text-xl font-medium text-gray-800">No Profiles Found</h3>
          <p class="mb-4 text-gray-600">You haven't created any profiles yet.</p>
          <router-link
            to="/profiles/create"
            class="inline-flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            <i class="mr-2 pi pi-plus"></i>
            Create First Profile
          </router-link>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <DeleteConfirmationModal
        v-if="showDeleteModal"
        :item-name="profileToDelete?.username"
        @confirm="deleteProfile"
        @cancel="showDeleteModal = false"
        :loading="deleting"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Parse from 'parse/dist/parse.min.js';
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue';

const profiles = ref([]);
const loading = ref(true);
const error = ref(null);
const showDeleteModal = ref(false);
const profileToDelete = ref(null);
const deleting = ref(false);

// Format date for display
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Fetch all profiles
const fetchProfiles = async () => {
  loading.value = true;
  error.value = null;
  try {
    const Profile = Parse.Object.extend('Profile');
    const query = new Parse.Query(Profile);
    query.include('user');
    query.descending('createdAt');
    const results = await query.find();

    profiles.value = results.map(profile => ({
      id: profile.id,
      username: profile.get('username'),
      email: profile.get('email'),
      phone: profile.get('phone'),
      createdAt: profile.createdAt,
      updatedAt: profile.updatedAt,
      user: profile.get('user'),
      parseObject: profile
    }));
  } catch (err) {
    console.error('Error fetching profiles:', err);
    error.value = 'Failed to load profiles. Please try again.';
  } finally {
    loading.value = false;
  }
};

// Confirm delete action
const confirmDelete = (profile) => {
  profileToDelete.value = profile;
  showDeleteModal.value = true;
};

// Delete profile
const deleteProfile = async () => {
  deleting.value = true;
  try {
    await profileToDelete.value.parseObject.destroy();
    profiles.value = profiles.value.filter(p => p.id !== profileToDelete.value.id);
    showDeleteModal.value = false;
  } catch (err) {
    console.error('Error deleting profile:', err);
    error.value = 'Failed to delete profile. Please try again.';
  } finally {
    deleting.value = false;
  }
};

// Load data on component mount
onMounted(() => {
  fetchProfiles();
});
</script>

<style scoped>
/* Animation for table rows */
tr {
  transition: all 0.2s ease;
}
</style>
